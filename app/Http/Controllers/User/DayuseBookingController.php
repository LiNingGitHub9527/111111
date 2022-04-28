<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\Hotel;
use App\Models\HotelNote;
use App\Models\Reservation;
use App\Http\Requests\User\ConfirmStayBookingRequest;
use App\Http\Requests\User\UpdateStayBookingRequest;
use App\Http\Requests\User\Dayuse\DayuseSearchRequest;
use DB;

class DayuseBookingController extends BookingBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->dayuse_service = app()->make('LineDayuseReserveService');
        $this->hotel_email_service = app()->make('HotelEmailService');
    }

    public function ajaxGetSelectCheckinTime(Request $request)
    {
        $post = $request->all();
        if (empty($post['checkin_date'])) {
            return response()->json(['res' => 'error', 'message' => 'ご利用日を入力してください']);
        }
        try {
            $lp = $this->browse_reserve_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);
            $stayAblePlans = $this->browse_reserve_service->getStayAblePlans($planIds, 0, 2);
            $checkinMinMax = $this->dayuse_service->getMinMaxCheckinTime($stayAblePlans, $post['checkin_date']);
            $checkinMinMax = $this->dayuse_service->makeTimeMinMax($checkinMinMax);

            return response()->json(['res' => 'ok', 'min_max' => $checkinMinMax]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }

    public function ajaxGetSelectStaytime(Request $request)
    {
        $post = $request->all();
        if (empty($post['checkin_date']) || empty($post['checkin_date_time'])) {
            return response()->json(['res' => 'error', 'message' => 'ご利用日・もしくはチェックイン予定時間を選択してください']);
        }
        try {
            $lp = $this->browse_reserve_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);
            $stayAblePlans = $this->browse_reserve_service->getStayAblePlans($planIds, 0, 2);
            $minStayTime = $this->dayuse_service->getMinStayTime($stayAblePlans);
            $checkinMax = $this->dayuse_service->getMaxLastCheckoutTime($stayAblePlans, $post['checkin_date']);

            $stayTimeMinMax = $this->dayuse_service->makeTimeChoice($post['checkin_date_time'], $checkinMax, $minStayTime);

            return response()->json(['res' => 'ok', 'stay_time' => $stayTimeMinMax]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }

    public function bookingSearch(DayuseSearchRequest $request)
    {
        $post = $request->all();
        $urlParam = $post['url_param'];
        $inOutDate = [Carbon::parse($post['checkin_date'])->format('Y-m-d')];
        $checkoutDateTime = Carbon::parse($post['checkin_date_time'])->addHours($post['stay_time'])->format('Y/m/d H:i');
        try {
            $lp = $this->browse_reserve_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            // formに関する追加バリデーション
            $addValidate = $request->checkFormDeadline($form, $post['checkin_date'], $post['checkin_date']);
            if (!$addValidate['res']) {
                return response()->json(['res' => 'error', 'error' => $addValidate['message']]);
            }
            // 追加バリデーション ここまで

            $hotel = Hotel::find($form['hotel_id']);
            $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();

            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);
            $roomTypeIds = $this->form_service->getFormRoomTypeIds($form, $lp['hotel_id']);
            $roomTypeCapas = $this->browse_reserve_service->getRoomTypePersonCapacity($roomTypeIds);

            $ageNums = $this->browse_reserve_service->convertPostAgesPerRoom($post, $hotel->id);

            foreach ($ageNums as $roomNum => $age) {
                $adultNum = $age['adult_num'];
                $childNum = 0;
                if (!empty($age['child_num'])) {
                    $childNum = collect($age['child_num'])->sum('num');
                } else {
                    $age['child_num'] = [];
                }
                $rcs = $this->browse_reserve_service->convertRCClassPerRoomType($roomTypeCapas, $adultNum, $childNum, $age['child_num'], $lp['hotel_id']);
                $roomTypeRCs[$roomNum] = $rcs;
            }

            $stayAblePlans = $this->browse_reserve_service->getStayAblePlans($planIds, 0, 2, $inOutDate);
            // プランに設定された時間を満たすものだけを残す
            $post['checkout_date_time'] = $this->browse_reserve_service->calcCheckoutDateTime($post['checkin_date_time'], $post['stay_time']);
            $stayAblePlans = $this->dayuse_service->checkPlanTime($stayAblePlans, $post['stay_time'], $post['checkin_date_time'], $post['checkout_date_time'], $post['checkin_date']);
            $searchPlanIds = collect($stayAblePlans)->pluck('id')->toArray();

            // !! 部屋タイプの子供定員数に設定されている人数を超える子供は大人として数える !!
            $stayAbleRooms = [];
            $classPersons = [];
            $planRoomsNGs = [];
            foreach ($roomTypeRCs as $roomNum => $rcs) {
                $stayAbleRoom = $this->browse_reserve_service->getStayAbleRooms($roomTypeIds, $rcs, [$post['checkin_date']]);
                $stayAbleRoom = $this->browse_reserve_service->transformStayAbleRooms($stayAbleRoom);
                $stayAbleRooms[$roomNum] = $stayAbleRoom;
                $classPerson = $this->browse_reserve_service->getPostClassPersonNums($rcs);
                $classPersons[$roomNum] = $classPerson;
                // plan_room_type_ratesのdate_sale_conditionが1のもの、plan_room_type_rates_per_classのamountが0のものを弾く
                $planRoomNG = $this->browse_reserve_service->getNGPlanRoomRates($roomTypeIds, $searchPlanIds, $inOutDate, $classPerson);
                if (isset($planRoomNGs['res']) && !$planRoomNGs['res']) {
                    return response()->json(['res' => 'error', 'message' => '検索された条件を満たすお部屋が見つかりませんでした。条件を変えてお探しください。']);
                }
                $planRoomNGs[$roomNum] = $planRoomNG;
            }

            //$stayAblePlansと$stayAbleRoomを合体させる
            $planRooms = [];
            foreach ($stayAbleRooms as $roomNum => $stayAbleRoom) {
                $planRoom = $this->browse_reserve_service->convertPlanRoomArr($stayAblePlans, $stayAbleRoom, $form->id, $post['room_num']);
                $planRoom = $this->browse_reserve_service->rejectNGPlanRoom($planRoom, $planRoomNGs, $roomTypeRCs[$roomNum]);
                $planRoom = $this->browse_reserve_service->rejectUnachievedRoomNum($planRoom, $post['room_num']);
                // キャンセルポリシーをテキストに整形する
                $planRoom = $this->browse_reserve_service->convertCancelPolicy($planRoom);
                $planRooms[$roomNum] = $planRoom;
            }

            // 部屋数ごとに全て共通するプランのみを残す
            $planRooms = $this->browse_reserve_service->leaveAllClearPlans($planRooms, $post['room_num']);
            if (empty($planRooms)) {
                return response()->json(['res' => 'error', 'message' => '検索された条件を満たすお部屋が見つかりませんでした。条件を変えてお探しください。']);
            }
            // ページをまたぐ、POSTデータをセッションに格納する & セッション上でプランを識別するワンタイムトークンを$planRoomsに格納する
            // ブラウザ上でidを参照・書き換えさせないため
            $planTokens = $this->reserve_session_service->makePlanTokens($planRooms);
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key);
            $planRooms = $this->reserve_session_service->putSearchParam($planRooms, $ageNums, $inOutDate, $urlParam, 2, 0, $hotel->id, $planTokens, $post['checkin_date_time'], $checkoutDateTime);
            $merge_plan_id = function(string $k, array $v): array {
                return array_merge($v, ['id' => intval($k)]);
            };
            $plans = array_map(
                $merge_plan_id, array_keys($planRooms), array_values($planRooms));

            $title = '【日帰り】プラン・お部屋選択 | ' . $lp['title'];

            return response()->json(['res' => 'ok', 'searchData' => compact('plans', 'hotel', 'hotelNotes', 'ageNums', 'inOutDate', 'urlParam', 'title')]);
        } catch (\Exception $e) {
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return response()->json(['res' => 'error', 'message' => $attentionMessage]);
        }
    }

    public function saveDayuseReservationData(ConfirmStayBookingRequest $request)
    {
        $post = $request->all();

        $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
        $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
        $selectedRooms = $bookingData['selected_rooms'];
        $plan = Plan::find($selectedRooms['plan_id']);
        $post['plan_id'] = $selectedRooms['plan_id'];
        unset($selectedRooms['plan_id']);

        // 追加バリデーション
        $checkinStart = $bookingData['base_info']['in_out_date'][0] . ' ' . $plan->checkin_start_time;
        $lastDate = $this->dayuse_service->getChangeDate($plan->checkin_start_time, $plan->last_checkin_time, $bookingData['base_info']['in_out_date'][0]);
        $inTime = Carbon::parse($bookingData['base_info']['checkin_date_time']);
        $endTime = Carbon::parse($bookingData['base_info']['checkout_date_time']);
        $post['stay_time'] = $endTime->diffInHours($inTime);
        $addValidate = $request->checkMinStayTime($plan->min_stay_time, $post['stay_time']);
        if (!$addValidate['res']) {
            return back()->withInput()->with(['error' => $addValidate['message']]);
        }
        $lastCheckoutDateTime = Carbon::parse($lastDate)->hour($plan->last_checkout_time)->format('Y-m-d H:i');
        $addValidate = $request->checkLastCheckoutTime($bookingData['base_info']['checkout_date_time'], $lastCheckoutDateTime);
        if (!$addValidate['res']) {
            return back()->withInput()->with(['error' => $addValidate['message']]);
        }
        $lastCheckin = Carbon::parse($lastDate)->hour($plan->last_checkin_time)->format('Y-m-d H:i');
        $addValidate = $request->checkCheckinTime($checkinStart, $lastCheckin, $bookingData['base_info']['checkin_date_time']);
        if (!$addValidate['res']) {
            return back()->withInput()->with(['error' => $addValidate['message']]);
        }
        // 追加バリデーション終了

        $post['room_num'] = count($selectedRooms);
        $post['checkin_start'] = $checkinStart;
        $post['checkin_end'] = $lastCheckin;
        $post['checkout_end'] = $lastCheckoutDateTime;
        $post = $this->browse_reserve_service->makeDayuseSavePostData($post, $bookingData);
        $post['address'] = $post['address1'] . ' ' . $post['address2'];

        if ($hotel->is_tax == 1) {
            $post = $this->addPriceRemarks($bookingData['room_amount']['tax'], $post);
        }
        $post = $this->addFormRemarks($bookingData['base_info']['url_param'], $post);

        $planRooms = $this->browse_reserve_service->makePlanRoomsFromSessionData($bookingData);
        // 予約直前に料金が0で更新されていた場合は予約不可
        $res = $this->checkIs0Price($planRooms);
        if (!$res['res']) {
            return back()->withInput()->with(['error' => $res['message']]);
        }

        // 部屋タイプが複数ある場合に、reservation_plansテーブル保存用に枝番号を付与する
        $planRooms = $this->reserve_service->calcReserveBranchNum($planRooms);
        // $planRoomsに必要なデータを加えて整形
        $res = $this->transformPlanRoom($planRooms, $post);
        $planRooms = $res['planRooms'];
        $post = $res['post'];

        // reservationレコードを保存
        $verifyToken = uniqid() . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 8 );
        $saveReserveData = $this->makePostSaveData($post, $verifyToken);
        $reserveId = $this->reserve_service->insertStayReserveData($saveReserveData);
        if ($post['payment_method'] == 1) {
            $prepay = $this->prePay($post, $reserveId);
            if ($prepay['res'] != 'ok') {
                Reservation::where('id', $reserveId)->delete();
                return back()->withInput()->with(['error' => $prepay['message']]);
            }
        }
        DB::beginTransaction();
        try {
            Reservation::where('id', $reserveId)->update([
                'payment_method' => $post['payment_method'] ?? null,
                'stripe_payment_id' => $post['stripe_payment_id'] ?? null,
                'stripe_customer_id' => $post['stripe_customer_id'] ?? null,
                'payment_status' => $post['payment_status'] ?? 0
            ]);

            // reservation_cancel_policyレコードを保存
            $this->saveReserveCanPoli($bookingData['selected_rooms']['plan_id'], $reserveId, $hotel->id);

            // reservation_plans, reservation_kids_policies, reservation_branch_numレコードを保存
            $planRoomPerBranch = $this->makeBranches($planRooms, $reserveId, $hotel, $bookingData['selected_rooms']['plan_id'], $post['accommodation_price_detail']);
            $branchNumIdMap = $this->browse_reserve_service->saveBranchData($planRoomPerBranch, $planRooms);
            $result = $this->browse_reserve_service->savePlanRooms($planRooms, $reserveId, $hotel, $branchNumIdMap);
            if (!$result['res']) {
                DB::rollback();
                if (!empty($bookingData['stripe_payment_id'])) {
                    $refundData = [];
                    $stripeService = app()->make('StripeService');
                    $stripeService->manageFullRefund($reserveId, $bookingData['stripe_payment_id'], $bookingData['room_amount']['sum'], $refundData);
                }
                Reservation::where('id', $reserveId)->delete();
                return back()->withInput()->with(['error' => $result['message']]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            if (!empty($post['stripe_payment_id'])) {
                $refundData = [];
                $stripeService = app()->make('StripeService');
                $stripeService->manageFullRefund($reserveId, $post['stripe_payment_id'], $bookingData['room_amount']['sum'], $refundData);
            }
            Reservation::where('id', $reserveId)->delete();
            return back()->withInput()->with(['error' => '予期せぬエラーが発生しました。']);
        }

        // 手間いらずに通知
        $this->temairazu_service->sendReservationNotification($hotel->client_id, $reserveId);

        $this->hotel_email_service->send($hotel->id, $reserveId, 1);
        // 予約データの保存に成功し、無料キャンセル期間外の場合はオーソリをチャージする
        // if (!empty($bookingData['payment_method']) && $bookingData['payment_method'] == 1 && !$bookingData['is_free_cancel']) {
        //     $stripeService = app()->make('StripeService');
        //     $stripeService->chargeAuthoryById($bookingData['stripe_payment_id'], $bookingData['room_amount']['sum']);
        //     Reservation::find($reserveId)->update(['payment_status' => config('prepay.payment_status.pay')]);
        // }

        // 予約情報を格納したセッションを消去
        $this->reserve_session_service->forgetSessionByKey($this->booking_session_key);

        $userShowUrl = $this->sendConfirmMail($verifyToken, $saveReserveData, $post, $hotel, $bookingData, $planRooms, $reserveId, 2);
        $title = '予約完了';
        return view('user.booking.complete', compact('userShowUrl', 'title'));
    }

    public function updateDayuseReservationData(UpdateStayBookingRequest $request)
    {
        $post = $request->all();

        $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
        $changeBookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);
        $reserveId = $changeBookingData['change_info']['reservation_id'];
        $reservation = Reservation::find($reserveId);
        $planId = $bookingData['selected_rooms']['plan_id'];
        $plan = Plan::find($planId);
        $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
        $selectedRooms = $bookingData['selected_rooms'];
        $plan = Plan::find($selectedRooms['plan_id']);
        $post['plan_id'] = $selectedRooms['plan_id'];
        unset($selectedRooms['plan_id']);

        // 追加バリデーション
        $checkinStart = $bookingData['base_info']['in_out_date'][0] . ' ' . $plan->checkin_start_time;
        $lastDate = $this->dayuse_service->getChangeDate($plan->checkin_start_time, $plan->last_checkin_time, $bookingData['base_info']['in_out_date'][0]);
        $inTime = Carbon::parse($bookingData['base_info']['checkin_date_time']);
        $endTime = Carbon::parse($bookingData['base_info']['checkout_date_time']);
        $post['stay_time'] = $endTime->diffInHours($inTime);
        $addValidate = $request->checkMinStayTime($plan->min_stay_time, $post['stay_time']);
        if (!$addValidate['res']) {
            return back()->withInput()->with(['error' => $addValidate['message']]);
        }
        $lastCheckoutDateTime = Carbon::parse($lastDate)->hour($plan->last_checkout_time)->format('Y-m-d H:i');
        $addValidate = $request->checkLastCheckoutTime($bookingData['base_info']['checkout_date_time'], $lastCheckoutDateTime);
        if (!$addValidate['res']) {
            return back()->withInput()->with(['error' => $addValidate['message']]);
        }
        $lastCheckin = Carbon::parse($lastDate)->hour($plan->last_checkin_time)->format('Y-m-d H:i');
        $addValidate = $request->checkCheckinTime($checkinStart, $lastCheckin, $bookingData['base_info']['checkin_date_time']);
        if (!$addValidate['res']) {
            return back()->withInput()->with(['error' => $addValidate['message']]);
        }
        // 追加バリデーション終了

        $post['room_num'] = count($selectedRooms);
        $post['checkin_start'] = $checkinStart;
        $post['checkin_end'] = $lastCheckin;
        $post['checkout_end'] = $lastCheckoutDateTime;
        $post = $this->browse_reserve_service->makeDayuseSavePostData($post, $bookingData);
        $verifyToken = $reservation->verify_token;

        $planRooms = $this->browse_reserve_service->makePlanRoomsFromSessionData($bookingData);

        // 予約直前に料金が0で更新されていた場合は予約不可
        $res = $this->checkIs0Price($planRooms);
        if (!$res['res']) {
            return back()->withInput()->with(['error' => $res['message']]);
        }

        // 既存予約と同じ部屋タイプの枝番号をplanRoomsにそれぞれ割り当てる
        $planAndBranch = $this->assignReservedBranch($planRooms, $reserveId);
        $planRooms = $planAndBranch['planRooms'];
        // $planRoomsに必要なデータを加えて整形
        $res = $this->transformPlanRoom($planRooms, $post, $hotel);
        $planRooms = $res['planRooms'];
        $post = $res['post'];

        if ($hotel->is_tax == 1) {
            $post = $this->addPriceRemarks($bookingData['room_amount']['tax'], $post);
        }
        $post = $this->addFormRemarks($bookingData['base_info']['url_param'], $post);
        // reservationsレコードを更新
        $saveReserveData = $this->makePostSaveData($post, $verifyToken);
        $saveReserveData = $this->convertUpdateReserveData($saveReserveData);
        // 決済情報を更新
        if ($plan->prepay != 1) {
            if ($reservation->payment_method == 1) {
                $reservation->update(['reservation_update_status' => 2]);
                $result = $this->updatePrePay($reservation, $bookingData, $post);
                if (!$result['res']){
                    Reservation::where('id', $reservation->id)->update(['reservation_update_status' => 0]);
                    return back()->withInput()->with(['error' => $result['message']]);
                }
                $saveReserveData['stripe_payment_id'] = $result['payment_id'];
                $saveReserveData['payment_status'] = $result['payment_status'];
                $saveReserveData['reservation_update_status'] = 1;
            }
        } else {
            $saveReserveData['payment_method'] = 0;
            $post['payment_method'] = 0;
        }
        DB::beginTransaction();
        try {
            $this->reserve_service->updateStayReserveData($reserveId, $saveReserveData);

            // reservation_cancel_policyを更新
            $this->updateReserveCanPoli($bookingData['selected_rooms']['plan_id'], $reserveId, $hotel->id);

            // 在庫を元に戻す
            $res = $this->reserveIncreaseRoomStock($reserveId, $hotel);
            if (!$res['res']) {
                DB::rollback();
                return back()->withInput()->with(['error' => '予期せぬエラーが発生しました。']);
            }

            // 変更のある枝番号を特定する ↓
            $branchData = $this->makeBranches($planRooms, $reserveId, $hotel, $bookingData['selected_rooms']['plan_id'], $post['accommodation_price_detail']);
            $insertPlanRooms = $this->browse_reserve_service->makeInsertPlanRooms($planRooms, $reserveId);
            $reservedBranchData = $planAndBranch['branchData'];
            $branchChangeMap = $this->compareBranchData($reservedBranchData, $branchData, $insertPlanRooms);
            // 2つのテーブルのレコードを保存処理 ↓
            $result = $this->updateReserveBranchPlan($planRooms, $reserveId, $hotel, $branchData, $branchChangeMap, $reservation->reservation_date);

            if (!$result['res']) {
                DB::rollback();
                if (!empty($saveReserveData['stripe_payment_id'])) {
                    $refundData = [];
                    $stripeService = app()->make('StripeService');
                    $stripeService->manageFullRefund($reserveId, $saveReserveData['stripe_payment_id'], $bookingData['room_amount']['sum'], $refundData);
                }
                Reservation::where('id', $reservation->id)->update(['reservation_update_status' => 0]);
                return back()->withInput()->with(['error' => $result['message'] ]);
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
            Reservation::where('id', $reservation->id)->update(['reservation_update_status' => 0]);
            return back()->withInput()->with(['error' => '予期せぬエラーが発生しました。']);
        }

        $this->cancelPrepay($reservation);

        // 手間いらずに通知
        $this->temairazu_service->sendReservationNotification($hotel->client_id, $reserveId);

        $this->hotel_email_service->send($hotel->id, $reserveId, 2);
        // 予約情報を格納したセッションをリセット
        $this->reserve_session_service->forgetSessionByKey($this->booking_session_key);
        $this->reserve_session_service->forgetSessionByKey($this->confirm_session_key);

        $userShowUrl = $this->sendChangeMail($reservation->verify_token, $saveReserveData, $post, $hotel, $bookingData, $planRooms, $reserveId, 2, $reservation->payment_method, $reservation);
        $title = '予約変更完了';
        return view('user.booking.complete', compact('userShowUrl', 'title'));

    }

    public function makePostSaveData($post, $verifyToken)
    {
        $saveData = [];
        $saveData['stay_type'] = 2;
        $saveData['lp_url_param'] = $post['lp_url_param'];
        $saveData['room_num'] = $post['room_num'];
        $saveData['address'] = $post['address'];
        $saveData['adult_num'] = $post['adult_num'];
        $saveData['child_num'] = $post['child_num'];
        $saveData['reservation_code'] = $post['reservation_code'];
        $saveData['client_id'] = $post['client_id'];
        $saveData['hotel_id'] = $post['hotel_id'];
        $saveData['name'] = $post['first_name'] . ' ' . $post['last_name'];
        $saveData['name_kana'] = $post['first_name_kana'] . ' ' . $post['last_name_kana'];
        $saveData['last_name_kana'] = $post['last_name_kana'];
        $saveData['first_name_kana'] = $post['first_name_kana'];
        $saveData['last_name'] = $post['last_name'];
        $saveData['first_name'] = $post['first_name'];
        $saveData['checkin_start'] = $post['checkin_start'];
        $saveData['checkin_end'] = $post['checkin_end'];
        $saveData['checkout_end'] = $post['checkout_end'];
        $saveData['checkin_time'] = $post['checkin_date_time'];
        $saveData['checkout_time'] = $post['checkout_date_time'];
        $saveData['email'] = $post['email'];
        $saveData['tel'] = $post['tel'];
        // $saveData['payment_method'] = $post['payment_method'];
        $saveData['accommodation_price'] = $post['accommodation_price'];
        $saveData['commission_rate'] = $post['commission_rate'];
        $saveData['commission_price'] = $post['commission_price'];
        $saveData['payment_commission_rate'] = $post['payment_commission_rate'];
        $saveData['payment_commission_price'] = $post['payment_commission_price'];
        $saveData['reservation_status'] = $post['reservation_status'];
        $saveData['verify_token'] = $verifyToken;
        $saveData['reservation_date'] = Carbon::now()->format('Y-m-d H:i:s');
        $saveData['special_request'] = $post['special_request'] ?? '';
        $saveData['created_at'] = now();

        return $saveData;
    }
}