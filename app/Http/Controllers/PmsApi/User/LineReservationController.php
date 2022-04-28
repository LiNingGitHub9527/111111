<?php

namespace App\Http\Controllers\PmsApi\User;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Plan;
use App\Models\Hotel;
use App\Models\CancelPolicy;
use App\Http\Requests\PmsApi\User\ReserveBaseInfoRequest;
use App\Http\Requests\PmsApi\User\SendSelectAdultNumRequest;
use App\Http\Requests\PmsApi\User\SendSelectChildNumRequest;
use App\Http\Requests\PmsApi\User\SendSelectPlanRequest;
use App\Http\Requests\PmsApi\User\SendSelectRoomTypeRequest;
use App\Http\Requests\PmsApi\User\PlanDetailRequest;
use App\Http\Requests\PmsApi\User\RoomTypeDetailRequest;

class LineReservationController extends ApiBaseController
{
    public function __construct()
    {
        $this->line_service = app()->make('LineReserveService');
        $this->browse_reserve_service = app()->make('BrowserReserveService');
        $this->form_service = app()->make('FormSearchService');
        $this->kids_policy_service = app()->make('KidsPolicyService');
        $this->convert_cancel_service = app()->make('ConvertCancelPolicyService');
        $this->reserve_service = app()->make('ReserveService');
        $this->calc_plan_service = app()->make('CalcPlanAmountService');
        $this->calc_form_service = app()->make('CalcFormAmountService');
        $this->temairazu_service = app()->make('TemairazuService');
        $this->session_service = app()->make('ReserveSessionService');
        $this->booking_session_key = 'booking';
    }

    // step0
    // 予約開始
    // lp_paramからclient_id, lp_id, hotel_idを取得して返す
    public function getReserveBaseInfo(ReserveBaseInfoRequest $request)
    {
        $lpParam = $request->get('url_param', '');
        try {
            $lp = $this->line_service->getLPfromParam($lpParam);
            $lpId = $lp->id;
            $clientId = $lp->client_id;
            $hotelId = $lp->hotel_id;
            $baseInfoParam = $this->line_service->convertBaseInfoData($lpId, $clientId, $hotelId);

            return $this->success($baseInfoParam);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    // step01
    // 大人人数の選択
    public function sendSelectAdultNum(SendSelectAdultNumRequest $request)
    {
        $lpParam = $request->get('url_param', '');
        try {
            $lp = $this->line_service->getFormFromLpParam($lpParam);
            $form = $this->form_service->findForm($lp['form_id']);
            $targetRoomTypeIds = $this->form_service->getFormRoomTypeIds($form, $lp['hotel_id']);
            $maxAdultNum = $this->line_service->getMaxAdultNum($targetRoomTypeIds);
            $kidsPolicies = $this->line_service->getKidsPolicies($lp['hotel_id']);
            $adultNumParam = $this->line_service->convertAdultNumData($maxAdultNum, $kidsPolicies);
            return $this->success($adultNumParam);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    // step02
    // 子供人数の選択
    public function sendSelectChildNum(SendSelectChildNumRequest $request)
    {
        $lpParam = $request->get('url_param', '');
        try {
            $lp = $this->line_service->getFormFromLpParam($lpParam);
            $form = $this->form_service->findForm($lp['form_id']);
            $targetRoomTypeIds = $this->form_service->getFormRoomTypeIds($form, $lp['hotel_id']);
            $maxChildNum = $this->line_service->getMaxChildtNum($targetRoomTypeIds);
            $kidsPolicies = $this->line_service->getKidsPolicies($lp['hotel_id']);
            $childNumParam = $this->line_service->convertChildNumData($maxChildNum, $kidsPolicies);
            return $this->success($childNumParam);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    // step03
    // 宿泊プランの選択
    public function sendSelectPlan(SendSelectPlanRequest $request)
    {
        $post = $request->all();
        $personNums = json_decode($post['person_nums']);
        try {
            $inOutDate = $this->line_service->convertInOutDate($post['checkin_date'], $post['checkout_date']);
            $nights = count($inOutDate);
            $lp = $this->line_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);

            $request->checkFormStatus($form);
            $request->checkFormDeadline($form, $post['checkin_date'], $post['checkout_date']);
            $request->checkPlanIds($planIds);
            
            $stayAblePlans = $this->line_service->getStayAblePlans($planIds, $nights, 1, $inOutDate);
            $searchPlanIds = collect($stayAblePlans)->pluck('id')->toArray();

            $roomTypeIds = $this->form_service->getFormRoomTypeIds($form, $lp['hotel_id']);
            $roomTypeCapas = $this->line_service->getRoomTypePersonCapacity($roomTypeIds);
            
            $request->checkRoomType($roomTypeCapas);

            $planRooms = [];
            foreach ($personNums as $nums) {
                $nums->ages = arrayObjectVars($nums->ages);

                $rcs = $this->line_service->convertRCClassPerRoomType($roomTypeCapas, $nums->adult_num, $nums->child_num, $nums->ages, $lp['hotel_id']);
                // !! 部屋タイプの子供定員数に設定されている人数を超える子供は大人として数える !!
                $stayAbleRoom = $this->line_service->getStayAbleRooms($roomTypeIds, $rcs, $inOutDate);
                $stayAbleRoom = $this->line_service->transformStayAbleRooms($stayAbleRoom);
                $stayAbleRoom = $this->line_service->checkNights($stayAbleRoom, $nights);

                //$stayAblePlansと$stayAbleRoomを合体させる
                $planRoom = $this->line_service->convertPlanRoomArr($stayAblePlans, $stayAbleRoom, $form->id, $post['room_num']);

                // plan_room_type_ratesのdate_sale_conditionが1のもの、plan_room_type_rates_per_classのamountが0のものを弾く
                $classPerson = $this->line_service->getPostClassPersonNums($rcs);
                $planRoomNG = $this->line_service->getNGPlanRoomRates($roomTypeIds, $searchPlanIds, $inOutDate, $classPerson);
                if (isset($planRoomNG['res']) && !$planRoomNG['res']) {
                    continue;
                }

                $planRoom = $this->line_service->rejectNGPlanRoom($planRoom, $planRoomNG, $rcs);

                // 取得したプランが、連泊の場合も全ての日程で宿泊可能な部屋が紐づいているかチェック
                $planRoom = $this->line_service->rejectUnachievedRoomNum($planRoom, $post['room_num']);

                // キャンセルポリシーをテキストに整形する
                $planRoom = $this->line_service->convertCancelPolicy($planRoom);

                $planRooms[] = $planRoom;
            }

            // 入力された複数の部屋で、全てに含まれるプランのみを残す（プランは1予約につき、１つしか選択できないため）
            $planRooms = $this->line_service->leaveAllClearPlans($planRooms, $post['room_num']);

            $planRooms = $this->line_service->unificatePlanRooms($planRooms);

            return $this->success($planRooms);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    // step04
    // 部屋タイプの選択
    public function sendSelectRoomType(SendSelectRoomTypeRequest $request)
    {
        $post = $request->all();
        $stayAbleRoomTypeIds = json_decode($post['stayable_room_type_ids']);
        $personNums = json_decode($post['person_nums']);
        try {
            $inOutDate = $this->line_service->convertInOutDate($post['checkin_date'], $post['checkout_date']);
            $targetPlan = Plan::find($post['plan_id']);
            $hotelId = $targetPlan->hotel_id;
            $lp = $this->line_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $ratePlanId = $targetPlan->is_new_plan ? $post['plan_id'] : $targetPlan->existing_plan_id;

            // !! 部屋タイプの子供定員数に設定されている人数を超える子供は大人として数える !!
            foreach ($personNums as $roomNum => $nums) {
                $age = $this->kids_policy_service->getKidsPolicy($nums->ages, $hotelId);
                $roomTypeCapa = $this->line_service->getRoomTypePersonCapacity($stayAbleRoomTypeIds[$roomNum]);

                // オブジェクトを配列に変換して渡す
                $numAges = arrayObjectVars($nums->ages);
                $roomTypeRC = $this->line_service->convertRCClassPerRoomType($roomTypeCapa, $nums->adult_num, $nums->child_num, $numAges, $lp['hotel_id']);

                $planRoomRate = $this->line_service->getPlanRoomRates($ratePlanId, $stayAbleRoomTypeIds[$roomNum], $inOutDate);
                if (!$targetPlan->is_new_plan) {
                    $planRoomRate = $this->calc_plan_service->calcPlanSettingAmount($planRoomRate, $targetPlan);
                }
                if (!empty($form)) {
                    $planRoomRate = $this->calc_form_service->calcFormSettingAmount($planRoomRate, $form, $targetPlan);
                }

                $planRoomRate = $this->line_service->convertPlanRoomRates($planRoomRate);
                $roomTypeRate = $this->line_service->getRCAmounts($roomTypeRC, $planRoomRate, $inOutDate, $age, $nums->child_num, $ratePlanId);
                // ※金額がマイナスの部屋タイプの日付を弾く処理
                $roomTypeRate = $this->line_service->rejectNegativeAmount($roomTypeRate);
                $stayAbleRoom = $this->line_service->getStayAbleRooms($stayAbleRoomTypeIds[$roomNum], $roomTypeRC, $inOutDate);
                $stayAbleRoom =  $this->line_service->makeDuplicateRoomUnique($stayAbleRoom);
                $stayAbleRoom = $this->line_service->rejectNonRateRoom($stayAbleRoom, $roomTypeRate);
                $stayAbleRoomBed = $this->line_service->getRoomTypeBeds($stayAbleRoomTypeIds[$roomNum]);
                $stayAbleRoomImage = $this->line_service->getRoomTypeImage($stayAbleRoomTypeIds[$roomNum]);
                $stayAbleRoomBed = $this->line_service->transformRoomTypeBedArr($stayAbleRoomBed);
                // 部屋タイプの配列と、部屋タイプごとのベッド,画像, 金額の配列を合体させる
                // かつ、各日にちの一部屋の料金の内訳(amount_breakdown)を算出しマージする
                $stayAbleRoom = $this->line_service->mergeRoomTypeArr($stayAbleRoom, $stayAbleRoomBed, $stayAbleRoomImage, $roomTypeRate);

                $stayAbleRooms[$roomNum] = $stayAbleRoom;
            }

            $hotel = Hotel::find($hotelId);
            $hotelInOutTime = $this->reserve_service->getStayInOutTime($hotel, $post['checkin_date'], $post['checkout_date']);
            $response['rooms'] = $stayAbleRooms;
            $response['hotel_in_out'] = $hotelInOutTime;

            return $this->success($response);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    //宿泊プランの詳細説明
    public function getPlanDetail(PlanDetailRequest $request)
    {
        $planId = $request->get('plan_id', '');

        try{
            $plan = Plan::find($planId);
            $policy = CancelPolicy::find($plan->cancel_policy_id);
            $cancelDesc = $this->convert_cancel_service->cancelConvert($policy->is_free_cancel, $policy->free_day, $policy->free_time, $policy->cancel_charge_rate);
            $noShowDesc = $this->convert_cancel_service->noShowConvert($policy->no_show_charge_rate);
            $payMethod = config('prepay.pay_type')[$plan->prepay];
            $planDetail = $this->line_service->convertPlanDetail($cancelDesc, $noShowDesc, $payMethod, $plan->name, $plan->description);

            return $this->success($planDetail);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    // 部屋タイプの詳細説明
    public function getRoomTypeDetail(RoomTypeDetailRequest $request)
    {
        $roomTypeId = $request->get('room_type_id', '');
        try {
            $roomType = $this->line_service->getRoomBedImage($roomTypeId);
            $roomType = $roomType->first();
            $roomType->images = $this->line_service->getImageFromPath($roomType->hotelRoomTypeImages);
            $roomType->beds = $roomType->hotelRoomTypeBeds;
            unset(
                $roomType->hotelRoomTypeImages,
                $roomType->hotelRoomTypeBeds,
            );

            foreach ($roomType->beds as &$bed) {
                $bed->bed_type = config('bed.bed_types')[$bed->bed_size];
                unset(
                    $bed->created_at,
                    $bed->updated_at
                );
            }

            return $this->success($roomType);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    // step05
    // CRM側のreservation_in_progressを取得し、情報入力画面をレンダリングする
    public function lineInputDisplay(Request $request)
    {
        $this->session_service->forgetSessionByKey($this->booking_session_key);
        $hash = $request->get('hash');
        $curlService = app()->make('ApiCallService');
        $bookingData = $curlService->getCurlResponseData(['token' => $hash]);
        $this->line_service->putBaseInfoByBookData($bookingData);
        $this->line_service->putSelectedRoomByBookData($bookingData);
        $this->line_service->putGuestInfoByBookingData($bookingData);

        // 情報入力
        return redirect()->route('user.booking_info_input');
    }
}