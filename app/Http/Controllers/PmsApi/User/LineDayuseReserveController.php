<?php

namespace App\Http\Controllers\PmsApi\User;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use App\Models\Plan;
use App\Models\Hotel;
use App\Models\CancelPolicy;
use App\Models\HotelRoomType;
use App\Jobs\Mail\ReserveConfirmJob;
use App\Http\Requests\PmsApi\User\Dayuse\ReserveBaseInfoRequest;
use App\Http\Requests\PmsApi\User\Dayuse\SendSelectAdultNumRequest;
use App\Http\Requests\PmsApi\User\Dayuse\SendSelectChildNumRequest;
use App\Http\Requests\PmsApi\User\Dayuse\SendSelectPlanRequest;
use App\Http\Requests\PmsApi\User\Dayuse\SendSelectRoomTypeRequest;
use App\Http\Requests\PmsApi\User\Dayuse\SendSelectCheckinTimeRequest;
use App\Http\Requests\PmsApi\User\Dayuse\SendSelectStayTimeRequest;
use App\Http\Requests\PmsApi\User\Dayuse\saveReserveRequest;
use DB;

class LineDayuseReserveController extends ApiBaseController
{
    public function __construct()
    {
        $this->line_service = app()->make('LineReserveService');
        $this->form_service = app()->make('FormSearchService');
        $this->kids_policy_service = app()->make('KidsPolicyService');
        $this->convert_cancel_service = app()->make('ConvertCancelPolicyService');
        $this->reserve_service = app()->make('ReserveService');
        $this->line_dayuse_service = app()->make('LineDayuseReserveService');
        $this->calc_plan_service = app()->make('CalcPlanAmountService');
        $this->calc_form_service = app()->make('CalcFormAmountService');
        $this->temairazu_service = app()->make('TemairazuService');
    }

    # step00
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

    # step01 チェックイン時間送信
    # 最早のチェックイン受け付け開始時間と、最遅のチェックイン受け付け終了時間を取得
    public function sendSelectCheckinTime(SendSelectCheckinTimeRequest $request)
    {
        $post = $request->all();
        try {
            $lp = $this->line_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);
            $stayAblePlans = $this->line_service->getStayAblePlans($planIds, 0, 2);
            $checkinMinMax = $this->line_dayuse_service->getMinMaxCheckinTime($stayAblePlans, $post['checkin_date']);
            $checkinMinMax = $this->line_dayuse_service->makeTimeMinMax($checkinMinMax);

            return $this->success($checkinMinMax);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    # step02 滞在時間送信
    # 最短の最低滞在時間を取得
    public function sendSelectStayTime(SendSelectStayTimeRequest $request)
    {
        $post = $request->all();
        try {
            $lp = $this->line_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);
            $stayAblePlans = $this->line_service->getStayAblePlans($planIds, 0, 2);
            $minStayTime = $this->line_dayuse_service->getMinStayTime($stayAblePlans);
            $checkinMax = $this->line_dayuse_service->getMaxLastCheckoutTime($stayAblePlans, $post['checkin_date']);

            $stayTimeMinMax = $this->line_dayuse_service->makeTimeChoice($post['checkin_date_time'], $checkinMax, $minStayTime);

            return $this->success($stayTimeMinMax);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }

    # step03 大人人数送信
    # 大人の人数とキッスポリシーの年齢を取得して送信
    public function sendSelectAdultNum(SendSelectAdultNumRequest $request)
    {
        $post = $request->all();
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

    # step04
    # 全てのキッズポリシーの年齢を取得して送信
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

    # step05
    # 宿泊可能な部屋が紐づくプランを取得して返す
    public function sendSelectPlan(SendSelectPlanRequest $request)
    {
        $post = $request->all();
        $personNums = json_decode($post['person_nums']);
        try {
            $lp = $this->line_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);
            $request->checkFormDeadline($form, $post['checkin_date'], $post['checkin_date']);
            if ($form->public_status == 0) {
                return $this->error('申し訳ありません。こちらのメッセージでは現在ご予約を受け付けておりません。別のメッセージ、または予約ページからご予約くださいませ。', 400);
            }
            
            $planIds = $this->form_service->getFormPlanIds($form, $lp['hotel_id']);

            $stayAblePlans = $this->line_service->getStayAblePlans($planIds, 0, 2, [$post['checkin_date']]);
            // プランに設定された時間を満たすものだけを残す
            $stayAblePlans = $this->line_dayuse_service->checkPlanTime($stayAblePlans, $post['stay_time'], $post['checkin_date_time'], $post['checkout_date_time'], $post['checkin_date']);
            $searchPlanIds = collect($stayAblePlans)->pluck('id')->toArray();

            $roomTypeIds = $this->form_service->getFormRoomTypeIds($form, $lp['hotel_id']);
            $roomTypeCapas = $this->line_service->getRoomTypePersonCapacity($roomTypeIds);

            foreach ($personNums as $nums) {
                $nums->ages = arrayObjectVars($nums->ages);

                $rcs = $this->line_service->convertRCClassPerRoomType($roomTypeCapas, $nums->adult_num, $nums->child_num, $nums->ages, $lp['hotel_id']);
                // !! 部屋タイプの子供定員数に設定されている人数を超える子供は大人として数える !!
                $stayAbleRoom = $this->line_service->getStayAbleRooms($roomTypeIds, $rcs, [$post['checkin_date']]);
                $stayAbleRoom = $this->line_service->transformStayAbleRooms($stayAbleRoom);

                //$stayAblePlansと$stayAbleRoomを合体させる
                $planRoom = $this->line_service->convertPlanRoomArr($stayAblePlans, $stayAbleRoom, $form->id, $post['room_num']);

                // plan_room_type_ratesのdate_sale_conditionが1のもの、plan_room_type_rates_per_classのamountが0のものを弾く
                $classPerson = $this->line_service->getPostClassPersonNums($rcs);
                $planRoomNG = $this->line_service->getNGPlanRoomRates($roomTypeIds, $searchPlanIds, [$post['checkin_date']], $classPerson);
                // if (isset($planRoomNG['res']) && !$planRoomNG['res']) {
                //     continue;
                // }
                $planRoom = $this->line_service->rejectNGPlanRoom($planRoom, $planRoomNG, $rcs);

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

    # step06
    # プランに紐づく部屋タイプの料金をそれぞれ算出して返す
    public function sendSelectRoomType(SendSelectRoomTypeRequest $request)
    {
        $post = $request->all();
        $stayAbleRoomTypeIds = json_decode($post['stayable_room_type_ids']);
        $personNums = json_decode($post['person_nums']);
        try {
            $inOutDate = [$post['checkin_date']];
            $targetPlan = Plan::find($post['plan_id']);
            $hotelId = $targetPlan->hotel_id;
            $lp = $this->line_service->getFormFromLpParam($post['url_param']);
            $form = $this->form_service->findForm($lp['form_id']);

            $ratePlanId = $targetPlan->is_new_plan ? $post['plan_id'] : $targetPlan->existing_plan_id;

            // !! 部屋タイプの子供定員数に設定されている人数を超える子供は大人として数える !!
            foreach ($personNums as $roomNum => $nums) {
                $age = $this->kids_policy_service->getKidsPolicy($nums->ages, $hotelId);
                $roomTypeCapa = $this->line_service->getRoomTypePersonCapacity($stayAbleRoomTypeIds[$roomNum]);
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
                // ※金額がマイナスの部屋タイプの日付を弾く処理を追加
                $roomTypeRate = $this->line_service->rejectNegativeAmount($roomTypeRate);
                $stayAbleRoom = $this->line_service->getStayAbleRooms($stayAbleRoomTypeIds[$roomNum], $roomTypeRC, $inOutDate);
                $stayAbleRoom =  $this->line_service->makeDuplicateRoomUnique($stayAbleRoom);

                $stayAbleRoomBed = $this->line_service->getRoomTypeBeds($stayAbleRoomTypeIds[$roomNum]);
                $stayAbleRoomImage = $this->line_service->getRoomTypeImage($stayAbleRoomTypeIds[$roomNum]);
                $stayAbleRoomBed = $this->line_service->transformRoomTypeBedArr($stayAbleRoomBed);
                // 部屋タイプの配列と、部屋タイプごとのベッド,画像, 金額の配列を合体させる
                // かつ、各日にちの一部屋の料金の内訳(amount_breakdown)を算出しマージする
                $stayAbleRoom = $this->line_service->mergeRoomTypeArr($stayAbleRoom, $stayAbleRoomBed, $stayAbleRoomImage, $roomTypeRate);

                $roomTypeCapas[$roomNum] = $roomTypeCapa;
                $planRoomRates[$roomNum] = $planRoomRate;
                $stayAbleRooms[$roomNum] = $stayAbleRoom;
            }

            return $this->success($stayAbleRooms);
        } catch (\Exception $e) {
            return $this->error('unexpected error', 400);
        }
    }
}

