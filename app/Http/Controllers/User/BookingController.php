<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\Hotel;
use App\Models\HotelNote;
use App\Models\HotelRoomType;
use App\Models\CancelPolicy;
use App\Models\Reservation;
use App\Models\ReservationCancelPolicy;
use App\Http\Requests\User\RenderSearchPanelRequest;
use App\Http\Requests\User\StaySearchRequest;
use App\Http\Requests\User\ConfirmStayBookingRequest;
use App\Http\Requests\User\UpdateStayBookingRequest;
use App\Http\Requests\User\ReservePrePayRequest;
use DB;
use View;

class BookingController extends BookingBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->convert_cancel_service = app()->make('ConvertCancelPolicyService');
        $this->hotel_email_service = app()->make('HotelEmailService');
    }

    public function searchPanel(RenderSearchPanelRequest $request)
    {
        $lpParam = $request->get('url_param', '');

        try {
            $lp = $this->browse_reserve_service->getFormFromLpParam($lpParam);
            $res = $request->cancelPolicy();
            if (!$res['res']) {
                return redirect($res['url']);
            }

            if (empty($lp)) {
                $notReserve = 1;
                $attentionMessage = '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。';
                return view('user.booking.search_panel', compact('notReserve', 'attentionMessage'));
            }
            $form = $this->form_service->findForm($lp['form_id']);
            if (is_null($form) || $form->public_status == 0) {
                $notReserve = 1;
                $attentionMessage = '申し訳ありません。アクセスされたURLからは現在ご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。';
                return view('user.booking.search_panel', compact('notReserve', 'attentionMessage'));
            }

            $addSalePerioddate = $request->checkFormSalePeriod($form);
            if (!$addSalePerioddate['res']) {
                $notReserve = 1;
                $attentionMessage = $addSalePerioddate['message'];
                return view('user.booking.search_panel', compact('notReserve', 'attentionMessage'));
            }

            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);
            $stayAblePlans = $this->browse_reserve_service->getStayAblePlans($planIds, 0, 2);
            $isDayuse = $this->browse_reserve_service->checkIncludeStayType($stayAblePlans, 2);

            $stayPlans = $this->browse_reserve_service->getStayAblePlans($planIds, 0, null);
            if (empty($stayPlans)) {
                $notReserve = 1;
                $attentionMessage = '申し訳ありません。アクセスされたURLからは現在宿泊のご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。';
                return view('user.booking.search_panel', compact('notReserve', 'attentionMessage'));
            }

            $targetRoomTypeIds = $this->form_service->getFormRoomTypeIds($form, $lp['hotel_id']);
            $maxChildNum = $this->browse_reserve_service->getMaxChildtNum($targetRoomTypeIds);
            $maxAdultNum = $this->browse_reserve_service->getMaxAdultNum($targetRoomTypeIds);
            $kidsPolicies = $this->browse_reserve_service->getKidsPolicies($lp['hotel_id']);
            $kidsPolicies = $this->browse_reserve_service->sortKidspolicyByAge($kidsPolicies);
            $maxChildAge = $this->browse_reserve_service->getMaxChildAge($kidsPolicies);
            $nowYear = Carbon::now()->format('Y');
            $nowMonth = Carbon::now()->format('n');

            $title = '【宿泊】予約検索 | ' . $lp['title'];
            $currentPage = 1;

            $changeBookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);
            if (!empty($changeBookingData) && !empty($changeBookingData['reservation']) && !empty($changeBookingData['reservation']['stay_type']) && $changeBookingData['reservation']['stay_type'] == 2) {
                return redirect()->route('user.booking_search_panel', ['url_param' => $lpParam, 'dayuse' => 'true']);
            }

            $hotel = Hotel::find($form['hotel_id']);
            $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();

            $hideSwitch = false;
            if (!empty($changeBookingData)) {
                $hideSwitch = true;
                return view('user.booking.search_panel', compact('nowYear', 'nowMonth', 'maxChildNum', 'maxAdultNum', 'kidsPolicies', 'maxChildAge', 'lpParam', 'isDayuse', 'hideSwitch', 'title', 'currentPage', 'hotel', 'hotelNotes'));
            }

            return view('user.booking.search_panel', compact('nowYear', 'nowMonth', 'maxChildNum', 'maxAdultNum', 'kidsPolicies', 'maxChildAge', 'lpParam', 'isDayuse', 'title', 'currentPage', 'hotel', 'hotelNotes'));
        } catch (\Exception $e) {
            $title = '【宿泊】予約検索 | ' . $lp['title'];
            $notReserve = 1;
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return view('user.booking.search_panel', compact('notReserve', 'attentionMessage', 'title'));
        }
    }

    public function bookingSearch(StaySearchRequest $request)
    {
        $post = $request->all();
        $urlParam = $post['url_param'];
        try {
            $inOutDate = $this->browse_reserve_service->convertInOutDate(Carbon::parse($post['checkin_date'])->format('Y-m-d'), Carbon::parse($post['checkout_date'])->format('Y-m-d'));
            $nights = count($inOutDate);
            $lp = $this->browse_reserve_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $hotel = Hotel::find($form['hotel_id']);
            $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();
            // formに関する追加バリデーション
            $addValidate = $request->checkFormDeadline($form, $post['checkin_date'], $post['checkout_date']);
            if (!$addValidate['res']) {
                return response()->json(['res' => 'error', 'error' => $addValidate['message']]);
            }
            // 追加バリデーション ここまで

            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);
            $roomTypeIds = $this->form_service->getFormRoomTypeIds($form, $lp['hotel_id']);
            $roomTypeCapas = $this->browse_reserve_service->getRoomTypePersonCapacity($roomTypeIds);
            $stayAblePlans = $this->browse_reserve_service->getStayAblePlans($planIds, $nights, 1, $inOutDate);

            $searchPlanIds = collect($stayAblePlans)->pluck('id')->toArray();

            $ageNums = $this->browse_reserve_service->convertPostAgesPerRoom($post, $hotel->id);

            $planRooms = [];
            foreach ($ageNums as $roomNum => $age) {
                $adultNum = $age['adult_num'];
                $childSum = 0;
                if (!empty($age['child_num'])) {
                    $childSum = collect($age['child_num'])->sum('num');
                } else {
                    $age['child_num'] = [];
                }
                $rcs = $this->browse_reserve_service->convertRCClassPerRoomType($roomTypeCapas, $adultNum, $childSum, $age['child_num'], $lp['hotel_id']);

                // !! 部屋タイプの子供定員数に設定されている人数を超える子供は大人として数える !!
                $stayAbleRoom = $this->browse_reserve_service->getStayAbleRooms($roomTypeIds, $rcs, $inOutDate);
                $stayAbleRoom = $this->browse_reserve_service->transformStayAbleRooms($stayAbleRoom);
                $stayAbleRoom = $this->browse_reserve_service->checkNights($stayAbleRoom, $nights);

                //$stayAblePlansと$stayAbleRoomを合体させる
                $planRoom = $this->browse_reserve_service->convertPlanRoomArr($stayAblePlans, $stayAbleRoom, $form->id, $post['room_num']);

                // plan_room_type_ratesのdate_sale_conditionが1のもの、plan_room_type_rates_per_classのamountが0のものを弾く
                $classPerson = $this->browse_reserve_service->getPostClassPersonNums($rcs);
                $planRoomNG = $this->browse_reserve_service->getNGPlanRoomRates($roomTypeIds, $searchPlanIds, $inOutDate, $classPerson);

                if (isset($planRoomNG['res']) && !$planRoomNG['res']) {
                    continue;
                }
                $planRoom = $this->browse_reserve_service->rejectNGPlanRoom($planRoom, $planRoomNG, $rcs);

                // 取得したプランが、連泊の場合も全ての日程で宿泊可能な部屋が紐づいているかチェック
                $planRoom = $this->browse_reserve_service->rejectUnachievedRoomNum($planRoom, $post['room_num']);

                // キャンセルポリシーをテキストに整形する
                $planRoom = $this->browse_reserve_service->convertCancelPolicy($planRoom);

                $roomTypeRCs[$roomNum] = $rcs;
                $stayAbleRooms[$roomNum] = $stayAbleRoom;
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
            $planRooms = $this->reserve_session_service->putSearchParam($planRooms, $ageNums, $inOutDate, $urlParam, 1, $nights, $hotel->id, $planTokens);
            $merge_plan_id = function(string $k, array $v): array {
                return array_merge($v, ['id' => intval($k)]);
            };
            $plans = array_map(
                $merge_plan_id, array_keys($planRooms), array_values($planRooms));

            $title = '【宿泊】プラン・お部屋選択 | ' . $lp['title'];

            return response()->json(['res' => 'ok', 'searchData' => compact('plans', 'hotel', 'hotelNotes', 'ageNums', 'inOutDate', 'urlParam', 'title')]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }

    public function ajaxPlanDetail(Request $request)
    {
        try {
            $planToken = $request->get('plan_token');
            $urlParam = $this->reserve_session_service->getUrlParam();
            if (empty($urlParam)) {
                return response()->json(['res' => 'error', 'message' => 'ページの識別情報が見つかりませんでした。恐れ入りますが、再度検索してくださいませ。']);
            }
            $planSessionInfo = $this->reserve_session_service->getPlanInfo($planToken, 0);
            if (empty($planSessionInfo)) {
                return response()->json(['res' => 'error', 'message' => 'プランの識別情報が見つかりませんでした。恐れ入りますが、ページを再読み込みしてください。']);
            }
            $plan = Plan::with('cancelPolicy')
                    ->where('id', $planSessionInfo['plan_id'])
                    ->first();

            $sessionBaseInfo = $this->reserve_session_service->getBaseInfo();
            if (empty($plan->cover_image)) {
                $plan->cover_image = asset('static/common/images/no_image.png');;
            }
            if ($plan->is_meal) {
                $plan->meal_type_kana = $this->browse_reserve_service->convertMealTypes($plan->meal_types);
            }

            $checkinDate = $sessionBaseInfo['in_out_date'][0];
            $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow(NULL, $checkinDate, $plan->cancelPolicy);

            $plan->cancel_desc = $this->convert_cancel_service->cancelConvert($isFreeCancel, $plan->cancelPolicy->free_day, $plan->cancelPolicy->free_time, $plan->cancelPolicy->cancel_charge_rate);
            $plan->no_show_desc = $this->convert_cancel_service->noShowConvert($plan->cancelPolicy->no_show_charge_rate);

            $plan->plan_token = $planToken;

            return response()->json(['res' => 'ok', 'plan_detail' => $plan]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }

    public function ajaxGetPlanRoomTypes(Request $request)
    {
        $planToken = $request->get('plan_token');
        try {
            $sessionBaseInfo = $this->reserve_session_service->getBaseInfo();
            $roomCount = count($sessionBaseInfo['age_nums']);

            $planSessionInfos = [];
            $stayAbleRoomTypeIdsPerRoom = [];
            $roomTypeCapas = [];
            $roomTypeRCs = [];
            for ($roomNum=0; $roomNum <= $roomCount-1; $roomNum++) {
                $planSessionInfo = $this->reserve_session_service->getPlanInfo($planToken, $roomNum);
                $planSessionInfos[$roomNum] = $planSessionInfo;

                $stayAbleRoomTypeIds = $planSessionInfo['stayable_room_type_ids'];
                $stayAbleRoomTypeIdsPerRoom[$roomNum] = $stayAbleRoomTypeIds;

                $capas = $this->browse_reserve_service->getRoomTypePersonCapacity($stayAbleRoomTypeIds);
                $roomTypeCapas[$roomNum] = $capas;

                if ($roomNum == 0 ) {
                    $targetPlan = Plan::find($planSessionInfo['plan_id']);
                    $hotelId = $targetPlan->hotel_id;
                }
            }

            $ages = [];
            foreach ($sessionBaseInfo['age_nums'] as $roomNum => $age) {
                if (!empty($age['child_num'])) {
                    $age['child_num'] = json_decode(json_encode($age['child_num']));
                    $ageData = $this->kids_policy_service->getKidsPolicy($age['child_num'], $hotelId);
                    $ages[$roomNum] = $ageData;
                }
            }

            $urlParam = $this->reserve_session_service->getUrlParam();
            $lp = $this->browse_reserve_service->getFormFromLpParam($urlParam);
            $form = $this->form_service->findForm($lp['form_id']);

            foreach ($sessionBaseInfo['age_nums'] as $roomNum => $ageNums) {
                if (!empty($ageNums['child_num'])) {
                    $childSum = collect($ageNums['child_num'])->sum('num');
                } else {
                    $childSum = 0;
                    $ageNums['child_num'] = [];
                }
                $rc = $this->browse_reserve_service->convertRCClassPerRoomType($roomTypeCapas[$roomNum], $ageNums['adult_num'], $childSum, $ageNums['child_num'], $lp['hotel_id']);
                $roomTypeRCs[$roomNum] = $rc;
            }

            $ratePlanId = $targetPlan->is_new_plan ? $planSessionInfo['plan_id'] : $targetPlan->existing_plan_id;
            $planRoomRates = [];
            foreach ($stayAbleRoomTypeIdsPerRoom as $roomNum => $stayAbleRoomTypeIds) {
                $rates = $this->browse_reserve_service->getPlanRoomRates($ratePlanId, $stayAbleRoomTypeIds, $sessionBaseInfo['in_out_date']);
                if (empty($rates)) {
                    return response()->json(['res' => 'error', 'message' => '選択されたプランでは宿泊可能なお部屋が見つかりませんでした。誠に恐れ入りますが、条件を変えて検索してください。']);
                }
                $planRoomRates[$roomNum] = $rates;
            }

            // 宿泊する日付の中に料金が0で登録されている部屋タイプを削除する
            $rejectRoomTypeIds = $this->browse_reserve_service->get0PriceRooms($planRoomRates, $roomTypeRCs);
            $stayAbleRoomTypeIdsPerRoom = $this->browse_reserve_service->trim0PriceRoomTypes($stayAbleRoomTypeIdsPerRoom, $rejectRoomTypeIds);

            // 既存プランをもとに料金設定しているプランの場合、existing_plan_idのplanは、is_new_planが１の前提
            foreach ($planRoomRates as $roomNum => $planRoomRate) {
                if (!$targetPlan->is_new_plan) {
                    $planRoomRate = $this->calc_plan_service->calcPlanSettingAmount($planRoomRate, $targetPlan);
                    $planRoomRates[$roomNum] = $planRoomRate;
                }
                if (!empty($form)) {
                    $planRoomRate = $this->calc_form_service->calcFormSettingAmount($planRoomRate, $form, $targetPlan);
                    $planRoomRates[$roomNum] = $planRoomRate;
                }
                $planRoomRates[$roomNum] = $this->browse_reserve_service->convertPlanRoomRates($planRoomRate);
            }

            $roomTypeRates = [];
            foreach ($sessionBaseInfo['age_nums'] as $roomNum => $ageNums) {
                if (!empty($ageNums['child_num'])) {
                    $childSum = collect($ageNums['child_num'])->sum('num');
                } else {
                    $childSum = 0;
                }
                if (empty($ages[$roomNum])) {
                    $ages[$roomNum] = [];
                }
                $rates = $this->browse_reserve_service->getRCAmounts($roomTypeRCs[$roomNum], $planRoomRates[$roomNum], $sessionBaseInfo['in_out_date'], $ages[$roomNum], $childSum, $ratePlanId);

                // ※金額がマイナスの部屋タイプの日付を弾く処理
                $rates = $this->browse_reserve_service->rejectNegativeAmount($rates);
                if (!empty($rates)) {
                    $roomTypeRates[$roomNum] = $rates;
                }
            }


            if (empty($roomTypeRates)) return response()->json(['res' => 'error', 'message' => '選択されたプランでは宿泊可能なお部屋が見つかりませんでした。誠に恐れ入りますが、条件を変えて検索してください。']);

            foreach ($roomTypeRCs as $roomNum => $rc) {
                $stayAbleRoom = $this->makeStayAbleRooms($roomNum, $stayAbleRoomTypeIdsPerRoom, $rc, $sessionBaseInfo, $stayAbleRoomTypeIds, $roomTypeRates);
                if (empty($stayAbleRoom)) return response()->json(['res' => 'error', 'message' => '選択されたプランでは宿泊可能なお部屋が見つかりませんでした。誠に恐れ入りますが、条件を変えて検索してください。']);
                $stayAbleRooms[$roomNum] = $stayAbleRoom;
            }

            $ageNumsKana = $this->browse_reserve_service->convertAgeNumPerRoom($sessionBaseInfo['age_nums']);

            // ページをまたぐ、POSTデータをセッションに格納する & セッション上でroomTypeを識別するワンタイムトークンを$stayAbleRoomsに格納する
            // ブラウザ上でidを参照・書き換えさせないため
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.searched_rooms');
            $stayAbleRooms = $this->reserve_session_service->putRoomDetails($planSessionInfo['plan_id'], $stayAbleRooms);
            $pickValues = function(array $v): array {
                return array_values($v);
            };
            $stayAbleRooms = array_map($pickValues, $stayAbleRooms);

            return response()->json(['res' => 'ok', 'roomTypes' => compact('stayAbleRooms', 'ageNums', 'ageNumsKana', 'targetPlan', 'planToken')]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }

    public function ajaxGetRoomTypeDetail(Request $request)
    {
        $roomToken = $request->get('room_token');
        try {
            $planId = $this->reserve_session_service->getRoomPlanIdByToken($roomToken);
            $roomDetailInfo = $this->reserve_session_service->getRoomDetailByToken($roomToken);
            $roomNum = $roomDetailInfo['room_num'];
            $targetPlan = Plan::find($planId);
            $roomDetail = $roomDetailInfo['room_detail'];
            $hotelId = $targetPlan->hotel_id;
            if ($targetPlan->is_meal) {
                $targetPlan->meal_type_kana = $this->browse_reserve_service->convertMealTypes($targetPlan->meal_types);
            }
            if (!empty($roomDetail['beds'])) {
                $roomDetail['beds_kana'] = $this->browse_reserve_service->convertBedTypes($roomDetail['beds']);
            } else {
                $roomDetail['beds_kana'] = '';
            }
            $roomDetail['hard_items'] = $this->hard_item_service->getRoomHardItem($roomDetail['room_type_id'], $hotelId);

            return response()->json(['res' => 'ok', 'roomTypeDetail' => compact('targetPlan', 'roomDetail', 'roomNum', 'roomToken')]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }

    public function ajaxGetRoomSelected(Request $request)
    {
        $roomToken = $request->get('room_token');
        try {
            $planId = $this->reserve_session_service->getRoomPlanIdByToken($roomToken);
            $roomDetailInfo = $this->reserve_session_service->getRoomDetailByToken($roomToken);
            $roomDetail = $roomDetailInfo['room_detail'];
            $roomNum = $roomDetailInfo['room_num'];
            $isInStock = $this->browse_reserve_service->checkSelectedRoomStock($roomDetail['date_stock_nums'], 1, $roomDetail);
            $searchedRooms = $this->reserve_session_service->getSessionByKey($this->booking_session_key . '.searched_rooms');
            $hideRoomTokens = $this->browse_reserve_service->makeSwitchRoomTokens($searchedRooms, $roomNum, $roomDetail['room_type_id']);
            $roomTokens = $hideRoomTokens;
            $roomTokens[] = $roomToken;
            $this->reserve_session_service->reduceSessionStockNum($roomDetail, 1, $roomTokens);

            $targetPlan = Plan::find($planId);
            $hotel = Hotel::find($targetPlan->hotel_id);
            $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();
            if (!empty($roomDetail['beds'])) {
                $roomDetail['bed_sum'] = $this->browse_reserve_service->calcBedSum($roomDetail['beds']);
            } else {
                $roomDetail['bed_sum'] = 0;
            }

            // セッション格納済みの、同じ部屋数目のselected_roomをforgetする
            $this->reserve_session_service->forgetSelectedRoomSessionByRoomNum($roomNum);

            // ページをまたぐ、POSTデータをセッションに格納する
            // ブラウザ上でidを参照・書き換えさせないため
            $isAllSelected = $this->reserve_session_service->putSelectedRoom($planId, $roomToken, $roomNum, $roomDetail);

            return response()->json(['res' => 'ok', 'selectedRoomData' => compact('targetPlan', 'hotel', 'hotelNotes', 'roomDetail', 'roomNum', 'roomToken'), 'is_all_selected' => $isAllSelected, 'isInStock' => $isInStock, 'hideRoomTokens' => $hideRoomTokens]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }

    public function selectedRoomCancel(Request $request)
    {
        $roomToken = $request->get('room_token');
        $roomNum = $request->get('room_num');
        try {
            $roomDetailInfo = $this->reserve_session_service->getRoomDetailByToken($roomToken);
            $roomDetail = $roomDetailInfo['room_detail'];
            $isInStock = $this->browse_reserve_service->checkCancelRoomStock($roomDetail['date_stock_nums'], 1);
            $showRoomTokens = [];
            $searchedRooms = $this->reserve_session_service->getSessionByKey($this->booking_session_key . '.searched_rooms');
            $showRoomTokens = $this->browse_reserve_service->makeSwitchRoomTokens($searchedRooms, $roomNum, $roomDetail['room_type_id']);
            $roomTokens = $showRoomTokens;
            $roomTokens[] = $roomToken;
            $this->reserve_session_service->increaseSessionStockNum($roomDetail, 1, $roomTokens);

            $selectedRooms = $this->reserve_session_service->getSessionByKey($this->booking_session_key . '.selected_rooms');
            $selectedRoomNums = $this->browse_reserve_service->makeSelectedRoomNums($selectedRooms);

            $this->reserve_session_service->forgetSelectedRoom($roomToken, $roomNum);
            return response()->json(['res' => 'ok', 'showRoomTokens' => $showRoomTokens, 'room_num' => $roomNum, 'selectedNums' => $selectedRoomNums]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました']);
        }
    }


    public function inputBookingInfo(Request $request)
    {
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
            $bookingData = $this->browse_reserve_service->makeStayBookingDateKana($bookingData, $hotel);
            $roomFees = $this->browse_reserve_service->transformStayFeePerRoom($bookingData['selected_rooms']);
            $roomAmount['sum'] = $this->browse_reserve_service->calcSumAmount($roomFees);
            $roomAmount = $this->browse_reserve_service->calcTax($roomAmount, $hotel->is_tax);
            $checkinScTimes = $this->browse_reserve_service->calcCheckinScTimes($hotel, $bookingData['base_info']['in_out_date'][0]);
            $planId = $bookingData['selected_rooms']['plan_id'];
            $plan = Plan::find($planId);

            $changeBookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key . '.change_info');
            $this->reserve_session_service->putBookingFees($roomFees, $roomAmount);

            $title = '予約情報入力';
            $currentPage = 3;

            $reservation = [];
            if (!empty($changeBookingData)) {
                $reservation = Reservation::find($changeBookingData['reservation_id']);
                $reservation->special_request = str_replace(config('temairazu.remarks.special_price'), '', $reservation->special_request);
                $trimTaxTx = strstr($reservation->special_request, config('temairazu.remarks.tax'));
                $reservation->special_request = str_replace($trimTaxTx, '', $reservation->special_request);
                return view('user.booking.update_input', compact('bookingData', 'hotel', 'roomFees', 'roomAmount', 'checkinScTimes', 'plan', 'reservation', 'title', 'currentPage'));
            }
            session()->put('booking.payment_status', 0);

            $lineGuestInfo = session()->get('booking.guest_from_line', []);
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

            return view('user.booking.input', compact('bookingData', 'hotel', 'roomFees', 'roomAmount', 'checkinScTimes', 'plan', 'title', 'currentPage', 'lineGuestInfo'));
        } catch (\Exception $e) {
            $this->reserve_session_service->forgetSessionByKey($this->booking_session_key . '.searched_rooms');
            $attentionMessage = '申し訳ありません。予期せぬエラーが発生しました。お時間をおいて再度お試しくださいませ。';
            return redirect( config('app.url') . '/page/search_panel?url_param=' . $bookingData['base_info']['url_param'] )->with(['error' => $attentionMessage]);
        }
    }

    public function saveStayReservationData(ConfirmStayBookingRequest $request)
    {
        $post = $request->all();

        $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
        $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
        $post['address'] = $post['address1'] . ' ' . $post['address2'];
        if ($hotel->is_tax == 1) {
            $post = $this->addPriceRemarks($bookingData['room_amount']['tax'], $post);
        }
        $post = $this->addFormRemarks($bookingData['base_info']['url_param'], $post);
        // $post = $this->addPriceRemarks($bookingData['room_amount']['tax'], $post);
        $post = $this->browse_reserve_service->makeSavePostData($post, $bookingData, $hotel);
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

        // reservationsレコードを保存
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
                    $stripeService->manageFullRefund($reserveId, $post['stripe_payment_id'], $bookingData['room_amount']['sum'], $refundData);
                }
                Reservation::where('id', $reserveId)->delete();
                return back()->withInput()->with(['error' => $result['message']]);
            }
            DB::commit();
        } catch (\Exception $e ) {
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

        // 処理完了


        $userShowUrl = $this->sendConfirmMail($verifyToken, $saveReserveData, $post, $hotel, $bookingData, $planRooms, $reserveId, 1);
        $title = '予約完了';
        return view('user.booking.complete', compact('userShowUrl', 'title'));
    }

    public function updateStayReservationData(UpdateStayBookingRequest $request)
    {
        $post = $request->all();
        $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
        $changeBookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);
        $reserveId = $changeBookingData['change_info']['reservation_id'];
        $reservation = Reservation::find($reserveId);
        $planId = $bookingData['selected_rooms']['plan_id'];
        $plan = Plan::find($planId);
        $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
        $planRooms = $this->browse_reserve_service->makePlanRoomsFromSessionData($bookingData);

        // 予約直前に料金が0で更新されていた場合は予約不可
        $res = $this->checkIs0Price($planRooms);
        if (!$res['res']) return back()->withInput()->with(['error' => $res['message']]);

        // 既存予約と同じ部屋タイプの枝番号をplanRoomsにそれぞれ割り当てる
        $planAndBranch = $this->assignReservedBranch($planRooms, $reserveId);
        $planRooms = $planAndBranch['planRooms'];
        // $planRoomsに必要なデータを加えて整形
        $res = $this->transformPlanRoom($planRooms, $post, $hotel);
        $planRooms = $res['planRooms'];
        $post = $res['post'];

        // reservationsレコードを更新
        if ($hotel->is_tax == 1) {
            $post = $this->addPriceRemarks($bookingData['room_amount']['tax'], $post);
        }
        $post = $this->addFormRemarks($bookingData['base_info']['url_param'], $post);
        // $post = $this->addPriceRemarks($bookingData['room_amount']['tax'], $post);
        $post = $this->browse_reserve_service->makeSavePostData($post, $bookingData, $hotel);
        $post['payment_method'] = $reservation->payment_method;
        $saveReserveData = $this->makePostSaveData($post, $reservation->verify_token);
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
            // $isBaseInfoChange = $this->compareBaseInfo($reservation->toArray(), $saveReserveData);
            $this->reserve_service->updateStayReserveData($reserveId, $saveReserveData);

            // reservation_cancel_policyを更新
            $this->updateReserveCanPoli($bookingData['selected_rooms']['plan_id'], $reserveId, $hotel->id);

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
                $this->reserveIncreaseRoomStock($reservation->id, $reservation->hotel);
                Reservation::where('id', $reservation->id)->update(['reservation_update_status' => 0]);
                return back()->withInput()->with(['error' => $result['message']]);
            }
            // 更新処理完了
            DB::commit();
        } catch (\Exception $e ) {
            DB::rollback();
            if (!empty($saveReserveData['stripe_payment_id'])) {
                $refundData = [];
                $stripeService = app()->make('StripeService');
                $stripeService->manageFullRefund($reserveId, $saveReserveData['stripe_payment_id'], $bookingData['room_amount']['sum'], $refundData);
            }
            $this->reserveIncreaseRoomStock($reservation->id, $reservation->hotel);
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

        $userShowUrl = $this->sendChangeMail($reservation->verify_token, $saveReserveData, $post, $hotel, $bookingData, $planRooms, $reserveId, 1, $reservation->payment_method, $reservation);

        $title = '予約変更完了';
        return view('user.booking.complete', compact('userShowUrl', 'title'));

    }

    public function makePostSaveData($post, $verifyToken)
    {
        $saveData = [];
        $saveData['adult_num'] = $post['adult_num'];
        $saveData['child_num'] = $post['child_num'];
        $saveData['room_num'] = $post['room_num'];
        $saveData['address'] = $post['address'];
        $saveData['reservation_code'] = $post['reservation_code'];
        $saveData['client_id'] = $post['client_id'];
        $saveData['hotel_id'] = $post['hotel_id'];
        $saveData['name'] =  $post['first_name'] . ' ' . $post['last_name'];
        $saveData['name_kana'] = $post['first_name_kana'] . ' ' . $post['last_name_kana'];
        $saveData['last_name'] = $post['last_name'];
        $saveData['first_name'] = $post['first_name'];
        $saveData['last_name_kana'] = $post['last_name_kana'];
        $saveData['first_name_kana'] = $post['first_name_kana'];
        $saveData['last_name_kana'] = $post['last_name_kana'];
        $saveData['first_name_kana'] = $post['first_name_kana'];
        $saveData['checkin_start'] = $post['checkin_start'];
        $saveData['checkin_end'] = $post['checkin_end'];
        $saveData['checkout_end'] = $post['checkout_end'];
        $saveData['checkin_time'] = $post['checkin_date'] . ' ' . $post['checkin_time'];
        $saveData['checkout_time'] = $post['checkout_end'];
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
        $saveData['special_request'] = $post['special_request'];
        $saveData['reservation_date'] = Carbon::now()->format('Y-m-d H:i:s');
        $saveData['created_at'] = now();

        return $saveData;
    }

    public function ajaxPrePay(ReservePrePayRequest $request)
    {
        $post = $request->except('_token');
        return response()->json( $this->prePay($post) );
    }

    public function bookingShow($token)
    {
        try {
            $reservation = $this->reserve_change_service->findReservationByToken($token);
            if (empty($reservation)) {
                $attentionMessage = 'ご予約が見つかりませんでした';
                $notReserve = 1;
                return view('user.booking.show', compact('attentionMessage', 'notReserve'));
            }
            if ($reservation['reservation_status'] != 0) {
                $attentionMessage = '既にキャンセル済みの予約です';
                $notReserve = 1;
                return view('user.booking.show', compact('attentionMessage', 'notReserve'));
            }
            $reserveCanPoli = $reservation->reservationCancelPolicy;
            $checkinTime = Carbon::parse($reservation->checkin_time);
            $checkinDate = $checkinTime->format('Y-m-d');
            $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow(NULL, $checkinDate, json_decode(json_encode($reserveCanPoli)));
            $cancelDesc = $this->convert_cancel_service->cancelConvert($isFreeCancel, $reserveCanPoli['free_day'], $reserveCanPoli['free_time'], $reservation->reservationCancelPolicy->cancel_charge_rate);
            $noShowDesc = $this->convert_cancel_service->noShowConvert($reservation->reservationCancelPolicy->no_show_charge_rate);

            $reservationPlans = $this->reserve_service->getReservePlansByReserveId($reservation['id']);
            // reservation_branchesから取得したplansのリレーションを、plansを主に整形する
            $reservationPlans = $this->reserve_service->convertBranchPlanRltn($reservationPlans);
            $reservationPlans = $this->reserve_service->rejectDelAndCanPlan($reservationPlans);
            $reservationPlans = $this->reserve_change_service->summarizeSameRooms($reservationPlans);
            $reservation['rooms'] = $reservationPlans;

            $planId = collect($reservationPlans)->first()['plan_id'];
            $plan = Plan::find($planId);
            $roomTypeIds = collect($reservationPlans)->pluck('room_type_id')->unique()->toArray();
            $roomTypes = HotelRoomType::select('id', 'name')->whereIn('id', $roomTypeIds)->get()->keyBy('id')->toArray();

            $hotel = Hotel::find($plan->hotel_id);
            $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();

            $this->reserve_session_service->forgetSessionByKey($this->confirm_session_key);
            $this->reserve_session_service->putBookingConfirmInfo($this->confirm_session_key, $plan, $reservation);
            $cancelable = false;

            if (Carbon::now()->lt($checkinTime->tomorrow())) {
                $cancelable = true;
            }
            $title = '予約詳細';

            return view('user.booking.show', compact('reservation', 'plan', 'roomTypes', 'hotel', 'hotelNotes', 'isFreeCancel', 'title', 'cancelDesc', 'noShowDesc', 'cancelable'));
        } catch (\Exception $e) {
            $title = '予約詳細';
            $attentionMessage = '予期せぬエラーが発生しました';
            $notReserve = 1;
            return view('user.booking.show', compact('attentionMessage', 'notReserve', 'title'));
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

    public function cancelConfirm(Request $request)
    {
        $stripeService = app()->make('StripeService');

        DB::beginTransaction();
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);

            // セッションが置かれてからの時間の経過をチェック
            $diffMinute = Carbon::now()->diffInMinutes($bookingData['cancel_info']['session_time']);
            if ($diffMinute >= 15) {
                return response()->json(['res' => 'error', 'message' => '一定時間操作がありませんでした。画面を再読み込みして再度お試しください。']);
            }

            $reserve = Reservation::find($bookingData['reservation']['id']);
            $hotel = Hotel::find($reserve->hotel_id);

            $cancelCommission = 0;
            if ($bookingData['reservation']['payment_method'] === 1) {
                // 返金処理を行う
                $result = $this->cancelRefund($reserve, $bookingData);
                if (!$result['res']) {
                    return response()->json(['res' => 'error', 'message' => $result['message']]);
                }
                $cancelCommission = $result['commission'];
            }

            // キャンセル分の在庫データを復活させる
            $this->reserveIncreaseRoomStock($reserve->id, $hotel);

            $reservationBranches = $reserve->reservationBranches->where('reservation_status', 0);

            // 予約をキャンセルでステータスを更新する
            $nowDateTime = Carbon::now()->format('Y-m-d H:i:s');
            $res = $this->reserve_service->confirmCancel($reserve, $nowDateTime, $bookingData['cancel_info']['cancel_fee'], $cancelCommission);

            $this->hotel_email_service->send($reserve->hotel_id, $reserve->id, 0, $reservationBranches);

            // 予約キャンセルを手間いらずに通知する
            $hotel = Hotel::find($reserve->hotel_id);
            $this->temairazu_service->sendReservationNotification($hotel->client_id, $reserve->id);

            DB::commit();
            return response()->json(['res' => 'ok']);
        } catch (\Exception $e) {
            ddlog('=====================cancel error=======================');
            ddlog('reservation and stripe info');
            ddlog('hotelId: ' . $hotel->id . '/ ' . $hotel->name. ' / reservationId: ' . $reserve->id . ' / stripe_payment_id: ' . $reserve->stripe_payment_id);
            ddlog($e);
            ddlog('=====================error end=======================');
            DB::rollback();
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました。']);
        }
    }

    public function ajaxChangeReserve(Request $request)
    {
        try{
            $bookingData = $this->reserve_session_service->getSessionByKey($this->confirm_session_key);
            $reservation = Reservation::where('id', $bookingData['reservation']['id'])->first();
            if ($reservation['reservation_status'] != 0) {
                return response()->json(['res' => 'error', 'message' => '既にキャンセル済みの予約です']);
            }
            $this->reserve_session_service->forgetSessionByKey($this->confirm_session_key . '.change_info');
            $this->reserve_session_service->putChangeInfo($bookingData['reservation']['id'], 1);
            $urlParam = $bookingData['reservation']['lp_url_param'];
            if ($bookingData['reservation']['stay_type'] == 1) {
                $searchUrl = route('user.booking_search_panel') . '?url_param=' . $urlParam;
            } else {
                $searchUrl = route('user.booking_search_panel', ['url_param' => $urlParam, 'dayuse' => 'true']);
            }
            return response()->json(['res' => 'ok', 'url' => $searchUrl]);
        } catch (\Exception $e) {
            return response()->json(['res' => 'error', 'message' => '予期せぬエラーが発生しました。']);
        }
    }

    public function planPreview(Request $request)
    {
        $data = json_decode($request->get('data'));
        $planData = get_object_vars($data);

        $hotel = Hotel::find($planData['hotel_id']);
        $hotelNotes = HotelNote::select('title', 'content')->where('hotel_id', $hotel->id)->get()->toArray();

        $planRoom = [];
        $planRoom['plan_name'] = $planData['name'];
        $planRoom['status'] = 'preview';
        if (empty($planData['cover_image'])) {
            $planRoom['cover_image'] = asset('static/common/images/no_image.png');
        } else {
            $planRoom['cover_image'] = $planData['cover_image'];
        }

        $plan = $data;
        $cancel = CancelPolicy::where('id', $plan->cancel_policy_id)->first();
        if (empty($plan->cover_image)) {
            $plan->cover_image = asset('static/common/images/no_image.png');;
        }
        if ($plan->is_meal) {
            $plan->meal_type_kana = $this->browse_reserve_service->convertMealTypes($plan->meal_types);
        }
        $plan->cancel_desc = $this->convert_cancel_service->cancelConvert($cancel->is_free_cancel, $cancel->free_day, $cancel->free_time, $cancel->cancel_charge_rate);
        $plan->no_show_desc = $this->convert_cancel_service->noShowConvert($cancel->no_show_charge_rate);
        $planRoom['planDetailHtml'] = View::make('user.booking.components.show_plan_detail', compact('plan'))->render();

        $planRooms[] = $planRoom;

        return view('user.booking.search', compact('planRooms', 'hotel', 'hotelNotes'));
    }

}