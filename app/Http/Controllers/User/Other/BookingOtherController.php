<?php

namespace App\Http\Controllers\User\Other;

use App\Exceptions\OtherReservationException;
use App\Http\Controllers\CommonUseCase\Reservation\BookingCoreController;
use App\Http\Requests\Api\User\Other\CreateReservationInputInfomationRequest;
use App\Http\Requests\Api\User\Other\GetReservationBlockRequest;
use App\Http\Requests\User\Other\ConfirmOtherBookingRequest;
use App\Http\Requests\User\RenderSearchPanelRequest;
use App\Jobs\Pms\ReservationCancelJob;
use App\Models\BaseCustomerItemValue;
use App\Models\CancelPolicy;
use App\Models\Form;
use App\Models\Hotel;
use App\Models\HotelNote;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Models\ReservationBlock;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservedReservationBlock;
use App\Support\Api\ApiClient;
use Carbon\Carbon;
use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;


class BookingOtherController extends BookingCoreController
{
    public function __construct()
    {
        parent::__construct();
        $this->form_service = app()->make('FormSearchService');
        $this->convert_cancel_service = app()->make('ConvertCancelPolicyService');
        $this->calc_other_form_service = app()->make('CalcOtherFormAmountService');
        $this->booking_session_key = 'booking_other';
        $this->confirm_session_key = 'booking_confirm';
        $this->reservation_block_session_key = 'booking_other.reservation_block';
        $this->reserve_change_service = app()->make('ReserveChangeService');
        $this->calc_cancel_policy_service = app()->make('CalcCancelPolicyService');
        $this->hard_item_service = app()->make('HardItemService');
    }

    public function renderSearchPanel(RenderSearchPanelRequest $request)
    {
        $lpParam = $request->get('url_param', '');
        $isAdminSearch = $lpParam == 'AdminUrlParam';

        if ($isAdminSearch && !$request->hasValidSignature()) {
            abort(401);
        }

        $lineUserId = null;
        if ($request->has('signature')) {
            if (!$request->hasValidSignature()) {
                abort(401, 'Signature Invalid');
            } else {
                $lineUserId = $request->get('line_user_id', null);
                $this->reserve_session_service->putSessionByKey('line_user_id', $lineUserId);
            }
        } else {
            $lineUserId =  $this->reserve_session_service->getSessionByKey('line_user_id');
        }

        try {
            // キャンセルポリシーの取得
            if (!$isAdminSearch) {
                $res = $request->cancelPolicy();
                if (!$res['res']) {
                    return redirect($res['url']);
                }
            }

            // LPとフォームデータのチェック
            $res = $this->checkLpAndForm($lpParam);
            if (!$res['res']) {
                $notReserve = 1;
                $attentionMessage = $res['message'];
                return view('user.booking.other.search_panel', compact('notReserve', 'attentionMessage'));
            }
            $lp = $res['lp'];
            $form = $res['form'];

            $today = Carbon::today();
            // 有効な販売期間であるかをチェック
            $res = $request->validateSalePeriod($form, $today);
            if (!$res['res']) {
                $notReserve = 1;
                $attentionMessage = $res['message'];
                return view('user.booking.other.search_panel', compact('notReserve', 'attentionMessage'));
            }

            $nowYear = $today->format('Y');
            $nowMonth = $today->format('n');

            $title = '予約検索 | ' . $lp['title'];
            $currentPage = 1;

            $changeBookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            if (!empty($changeBookingData) && !empty($changeBookingData['reservation'])) {
                return redirect()->route('user.booking.other.search_panel', ['url_param' => $lpParam]);
            }

            $hotelId = $isAdminSearch ? $request->hotel_id : $form['hotel_id'];
            $hotel = Hotel::find($hotelId);
            $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();

            $roomTypes = [];
            // forms.is_room_typeが1の場合はforms.room_type_idsに含まれるroom_typesのみを返す
            $isRoomType = $form->is_room_type;
            if ($isRoomType == 1) {
                $roomTypeIds = $form->room_type_ids;
                $getRoomTypes = HotelRoomType::where('hotel_id', $hotelId)
                    ->whereIn('id', $roomTypeIds)->with(['hotelRoomTypeImages'])->get();
            } else {
                $getRoomTypes = HotelRoomType::where('hotel_id', $hotelId)->with(['hotelRoomTypeImages'])->get();
            }
            if ($getRoomTypes->isEmpty()) {
                // 部屋タイプの一覧が取得できなかった時
                $notReserve = 1;
                $attentionMessage = '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。';
                return view('user.booking.other.search_panel', compact('notReserve', 'attentionMessage'));
            }
            foreach ($getRoomTypes as $key => $room_type) {
                $roomTypes[][$room_type->id] = [
                    'name' => $room_type->name,
                    'images' => $room_type->hotelRoomTypeImages->sortBy('id')->map(function ($item) {
                        return $item->imageSrc();
                    })->toArray(),
                ];
            }
            // キャンセルポリシーの取得
            $cancelPolicy = $isAdminSearch ? CancelPolicy::where('hotel_id', $hotelId)->where('is_default', true)->first() : $form->cancelPolicy()->first();

            if ($isAdminSearch && empty($cancelPolicy)) {
                $notReserve = 1;
                $attentionMessage = 'デフォルトのキャンセルポリシーが設定されていません。';
                return view('user.booking.other.search_panel', compact('notReserve', 'attentionMessage'));
            }

            $isFreeCancel = $cancelPolicy->is_free_cancel;
            $isRequestReservation = $form['is_request_reservation'];
            // キャンセルポリシーをテキストに整形する
            $cancelDesc = $this->convert_cancel_service->cancelConvert($isFreeCancel, $cancelPolicy->free_day, $cancelPolicy->free_time, $cancelPolicy->cancel_charge_rate);
            $noShowDesc = $this->convert_cancel_service->noShowConvert($cancelPolicy->no_show_charge_rate);

            // ページをまたぐ、POSTデータをセッションに格納する & セッション上で部屋タイプを識別するワンタイムトークンを$roomTypesに格納する
            // ブラウザ上でidを参照・書き換えさせないため
            $roomTypeTokens = $this->reserve_session_service->makeRoomTypeTokens($roomTypes);
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key);
            $roomTypes = $this->reserve_session_service->putOtherRoomTypes($roomTypes, $lpParam, $hotelId, $roomTypeTokens);
            // 元のidは見せたくないので振り直し
            $roomTypes = array_values($roomTypes);

            // 事業の種類を取得
            $businessType = $hotel->business_type;

            return $isAdminSearch ? view('user.booking.other.admin_search_panel', compact('nowYear', 'nowMonth', 'lpParam', 'title', 'currentPage', 'hotel', 'hotelNotes', 'roomTypes', 'cancelDesc', 'noShowDesc', 'businessType', 'lineUserId')) : view('user.booking.other.search_panel', compact('nowYear', 'nowMonth', 'lpParam', 'title', 'currentPage', 'hotel', 'hotelNotes', 'roomTypes', 'cancelDesc', 'noShowDesc', 'businessType', 'isRequestReservation'));
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $title = '予約検索 | ' . $lp['title'];
            $notReserve = 1;
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return view('user.booking.other.search_panel', compact('notReserve', 'attentionMessage', 'title'));
        }
    }


    public function getReservationBlock(GetReservationBlockRequest $request): JsonResponse
    {
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');
        $isAvailable = $request->get('is_available', '') == 'true';

        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            if (empty($bookingData)) {
                return $this->error('申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。', 400);
            }
            $urlParam = $this->reserve_session_service->getUrlParam($this->booking_session_key);
            $isAdminSearch = $urlParam == 'AdminUrlParam';

            // LPとフォームデータのチェック
            $res = $this->checkLpAndForm();
            if (!$res['res']) {
                return $this->error($res['message'], 404);
            }
            $form = $res['form'];

            // 有効な予約可能期間であるかをチェック
            if ($form->is_deadline === 1) {
                $compareSrcStart = Carbon::parse($startDate);
                $compareSrcEnd = Carbon::parse($endDate);
                $deadlineStart = Carbon::parse($form->deadline_start);
                $deadlineEnd = Carbon::parse($form->deadline_end);
                if ($compareSrcStart->gt($deadlineEnd) || $compareSrcEnd->lt($deadlineStart)) {
                    // 有効な予約可能期間外の場合
                    return $this->success([
                        'reservation_blocks' => [],
                    ]);
                }
                // リクエストの予約枠取得の開始日が予約可能期間より前の日付の場合、予約可能期間の開始日を予約枠取得の開始日とする
                if ($compareSrcStart->lt($deadlineStart)) {
                    $startDate = $deadlineStart->format('Y-m-d');
                }
                // リクエストの予約枠取得の終了日が予約可能期間より後の日付の場合、予約可能期間の終了日を予約枠取得の終了日とする
                if ($compareSrcEnd->gt($deadlineEnd)) {
                    $endDate = $deadlineEnd->format('Y-m-d');
                }
            }

            // formsレコードのroom_type_ids（json）内に、リクエストされたroom_type_tokenのidが含まれているかチェック
            $condition = [
                'hotel_id' => $isAdminSearch ? $request->hotel_id : $form->hotel_id ,
                // 手仕舞いされていない予約枠のみ取得する
                'is_closed' => 0,
            ];
            if ($isAvailable) {
                $condition['is_available'] = 1;
            }

            // トークンに紐付いた部屋タイプを取得
            $roomTypeToken = $request->get('room_type_token', '');
            $roomTypeInfo = $this->reserve_session_service->getOtherRoomTypeInfo($roomTypeToken);
            if (empty($roomTypeInfo)) {
                $attentionMessage = '申し訳ありません。指定された部屋タイプは有効ではございません。恐れ入りますが、別のページからお手続きくださいませ。';
                return $this->error($attentionMessage, 404);
            }

            // 部屋タイプが限定されている場合
            if ($form->is_room_type == 1 || $form->isAdminForm()) {
                $roomTypeId = $roomTypeInfo['room_type_id'];
            }
            if ($form->is_room_type == 1) {
                $res = $request->checkFormRoomTypeIds($form, $roomTypeId);
                if (!$res['res']) {
                    return $this->error($res['message'], 404);
                }
                $condition['room_type_id'] = $roomTypeId;
            }else if($form->isAdminForm()){
                $condition['room_type_id'] = $roomTypeId;
            } else {
                $condition['room_type_id'] = $roomTypeInfo['room_type_id'];
            }

            // reservation_blocks取得
            $reserveBlocks = ReservationBlock::with('roomType')
                ->where($condition)
                ->where('room_num', '>', 0)
                ->whereBetween('date', [$startDate, $endDate])
                ->orderBy('date', 'asc')->orderBy('start_hour', 'asc')->orderBy('start_minute', 'asc')
                ->get();

            // 料金計算 ここから
            // 以下の予約枠を削除する
            // - 料金が0で登録されている予約枠
            // - 受付開始時刻より前の予約枠
            // - 最終退館時刻を超えている予約枠
            // - 当日の予約枠で、現在時刻の15分後より前に開始する予約枠
            $minutesLater = Carbon::now()->addMinutes(15);
            $reserveBlocks = $reserveBlocks->filter(function ($value) use ($form, $minutesLater) {
                $reservationBlockStartTime = $this->browse_reserve_service->createCarbonTimeOver24Hour($value->date, sprintf('%02d:%02d', $value->start_hour, $value->start_minute));
                $reservationBlockEndTime = $this->browse_reserve_service->createCarbonTimeOver24Hour($value->date, sprintf('%02d:%02d', $value->end_hour, $value->end_minute));
                $checkinStartTime = $this->browse_reserve_service->createCarbonTimeOver24Hour($value->date, $form->hotel->checkin_start ?? '00:00');
                $checkoutEndTime = $this->browse_reserve_service->createCarbonTimeOver24Hour($value->date, $form->hotel->checkout_end ?? '24:00');
                return $value['price'] > 0 && $reservationBlockStartTime->gte($checkinStartTime) && $reservationBlockEndTime->lte($checkoutEndTime) && $reservationBlockStartTime->gte($minutesLater);
            })->toArray();
            if (!empty($form)) {
                foreach ($reserveBlocks as $blockNum => $block) {
                    $reserveBlocks[$blockNum]['amount'] = $this->calc_other_form_service->calcReservationBlockFormSettingAmount($form, $block);
                }
                // フォームの特別価格計算後に利用料金が0円以下の場合は除外する
                $reserveBlocks = array_filter($reserveBlocks, function ($block) {
                    return $block['amount'] > 0;
                });
            }
            // 料金計算 ここまで

            // idをtokenと差し替える
            $tokens = $this->reserve_session_service->makeReservationBlockTokens($reserveBlocks);
            $reserveBlocks = $this->reserve_session_service->putReservationBlocks($reserveBlocks, $tokens);

            $responseBlocks = [];
            foreach ($reserveBlocks as $b) {
                // reservation_blocksのperson_capacityがNULLの場合には、
                // room_typeのadult_numで、person_capacityの値を埋める
                $personCapacity = $b['person_capacity'] ?? $b['room_type']['adult_num'];
                $responseBlocks[$b['date']][] = [
                    'reservation_block_token' => $b['reservation_block_token'],
                    'is_available' => $b['is_available'],
                    'person_capacity' => $personCapacity,
                    'price' => $b['amount'],
                    'start_time' => sprintf('%02d:%02d', $b['start_hour'], $b['start_minute']),
                    'end_time' => sprintf('%02d:%02d', $b['end_hour'], $b['end_minute']),
                    'room_num' => $b['room_num'] - $b['reserved_num'],
                ];
            }

            $data = [
                'reservation_blocks' => $responseBlocks,
            ];
            return $this->success($data);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->reserve_session_service->forgetSessionByKey($this->reservation_block_session_key);
            return $this->error('予期せぬエラーが発生しました。', 500);
        }
    }



    /**
     * LPの存在とフォーム公開状態をチェック
     *
     * @param string|null $urlParam
     * @return array
     */
    private function checkLpAndForm(string $urlParam = null): array
    {

        $lpParam = $urlParam ?: $this->reserve_session_service->getUrlParam($this->booking_session_key);
        $lp = $this->browse_reserve_service->getFormFromLpParam($lpParam);
        if (empty($lp)) {
            return [
                'res' => false,
                'message' => '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。',
            ];
        }
        $formId = $lp['form_id'];
        $form = $this->form_service->findForm($formId);
        if (is_null($form) || $form->public_status == 0) {
            return [
                'res' => false,
                'message' => '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。',
            ];
        }
        return ['res' => true, 'lp' => $lp, 'form' => $form];
    }

    public function inputBookingInfo(CreateReservationInputInfomationRequest $request)
    {
        $selectedBlocks = $request->get('selected_blocks', []);

        $lineUserId = $this->reserve_session_service->getSessionByKey('line_user_id');
        Log::info("LINE USER ID:" . $lineUserId);

        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            if (empty($bookingData)) {
                return $this->error('申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。', 400);
            }

            // LPとフォームデータのチェック
            $res = $this->checkLpAndForm();
            if (!$res['res']) {
                $attentionMessage = $res['message'];
                return $this->error($attentionMessage, 400);
            }
            $form = $res['form'];
            $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
            $businessType = $hotel->business_type;
            $title = '予約情報入力';
            // ホテルの方で2ページ目がなくなっているので、2は欠番
            $currentPage = 3;

            $roomTypes = $this->reserve_session_service->makeRoomTypesFromSessionData($selectedBlocks);
            // Form料金の計算
            $roomTypes = collect($roomTypes)->map(function ($roomType) use ($form) {
                $roomType['amount'] = $this->calc_other_form_service->calcReservationBlockFormSettingAmount($form, $roomType);
                return $roomType;
            })->toArray();
            // 入力された部屋数分、予約可能であるか確認
            $bookable = collect($roomTypes)->every(function ($roomType) use ($roomTypes) {
                return $roomType['room_num'] - $roomType['reserved_num'] - count($roomTypes) >= 0;
            });

            if (empty($roomTypes) || !$bookable) {
                return $this->error('申し訳ございません、ご予約のお手続き中にご選択された予約枠が満室となりました。大変恐れ入りますが、再度日時をご選択くださいませ。', 400);
            }

            // チェックインの日付
            // 複数予約の場合、1番目の予約をチェックインの日時に指定する
            $checkinDate = $roomTypes[0]['date'];
            $startTime = $roomTypes[0]['start_time'];
            $endTime = $roomTypes[0]['end_time'];
            $checkinTime = $checkinDate . ' ' . $startTime;
            $checkoutTime = $checkinDate . ' ' . $endTime;

            // 料金
            $roomAmount['sum'] = $this->browse_reserve_service->calcSumAmount($roomTypes);
            // 部屋情報/料金をセッションに保存
            $this->reserve_session_service->putBookingRoomInfos($roomTypes, $roomAmount);

            $baseCustomerItems = [];
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.base_customer_items');
            if ($businessType == 3 || $businessType == 4) {
                // CRMから「予約時の入力項目」一覧を取得する
                $client = $hotel->client;
                $data = [
                    'client_id' => $client->pms_client_id,
                    'lineUserId' => $lineUserId
                ];
                $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $data);
                $baseCustomerItems = $apiClient->doGetRequest('base_customer_items/' . $hotel->crm_base_id . '?' . $apiClient->getUrlParams());
                if (!is_array($baseCustomerItems)) {
                    return $this->error('予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。', 400);
                }

                // sort_numの昇順に並び替え
                $sortKey = array_column($baseCustomerItems, 'sort_num');
                array_multisort($sortKey, SORT_ASC, $baseCustomerItems);
                // is_reservation_itemが1のレコードだけに絞り込む
                $baseCustomerItems = array_filter($baseCustomerItems, function ($item) {
                    return $item['is_reservation_item'] == 1;
                });
                $this->reserve_session_service->putSessionByKey($this->booking_session_key . '.base_customer_items', $baseCustomerItems);
            }

            // data_type=10のbaseCustomerItemがない場合
            $isNotExistsEmail = collect($baseCustomerItems)->filter(function ($item) {
                return $item['data_type'] == 10 && $item['is_reservation_item'] == 1;
            })->isEmpty();

            // LINEからの予約
            $lineGuestInfo = $this->reserve_session_service->getSessionByKey($this->booking_session_key . '.guest_from_line');
            if (!empty($lineGuestInfo)) {
                $lineGuestInfo['firstName'] = $lineGuestInfo['name'];
                $splitName = extractSpace($lineGuestInfo['name'], 2);
                if (!empty($splitName[1])) {
                    $lineGuestInfo['firstName'] = $splitName[0];
                    $lineGuestInfo['lastName'] = $splitName[1];
                }

                $lineGuestInfo['firstNameKana'] = $lineGuestInfo['nameKana'];
                $splitNameKana = extractSpace($lineGuestInfo['nameKana'], 2);
                if (!empty($splitNameKana[1])) {
                    $lineGuestInfo['firstNameKana'] = $splitNameKana[0];
                    $lineGuestInfo['lastNameKana'] = $splitNameKana[1];
                }
                $lineGuestInfo['tel'] = str_replace("-", "", $lineGuestInfo['tel']);
            }

            $redirectParams = compact(
                'bookingData',
                'hotel',
                'roomAmount',
                'checkinDate',
                'startTime',
                'endTime',
                'roomTypes',
                'form',
                'title',
                'currentPage',
                'lineGuestInfo',
                'baseCustomerItems',
                'businessType',
                'isNotExistsEmail'
            );

            // 予約更新であるかを判定
            $changeBookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key . '.change_info');
            if (!empty($changeBookingData)) {
                $reserveId = $changeBookingData['reservation_id'];
                $reservation = Reservation::with('baseCustomerItemValues')->find($reserveId);

                $existsItems = array_column(
                    $reservation->baseCustomerItemValues->toArray(),
                    null,
                    'base_customer_item_id'
                );
                $reservation['base_customer_item_values'] = collect(array_column($baseCustomerItems, null, 'id'))
                    ->map(function ($item) use ($reserveId, $existsItems, $roomTypes, &$startTime, &$endTime) {
                        if (array_key_exists($item['id'], $existsItems)) {
                            $existsItem = $existsItems[$item['id']];
                            // 予約情報にチェックイン/アウトの日時が登録されている場合は使用する
                            if ($existsItem['data_type'] == 14) {
                                $startTime = $existsItem['value'];
                            } elseif ($existsItem['data_type'] == 15) {
                                $endTime = $existsItem['value'];
                            } elseif ($existsItem['data_type'] == 12) {
                                $existsItem['value'] = $roomTypes[0]['room_name'];
                            }
                            return $existsItem;
                        }
                        return [
                            'reservation_id' => $reserveId,
                            'base_customer_item_id' => $item['id'],
                            'name' => $item['name'],
                            'data_type' => $item['data_type'],
                            'value' => ''
                        ];
                    })->toArray();
                $redirectParams['reservation'] = $reservation;
            }
            $redirectParams['startTime'] = $startTime;
            $redirectParams['endTime'] = $endTime;

            // リダイレクト時に必要なデータをセッションに保存
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.redirect_params');
            $this->reserve_session_service->putSessionByKey($this->booking_session_key . '.redirect_params', $redirectParams);
            $this->reserve_session_service->putSessionByKey($this->booking_session_key . '.line_user_id', $lineUserId);

            if (empty($changeBookingData)) {
                // 支払いステータスをセッションに保存
                $this->reserve_session_service->putSessionByKey($this->booking_session_key . '.payment_status', 0);
            }
            return $this->success();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.base_customer_items');
            return $this->error('申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。', 500);
        }
    }

    public function renderInputBookingInfo()
    {
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            $urlParam = $this->reserve_session_service->getUrlParam($this->booking_session_key);
            $isAdminSearch = $urlParam == 'AdminUrlParam';
            $redirectSessionKey = $this->booking_session_key . '.redirect_params';
            // リダイレクト時のセッションデータを取得
            $redirectParams = $this->reserve_session_service->getSessionByKey($redirectSessionKey);
            $isRequestReservation = $bookingData['redirect_params']['form']['is_request_reservation'];
            if (empty($redirectParams)) {
                $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
                return redirect(route('user.other.render_search_panel') . '?url_param=' . $bookingData['base_info']['url_param'])->with(['error' => $attentionMessage]);
            }

            extract($redirectParams);
            $params = compact(
                'bookingData',
                'hotel',
                'roomAmount',
                'checkinDate',
                'startTime',
                'endTime',
                'roomTypes',
                'form',
                'title',
                'currentPage',
                'lineGuestInfo',
                'baseCustomerItems',
                'businessType',
                'isNotExistsEmail',
				'isRequestReservation',
                'isAdminSearch'
            );

            // 予約変更の場合
            if (array_key_exists('reservation', $redirectParams)) {
                $params['reservation'] = $reservation;
                return view('user.booking.other.update_input', $params);
            }
            // 予約登録の場合
            return view('user.booking.other.input', $params);
        } catch (\Exception $e) {
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.base_customer_items');
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return redirect(route('user.other.render_search_panel') . '?url_param=' . $bookingData['base_info']['url_param'])->with(['error' => $attentionMessage]);
        }
    }


    public function renderAdminInputBookingInfo()
    {
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            $urlParam = $this->reserve_session_service->getUrlParam($this->booking_session_key);
            $isAdminSearch = $urlParam == 'AdminUrlParam';
            $redirectSessionKey = $this->booking_session_key . '.redirect_params';
            // リダイレクト時のセッションデータを取得
            $redirectParams = $this->reserve_session_service->getSessionByKey($redirectSessionKey);

            if (empty($redirectParams)) {
                $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
                return redirect(url()->previous())->with(['error' => $attentionMessage]);
            }

            extract($redirectParams);
            $params = compact(
                'bookingData',
                'hotel',
                'roomAmount',
                'checkinDate',
                'startTime',
                'endTime',
                'roomTypes',
                'form',
                'title',
                'currentPage',
                'lineGuestInfo',
                'baseCustomerItems',
                'businessType',
                'isNotExistsEmail',
                'isAdminSearch'
            );

            // 予約変更の場合
            if (array_key_exists('reservation', $redirectParams)) {
                $params['reservation'] = $reservation;
                return view('user.booking.other.update_input', $params);
            }
            // 予約登録の場合
            return view('user.booking.other.admin-input', $params);
        } catch (\Exception $e) {
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.base_customer_items');
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return redirect(url()->previous())->with(['error' => $attentionMessage]);
        }
    }

    public function saveReservationData(ConfirmOtherBookingRequest $request)
    {
        $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
        $lineUserId = $this->reserve_session_service->getSessionByKey('line_user_id');

        if (empty($bookingData)) {
            $notReserve = 1;
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return view('user.booking.other.search_panel', compact('notReserve', 'attentionMessage'));
        }

        $sessionTime = $this->reserve_session_service->getReservationInfoSessionTime($this->booking_session_key);
        if ($this->other_reserve_service->checkSessionTimeOut($sessionTime)) {
            // 選択済みの予約枠を破棄
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.selected_rooms');
            $attentionMessage = '一定時間操作がありませんでした。画面を再読み込みして再度お試しください。';
            return redirect(route('user.other.render_search_panel') . '?url_param=' . $bookingData['base_info']['url_param'])->with(['error' => $attentionMessage]);
        }

        $baseCustomerItems = $bookingData['base_customer_items'];

        // LPとフォームデータのチェック
        $res = $this->checkLpAndForm();
        if (!$res['res']) {
            $attentionMessage = $res['message'];
            return back()->withInput()->with(['error' => $attentionMessage]);
        }
        $form = $res['form'];

        $params = $this->browse_reserve_service->formatCheckInOutTime($baseCustomerItems, $request->all());

        // 選択済みの部屋を取得
        $selectedRooms = $bookingData['selected_rooms'] ?? [];
        if (empty($selectedRooms)) {
            return back()->withInput()->with(['error' => '予期せぬエラーが発生しました。']);
        }
        // 指定された期間の予約可能な予約枠の取得
        $reserveBlocks = $this->other_reserve_service->getReservationBlocks($selectedRooms);
        // 予約直前に予約枠がis_availableが0で更新されていた場合は予約不可
        if ($reserveBlocks->isEmpty()) {
            $attentionMessage = '申し訳ございません、ご予約のお手続き中にご選択された予約枠が満室となりました。大変恐れ入りますが、再度日時をご選択くださいませ。';
            return back()->withInput()->with(['error' => $attentionMessage]);
        }
        // 予約直前に料金が0で更新されていた場合は予約不可
        $amounts = $reserveBlocks->map(function ($block) use ($form) {
            return $this->calc_other_form_service->calcReservationBlockFormSettingAmount($form, $block->toArray());
        })->toArray();
        if (in_array(0, $amounts)) {
            $attentionMessage = '申し訳ございません、ご予約のお手続き中にご選択されたお部屋情報が更新されました。大変恐れ入りますが、再度お部屋をご選択くださいませ。';
            return back()->withInput()->with(['error' => $attentionMessage]);
        }

        // 予約データのDB保存 ここから
        $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
        $businessType = $hotel->business_type;

        $reserveBlock = $reserveBlocks[0];
        $cardData = collect($params)->only([
            'card_number', 'expiration_month', 'expiration_year', 'cvc', 'payment_method'
        ])->toArray();
        $saveData = $cardData;

        $isAdminSearch = $bookingData['base_info']['url_param'] == 'AdminUrlParam';
        if ($isAdminSearch) {
            $params['reservation_kinds'] = 1;
            $params['crm_base_id'] = $hotel->crm_base_id;
            $params['line_user_id'] = ["{$bookingData['line_user_id']}"];

            if (!empty($params['price'])) {
                $bookingData['room_amount']['sum'] = $params['price'];
            }
        }

        if ($businessType == 2 || $businessType == 5) {
            $saveData['address'] = $params['address1'] . ' ' . $params['address2'];
        } elseif ($businessType == 3 || $businessType == 4) {
            // data_typeによってパラメータを整形
            collect($baseCustomerItems)->each(function ($value) use (&$saveData, $params) {
                $dataType = $value['data_type'];
                $itemId = $value['id'];
                $key = '';
                if ($dataType == 8) {
                    $key = 'full_name';
                } elseif ($dataType == 9) {
                    $key = 'tel';
                } elseif ($dataType == 10) {
                    $key = 'email';
                } elseif ($dataType == 11) {
                    $key = 'address';
                }
                if (!empty($key)) {
                    $saveData[$key] = $params['item_' . $itemId];
                }
            });
            $saveData['checkin_time'] = $reserveBlock->date . ' ' . $reserveBlock->getStartTime();
            $saveData['checkout_time'] = $reserveBlock->date . ' ' . $reserveBlock->getEndTime();

            if (!array_key_exists('email', $saveData) && array_key_exists('email', $params)) {
                $saveData['email'] = $params['email'];
            }
        }
        $data = $this->browse_reserve_service->makeReservationSaveData($saveData, $bookingData, $hotel, $reserveBlock->date,$form);

        DB::beginTransaction();
        $reserveId = -1;
        $message = '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。';
        try {
            $verifyToken = uniqid() . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8);
            $saveReserveData = $this->makeReservationSaveData($data, $verifyToken);
            $reserveId = intval($this->reserve_service->insertStayReserveData($saveReserveData));
            // 事前決済の場合はStripeによる決済処理
            if ($saveReserveData['payment_method'] == 1) {
                $payData = array_merge(
                    $cardData,
                    collect($data)->only([
                        'name', 'email', 'tel', 'checkin_time'
                    ])->toArray(),
                );
                $prepay = $this->otherPrePay($payData, $reserveId, $form, $bookingData['room_amount']['sum']);
                if ($prepay['res'] != 'ok') {
                    throw new OtherReservationException($prepay['message']);
                }
                // 決済処理結果の更新
                $reservation = Reservation::where('id', $reserveId)->first();
                $reservation->fill([
                    'payment_method' => $payData['payment_method'] ?? null,
                    'stripe_payment_id' => $payData['stripe_payment_id'] ?? null,
                    'stripe_customer_id' => $payData['stripe_customer_id'] ?? null,
                    'payment_status' => $payData['payment_status'] ?? 0,
                    'approval_status'   => 1
                ])->save();
            }

            // reservation_cancel_policyレコードを保存
            $cancelPolicyId =  null;
            if ($isAdminSearch) {
                $cancelPolicy = $isAdminSearch ? CancelPolicy::where('hotel_id', $hotel->id)->where('is_default', true)->first() : $form->cancelPolicy()->first();
                $cancelPolicyId = $cancelPolicy->id;
            }
            $this->saveOtherReservationCancelPolicy($form, $reserveId, $hotel->id, $cancelPolicyId);


            // base_customer_item_valuesのレコードを保存
            $ret = $this->other_reserve_service->saveBaseCustomerItemValues($reserveId, $params, $baseCustomerItems);
            if (!$ret) {
                throw new OtherReservationException($message);
            }

            // CRM(PMS)側へ予約データを同期するための、APIリクエスト
            $ret = $this->other_reserve_service->savePmsReservationData(
                $bookingData,
                $hotel,
                $baseCustomerItems,
                $reserveId,
                $saveReserveData,
                $params,
                1,
                $lineUserId
            );
            if (is_null($ret) || !$ret->status || $ret->code !== 200) {
                Log::error(json_encode($ret));
                throw new OtherReservationException($message);
            }

            // 選択された部屋の分を更新
            collect($selectedRooms)->each(function ($room) use ($reserveId, $lineUserId, $ret) {
                // reservation_blocksのレコードを更新
                $result = $this->updateReservationBlocks($room['reservation_block_id']);
                if (!$result) {
                    $message = '申し訳ございません、ご予約のお手続き中にご選択されたお部屋情報が更新されました。大変恐れ入りますが、再度お部屋をご選択くださいませ。';
                    throw new OtherReservationException($message);
                }

                // reserved_reservation_blocksのレコードを保存
                $roomAmount = ['sum' => $room['amount']];
                $checkinTime = Carbon::parse($room['start_time']);
                list($endHour, $endMinute, $endSeconds) = explode(':', $room['end_time']);
                $this->saveReservedReservationBlocks([
                    'reservation_id' => $reserveId,
                    'reservation_block_id' => $room['reservation_block_id'],
                    'line_user_id' => $lineUserId ?? 0,
                    'customer_id' => $ret->data->base_customer_id ?? 0,
                    'person_num' => $room['person_num'],
                    'price' => $roomAmount['sum'],
                    'date' => $room['date'],
                    'start_hour' => $checkinTime->format('H'),
                    'start_minute' => $checkinTime->format('i'),
                    'end_hour' => $endHour,
                    'end_minute' => $endMinute,
                ]);
            });


            DB::commit();
        } catch (OtherReservationException $e) {
            $this->rollbackReservationData($reserveId, $params, $bookingData);
            return back()->withInput()->with(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            $this->rollbackReservationData($reserveId, $params, $bookingData);
            return back()->withInput()->with(['error' => $message]);
        }
        // 予約データのDB保存 ここまで

        // 予約情報を格納したセッションを消去
        $this->reserve_session_service->forgetSessionByKey($this->booking_session_key);
        $this->reserve_session_service->forgetSessionByKey('line_user_id');

        $isRequestReservation = $form['is_request_reservation'];
        // ユーザーが入力したメールアドレスに、予約完了メールを送信する
        $userShowUrl = $this->other_reserve_service->sendMail($verifyToken, $saveReserveData, $hotel, $reserveId, $isRequestReservation? 'request':"confirm");
        $title = '予約完了';
        return view('user.booking.other.complete', compact('userShowUrl', 'title', 'isRequestReservation'));
    }



    /**
     * 予約のロールバック
     *
     * @param int $reserveId
     * @param array $params
     * @param array $bookingData
     * @return void
     */
    private function rollbackReservationData(int $reserveId, array $params, array $bookingData): void
    {
        DB::rollback();
        if ($reserveId > 0) {
            if (!empty($params['stripe_payment_id'])) {
                $refundData = [];
                $stripeService = app()->make('StripeService');
                $stripeService->manageFullRefund($reserveId, $params['stripe_payment_id'], $bookingData['room_amount']['sum'], $refundData);
            }
            Reservation::where('id', $reserveId)->delete();
        }
    }

    /**
     * reservationsテーブルの保存データを整形する
     *
     * @param array $post
     * @param string $verifyToken
     * @return void
     */
    private function makeReservationSaveData(array $post, string $verifyToken): array
    {
        $saveData = [];
        $saveData['adult_num'] = $post['adult_num'];
        $saveData['child_num'] = $post['child_num'] ?? 0;
        $saveData['room_num'] = $post['room_num'];
        $saveData['address'] = $post['address'];
        $saveData['reservation_code'] = $post['reservation_code'];
        $saveData['client_id'] = $post['client_id'];
        $saveData['hotel_id'] = $post['hotel_id'];
        $saveData['name'] =  $post['name'];
        // $saveData['name_kana'] = $post['first_name_kana'] . ' ' . $post['last_name_kana'];
        $saveData['last_name'] = $post['last_name'] ?? '';
        $saveData['first_name'] = $post['first_name'] ?? '';
        $saveData['last_name_kana'] = $post['last_name_kana'] ?? '';
        $saveData['first_name_kana'] = $post['first_name_kana'] ?? '';
        $saveData['last_name_kana'] = $post['last_name_kana'] ?? '';
        $saveData['first_name_kana'] = $post['first_name_kana'] ?? '';
        $saveData['checkin_start'] = $post['checkin_start'];
        $saveData['checkin_end'] = $post['checkin_end'];
        $saveData['checkout_end'] = $post['checkout_end'];
        $saveData['checkin_time'] = $post['checkin_time'];
        $saveData['checkout_time'] = $post['checkout_time'];
        $saveData['email'] = $post['email'];
        $saveData['tel'] = $post['tel'];
        $saveData['payment_method'] = $post['payment_method'];
        $saveData['accommodation_price'] = $post['accommodation_price'];
        $saveData['commission_rate'] = config('commission.reserve_rate') * 100;
        $saveData['commission_price'] = $post['commission_price'];
        if ($post['payment_method'] == 0) {
            $saveData['payment_commission_rate'] = 0;
            $saveData['payment_commission_price'] = 0;
        } else {
            $saveData['payment_commission_rate'] = $post['payment_commission_rate'];
            $saveData['payment_commission_price'] = $post['payment_commission_price'];
        }
        $saveData['reservation_status'] = $post['reservation_status'];
        $saveData['lp_url_param'] = $post['lp_url_param'];
        $saveData['verify_token'] = $verifyToken;
        // $saveData['special_request'] = $post['special_request'];
        $saveData['reservation_date'] = Carbon::now()->format('Y-m-d H:i:s');
        $saveData['created_at'] = now();
        $saveData['is_request'] = $post['is_request'];

        return $saveData;
    }

    /**
     * reservation_cancel_policyテーブルにデータを保存する
     *
     * @param Form $form
     * @param string $reserveId
     * @param string $hotelId
     * @return boolean
     */
    private function saveOtherReservationCancelPolicy(Form $form, string $reserveId, string $hotelId, $cancelPolicyId = null): bool
    {
        $policy = CancelPolicy::find($cancelPolicyId ? $cancelPolicyId : $form->cancel_policy_id);
        return $this->reserve_service->saveReserveCanPoli($policy, $reserveId, $hotelId);
    }

    /**
     * reservation_blocksテーブルの予約部屋数と予約可能状態を更新する
     *
     * @param integer $reservationBlockId
     * @return boolean
     */
    private function updateReservationBlocks(int $reservationBlockId): bool
    {
        $block = ReservationBlock::where('id', $reservationBlockId)->first();
        if (empty($block)) {
            return false;
        }
        $roomNum = $block->room_num;
        $reservedNum = $block->reserved_num;
        $isAvailable = $block->is_available;
        if ($reservedNum >= $roomNum || $isAvailable != 1) {
            return false;
        }
        $newReservedNum = $reservedNum + 1;
        $block->reserved_num = $newReservedNum;
        if ($newReservedNum >= $roomNum) {
            $block->is_available = 0;
        }
        return $block->save();
    }

    /**
     * reserved_reservation_blocksテーブルへデータを保存する
     *
     * @param array $saveData
     * @return boolean falseの場合、DBへの保存エラー
     */
    private function saveReservedReservationBlocks(array $saveData): bool
    {
        $ret = ReservedReservationBlock::create($saveData);
        return !is_null($ret);
    }

    public function updateReservationData(ConfirmOtherBookingRequest $request)
    {
        $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
        if (empty($bookingData)) {
            $notReserve = 1;
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return view('user.booking.other.search_panel', compact('notReserve', 'attentionMessage'));
        }

        $sessionTime = $this->reserve_session_service->getReservationInfoSessionTime($this->booking_session_key);
        if ($this->other_reserve_service->checkSessionTimeOut($sessionTime)) {
            // 選択済みの予約枠を破棄
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.selected_rooms');
            $attentionMessage = '一定時間操作がありませんでした。画面を再読み込みして再度お試しください。';
            return redirect(route('user.other.render_search_panel') . '?url_param=' . $bookingData['base_info']['url_param'])->with(['error' => $attentionMessage]);
        }

        $baseCustomerItems = $bookingData['base_customer_items'];

        // LPとフォームデータのチェック
        $res = $this->checkLpAndForm();
        if (!$res['res']) {
            $attentionMessage = $res['message'];
            return back()->withInput()->with(['error' => $attentionMessage]);
        }
        $form = $res['form'];

        $params = $this->browse_reserve_service->formatCheckInOutTime($baseCustomerItems, $request->all());

        // 選択済みの部屋を取得
        $selectedRooms = $bookingData['selected_rooms'] ?? [];
        if (empty($selectedRooms)) {
            return back()->withInput()->with(['error' => '予期せぬエラーが発生しました。']);
        }
        // 指定された期間の予約可能な予約枠の取得
        $reserveBlocks = $this->other_reserve_service->getReservationBlocks($selectedRooms);
        // 予約直前に予約枠がis_availableが0で更新されていた場合は予約不可
        if ($reserveBlocks->isEmpty()) {
            $attentionMessage = '申し訳ございません、ご予約のお手続き中にご選択された予約枠が満室となりました。大変恐れ入りますが、再度日時をご選択くださいませ。';
            return back()->withInput()->with(['error' => $attentionMessage]);
        }
        // 予約直前に料金が0で更新されていた場合は予約不可
        $amounts = $reserveBlocks->map(function ($block) use ($form) {
            return $this->calc_other_form_service->calcReservationBlockFormSettingAmount($form, $block->toArray());
        })->toArray();
        if (in_array(0, $amounts)) {
            $attentionMessage = '申し訳ございません、ご予約のお手続き中にご選択されたお部屋情報が更新されました。大変恐れ入りますが、再度お部屋をご選択くださいませ。';
            return back()->withInput()->with(['error' => $attentionMessage]);
        }

        $changeBookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);
        $reserveId = $changeBookingData['change_info']['reservation_id'];
        $reservation = Reservation::find($reserveId);

        // 予約データの更新 ここから
        $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
        $businessType = $hotel->business_type;

        $reserveBlock = $reserveBlocks[0];
        $saveData = [];
        if ($businessType == 2 || $businessType == 5) {
            $saveData['address'] = $params['address1'] . ' ' . $params['address2'];
        } elseif ($businessType == 3 || $businessType == 4) {
            // data_typeによってパラメータを整形
            collect($baseCustomerItems)->each(function ($value, $idx) use (&$saveData, $params) {
                $dataType = $value['data_type'];
                $itemId = $value['id'];
                $key = '';
                if ($dataType == 8) {
                    $key = 'full_name';
                } elseif ($dataType == 9) {
                    $key = 'tel';
                } elseif ($dataType == 10) {
                    $key = 'email';
                } elseif ($dataType == 11) {
                    $key = 'address';
                }
                if (!empty($key)) {
                    $saveData[$key] = $params['item_' . $itemId];
                }
            });
            $saveData['checkin_time'] = $reserveBlock->date . ' ' . $reserveBlock->getStartTime();
            $saveData['checkout_time'] = $reserveBlock->date . ' ' . $reserveBlock->getEndTime();

            if (!array_key_exists('email', $saveData) && array_key_exists('email', $params)) {
                $saveData['email'] = $params['email'];
            }
        }
        $data = $this->browse_reserve_service->makeReservationSaveData($saveData, $bookingData, $hotel, $reserveBlock->date,$form);
        $data['payment_method'] = $reservation->payment_method;

        $saveReserveData = $this->makeReservationSaveData($data, $reservation->verify_token);
        // 更新処理用の項目に値を入れ替える
        $saveReserveData = $this->convertUpdateReserveData($saveReserveData);

        DB::beginTransaction();
        $message = '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。';
        try {
            // 決済情報を更新
            if ($form->prepay != 1) {
                if ($reservation->payment_method == 1) {
                    $reservation->update(['reservation_update_status' => 2]);
                    $result = $this->updatePrePay($reservation, $bookingData, $saveReserveData, $businessType, $bookingData['room_amount']['sum']);
                    if (!$result['res']) {
                        Reservation::where('id', $reservation->id)->update(['reservation_update_status' => 0]);
                        return back()->withInput()->with(['error' => $result['message']]);
                    }
                    $saveReserveData['stripe_payment_id'] = $result['payment_id'];
                    $saveReserveData['payment_status'] = $result['payment_status'];
                    $saveReserveData['reservation_update_status'] = 1;
                }
            } else {
                $saveReserveData['payment_method'] = 0;
                $data['payment_method'] = 0;
            }

            $this->reserve_service->updateStayReserveData($reserveId, $saveReserveData);

            // reservation_cancel_policyを更新
            $this->updateReserveCancelPolicyById($form->cancel_policy_id, $reserveId, $hotel->id);

            // 既存の予約枠のキャンセル処理
            $this->other_reserve_service->increaseReserveBlockByReservation($reservation);

            // 選択された部屋の予約処理
            collect($selectedRooms)->each(function ($room) use ($reserveId) {
                // reservation_blocksのレコードを更新
                $isIncrement = true;
                $ret = $this->updateReservationBlocks($room['reservation_block_id'], $isIncrement);
                if (!$ret) {
                    $message = '申し訳ございません、ご予約のお手続き中にご選択されたお部屋情報が更新されました。大変恐れ入りますが、再度お部屋をご選択くださいませ。';
                    throw new OtherReservationException($message);
                }

                // reserved_reservation_blocksのレコードを保存
                $roomAmount = ['sum' => $room['amount']];
                $checkinTime = Carbon::parse($room['start_time']);
                list($endHour, $endMinute, $endSeconds) = explode(':', $room['end_time']);
                $this->saveReservedReservationBlocks([
                    'reservation_id' => $reserveId,
                    'reservation_block_id' => $room['reservation_block_id'],
                    'customer_id' => 0,
                    'line_user_id' => 0,
                    'person_num' => $room['person_num'],
                    'price' => $roomAmount['sum'],
                    'date' => $room['date'],
                    'start_hour' => $checkinTime->format('H'),
                    'start_minute' => $checkinTime->format('i'),
                    'end_hour' => $endHour,
                    'end_minute' => $endMinute,
                ]);
            });

            // base_customer_item_valuesのレコードを保存
            $ret = $this->other_reserve_service->saveBaseCustomerItemValues($reserveId, $params, $baseCustomerItems);
            if (!$ret) {
                throw new OtherReservationException($message);
            }

            // CRM(PMS)側へ予約データを同期するための、APIリクエスト
            $ret = $this->other_reserve_service->savePmsReservationData(
                $bookingData,
                $hotel,
                $baseCustomerItems,
                $reserveId,
                $saveReserveData,
                $params,
                2,
                null
            );
            if (is_null($ret) || !$ret->status || $ret->code !== 200) {
                throw new OtherReservationException($message);
            }

            // 更新処理完了
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            if (!empty($saveReserveData['stripe_payment_id'])) {
                $refundData = [];
                $stripeService = app()->make('StripeService');
                $stripeService->manageFullRefund($reserveId, $saveReserveData['stripe_payment_id'], $bookingData['room_amount']['sum'], $refundData);
            }
            $this->reserveIncreaseRoomStock($reservation->id, $reservation->hotel);
            Reservation::where('id', $reservation->id)->update(['reservation_update_status' => 0]);
            return back()->withInput()->with(['error' => $e->getMessage() ?? $message]);
        }
        $this->cancelPrepay($reservation);
        // ユーザーが入力したメールアドレスに、予約完了メールを送信する
        $data['reservation_code'] = $reservation->reservation_code;
        $userShowUrl = $this->other_reserve_service->sendMail($reservation->verify_token, $data, $hotel, $reserveId, 'update');

        // 予約情報を格納したセッションをリセット
        $this->reserve_session_service->forgetSessionByKey($this->booking_session_key);
        $this->reserve_session_service->forgetSessionByKey($this->confirm_session_key);

        $title = '予約変更完了';
        $isRequestReservation = $form->is_request_reservation;
        return view('user.booking.other.complete', compact('userShowUrl', 'title', 'isRequestReservation'));
    }

    /**
     * 予約詳細
     */
    public function bookingShow($token)
    {
        try {
            $reservation = $this->reserve_change_service->findReservationByToken($token);
            $bookingApprovalMessage = '';
            $bookingApprovalStatus = $reservation['approval_status'];
            if($bookingApprovalStatus== 2) {
                if($reservation['reservation_status'] == 0) {
                    $bookingApprovalMessage = "予約は確定しています";
                }
                if($reservation['reservation_status']== 1) {
                    $bookingApprovalMessage = "予約はキャンセルされています";
                }
            }else {
                $bookingApprovalMessage = "ご予約はまだ確定していません。施設の確認をお待ち下さい。";
            }

            if (empty($reservation)) {
                $attentionMessage = 'ご予約が見つかりませんでした';
                $notReserve = 1;
                return view('user.booking.other.show', compact('attentionMessage', 'notReserve'));
            }
            if ($reservation['reservation_status'] != 0) {
                $attentionMessage = '既にキャンセル済みの予約です';
                $notReserve = 1;
                return view('user.booking.other.show', compact('attentionMessage', 'notReserve'));
            }

            $reserveCanPoli = $reservation->reservationCancelPolicy;
            $checkinTime = Carbon::parse($reservation->checkin_time);
            $checkinDate = $checkinTime->format('Y-m-d');
            $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow(NULL, $checkinDate, json_decode(json_encode($reserveCanPoli)));
            $cancelDesc = $this->convert_cancel_service->cancelConvert($isFreeCancel, $reserveCanPoli['free_day'], $reserveCanPoli['free_time'], $reservation->reservationCancelPolicy->cancel_charge_rate);
            $noShowDesc = $this->convert_cancel_service->noShowConvert($reservation->reservationCancelPolicy->no_show_charge_rate);


            $hotel = Hotel::find($reservation->hotel_id);
            $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();

            $reservedBlocks = $reservation->reservedBlocks;
            $reservation['rooms'] = $reservedBlocks->map(function($block) {
                $block->room_type_id = $block->reservationBlock->room_type_id;
                return $block;
            });
            $roomTypes = $reservedBlocks->map(function($block) {
                return $block->reservationBlock->roomType;
            })->pluck(null, 'id');

            $this->reserve_session_service->forgetSessionByKey($this->confirm_session_key);
            $this->reserve_session_service->putBookingConfirmInfo($this->confirm_session_key, [], $reservation);
            $cancelable = false;

            if (Carbon::now()->lt($checkinTime->tomorrow())) {
                $cancelable = true;
            }
            $title = '予約詳細';

            $businessType = $hotel->business_type;
            $baseCustomerItemValues = [];
            if ($businessType == 3 || $businessType == 4) {
                $baseItems = BaseCustomerItemValue::where('reservation_id', $reservation->id)->get();
                // CRMから「予約時の入力項目」一覧を取得する
                $client = $hotel->client;
                $data = [
                    'client_id' => $client->pms_client_id
                ];
                $apiClient = new ApiClient(config('signature.gb_signature_api_key'), $data);
                $baseCustomerItems = $apiClient->doGetRequest('base_customer_items/' . $hotel->crm_base_id . '?' . $apiClient->getUrlParams());
                if (!is_array($baseCustomerItems)) {
                    $message = '予期せぬエラーが発生しました。恐れ入りますが、お時間をおいて再度お試しくださいませ。';
                    throw new OtherReservationException($message);
                }

                // sort_numの昇順に並び替え
                $sortKey = array_column($baseCustomerItems, 'sort_num');
                array_multisort($sortKey, SORT_ASC, $baseCustomerItems);
                // is_reservation_itemが1のレコードだけに絞り込む
                $baseCustomerItems = array_filter($baseCustomerItems, function ($item) {
                    return $item['is_reservation_item'] == 1;
                });
                // CRMで設定されているソート順に並び替え
                $filtered = collect($baseCustomerItems)->map(function ($item) use ($baseItems) {
                    return $baseItems->search(function ($v) use ($item) {
                        return $v->base_customer_item_id == $item['id'];
                    });
                })->filter(function ($item) {
                    return $item !== false;
                });
                $baseCustomerItemValues = $filtered->map(function ($value) use ($baseItems) {
                    return $baseItems->get($value);
                });
            }

            // data_type=10のbaseCustomerItemがない場合
            $isNotExistsEmail = collect($baseCustomerItems)->filter(function ($item) {
                return $item['data_type'] == 10 && $item['is_reservation_item'] == 1;
            })->isEmpty();

            return view("user.booking.other.show", compact(
                'reservation',
                'hotel',
                'roomTypes',
                'hotelNotes',
                'isFreeCancel',
                'title',
                'cancelDesc',
                'noShowDesc',
                'cancelable',
                'baseCustomerItemValues',
                'isNotExistsEmail',
				'bookingApprovalMessage',
				'bookingApprovalStatus'
            ));
        } catch (\Exception $e) {
            $title = '予約詳細';
            $attentionMessage = $e->getMessage() ?? '予期せぬエラーが発生しました';
            $notReserve = 1;
            Log::error('=====================detail show error=======================');
            Log::error($e);
            Log::error('=====================error end=======================');
            return view('user.booking.other.show', compact('attentionMessage', 'notReserve', 'title'));
        }
    }

    public function otherCancelConfirm(Request $request)
    {
        DB::beginTransaction();
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);

            $sessionTime = $this->reserve_session_service->getCancelInfoSessionTime();
            if ($this->other_reserve_service->checkSessionTimeOut($sessionTime)) {
                return response()->json(['res' => 'error', 'message' => '一定時間操作がありませんでした。画面を再読み込みして再度お試しください。']);
            }

            $reserve = Reservation::find($bookingData['reservation']['id']);
            $hotel = Hotel::find($reserve->hotel_id);

            $cancelCommission = 0;
            if ($bookingData['reservation']['payment_method'] === 1) {
                $result = $this->cancelRefund($reserve, $bookingData);
                if (!$result['res']) {
                    return response()->json(['res' => 'error', 'message' => $result['message']]);
                }
                $cancelCommission = $result['commission'];
            }

            // キャンセル分の在庫データを復活させる
            $this->increaseReserveBlockByCancel($reserve);

            // 予約をキャンセルでステータスを更新する
            $nowDateTime = Carbon::now()->format('Y-m-d H:i:s');
            $res = $this->reserve_service->confirmCancel($reserve, $nowDateTime, $bookingData['cancel_info']['cancel_fee'], $cancelCommission);

            //キャンセルをCRMに通知
            $data = [
                "id" => $reserve->id,
                "reservation_kinds" => 3,
            ];

            dispatch(new ReservationCancelJob($data))->onQueue('pms-sync-job');
            DB::commit();
            return response()->json(['res' => 'ok']);
        } catch (\Exception $e) {
            Log::error('=====================cancel error=======================');
            Log::error($e->getMessage());
            Log::error('reservation and stripe info');
            Log::error('hotelId: ' . $hotel->id . '/ ' . $hotel->name . ' / reservationId: ' . $reserve->id . ' / stripe_payment_id: ' . $reserve->stripe_payment_id);
            Log::error($e);
            Log::error('=====================error end=======================');
            DB::rollback();
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました。']);
        }
    }

    public function ajaxChangeReserve(Request $request)
    {
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);
            $reservation = Reservation::where('id', $bookingData['reservation']['id'])->first();
            if ($reservation['reservation_status'] != 0) {
                return response()->json(['res' => 'error', 'message' => '既にキャンセル済みの予約です']);
            }
            $this->reserve_session_service->forgetSessionByKey($this->confirm_session_key . '.change_info');
            $this->reserve_session_service->putChangeInfo($bookingData['reservation']['id'], 1);
            $urlParam = $bookingData['reservation']['lp_url_param'];
            $searchUrl = route('user.other.render_search_panel') . '?url_param=' . $urlParam;
            return response()->json(['res' => 'ok', 'url' => $searchUrl]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました。']);
        }
    }

    public function checkCancelCondition(Request $request)
    {
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);
            $reservation = Reservation::where('id', $bookingData['reservation']['id'])->first();
            $checkinTime = Carbon::parse($reservation->checkin_time);
            $reservationCancelPolicy = ReservationCancelPolicy::where('id', $bookingData['reservation']['reservation_cancel_policy']['id'])->first();
            $canpoliService = app()->make('CancelPolicyService');
            $isFreeCancel = $canpoliService->checkFreeCancelByNow(NULL, $checkinTime->format('Y-m-d'), $reservationCancelPolicy);
            if ($checkinTime->lt(Carbon::now())) {
                if (!$isFreeCancel) {
                    return response()->json(['res' => 'error', 'message' => 'チェックイン時間を過ぎたので、キャンセルできません']);
                }
            }
            $paymentMethod = $bookingData['reservation']['payment_method'];
            $reserveId = $bookingData['reservation']['id'];
            $cancelPolicy = ReservationCancelPolicy::where('reservation_id', $reserveId)->first();
            $checkinDate = $checkinTime->format('Y-m-d');
            $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow(NULL, $checkinDate, json_decode(json_encode($cancelPolicy)));

            $nowDateTime = Carbon::now()->format('Y-m-d H:i');
            $cancelFeeData = $this->calc_cancel_policy_service->getCancelFee($cancelPolicy, $checkinDate, $nowDateTime, $bookingData['reservation']['accommodation_price'], $isFreeCancel);

            $this->reserve_session_service->forgetSessionByKey($this->confirm_session_key . '.cancel_info');
            $this->reserve_session_service->putCancelInfo($cancelFeeData['cancel_fee'], $cancelFeeData['is_free_cancel']);
            $html = View::make('user.booking.components.display_cancel', compact('cancelFeeData', 'paymentMethod'))->render();

            return response()->json(['res' => 'ok', 'html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました。']);
        }
    }

    public function ajaxGetRoomTypeDetail(Request $request)
    {
        $roomTypeToken = $request->get('room_type_token', '');
        try {
            $roomTypeInfo = $this->reserve_session_service->getOtherRoomTypeInfo($roomTypeToken);
            $roomTypeId = $roomTypeInfo['room_type_id'];
            $hotelRoomType = HotelRoomType::select('id', 'name', 'room_num', 'adult_num', 'child_num', 'room_size')
                ->with('hotelRoomTypeImages')->find($roomTypeId);
            $roomDetail = [
                'name' => $hotelRoomType->name,
                'room_num' => $hotelRoomType->room_num,
                'adult_num' => $hotelRoomType->adult_num,
                'child_num' => $hotelRoomType->child_num,
                'room_size' => $hotelRoomType->room_size,
                'images' => $hotelRoomType->hotelRoomTypeImages->sortBy('id')->map(function ($item) {
                    return $item->imageSrc();
                })->toArray(),
            ];

            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            $hotelId = $bookingData['base_info']['hotel_id'];
            $roomDetail['hard_items'] = $this->hard_item_service->getRoomHardItem($roomTypeId, $hotelId);

            $data = [
                'room_type_detail' => $roomDetail,
            ];
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error('予期せぬエラーが発生しました。', 500);
        }
    }
}
