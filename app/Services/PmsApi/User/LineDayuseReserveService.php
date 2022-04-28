<?php
namespace App\Services\PmsApi\User;

use App\Services\commonUseCase\Reservation\ReserveSearchService;

use Carbon\Carbon;
use DB;

// PMSのLINE APIで予約完結するためのビジネスロジック
class LineDayuseReserveService extends ReserveSearchService
{

    public function __construct()
    {

    }

    # step01
    # 最早のチェックイン受け付け開始時間と、最遅のチェックイン受け付け終了時間を取得
    public function getMinMaxCheckinTime($plans, $checkinDate)
    {
        $timeArr = collect($plans)
                   ->transform(function($plan) use($checkinDate){
                       $checkinStartTime = Carbon::parse($checkinDate)->hour($plan['checkin_start_time'])->format('H:i');
                       $lastCheckinTime = Carbon::parse($checkinDate)->hour($plan['last_checkin_time'])->format('H:i');

                       $lastDate = $this->getChangeDate($checkinStartTime, $lastCheckinTime, $checkinDate);
                       $startDateTime = $checkinDate . ' ' . $checkinStartTime;
                       $lastDateTime =  $lastDate . ' ' . $lastCheckinTime;
                       $res = [
                           'checkin_start_time' => $startDateTime,
                           'last_checkin_time' => $lastDateTime,
                       ];

                       return $res;
                   })
                   ->toArray();

        $min = collect($timeArr)->pluck('checkin_start_time')->min();
        $max = collect($timeArr)->pluck('last_checkin_time')->max();
        $res['min'] = $min;
        $res['max'] = $max;

        return $res;
    }

    public function makeTimeMinMax($minMax)
    {
        $choiceArr = [];
        $minStamp = Carbon::parse($minMax['min']);
        $maxStamp = Carbon::parse($minMax['max']);

        $time = $minStamp;
        while($time <= $maxStamp) {
            $currentTime = $time->format('Y/m/d H:i');
            $choiceArr[] = $currentTime;
            $time = $time->addHours(1);
        }
        
        return $choiceArr;
    }

    public function makeTimeChoice($postCheckinDateTime, $checkinMax, $minStayTime)
    {
        $choiceArr = [];
        $minStamp = Carbon::parse($postCheckinDateTime);
        $maxStamp = Carbon::parse($checkinMax);

        $time = $minStamp;
        $currentStayTime = $minStayTime;
        $i = 0;
        while($time <= $maxStamp) {
            $choiceArr[$i] = $currentStayTime;
            $time = $time->addHours(1);
            $currentStayTime++;
            $i++;
        }

        return $choiceArr;
    }

    # step02
    # 最短の最低滞在時間を取得
    public function getMinStayTime($plans)
    {
        return collect($plans)->pluck('min_stay_time')->min();
    }

    public function getMaxLastCheckoutTime($plans, $checkinDate)
    {
        $max = collect($plans)
                   ->transform(function($plan) use($checkinDate){
                       $useDate = $this->getChangeDate($plan['checkin_start_time'], $plan['last_checkin_time'], $checkinDate);
                       $lastCheckinTime = $useDate . ' ' . $plan['last_checkin_time'] . ':00';

                       return $lastCheckinTime;
                   })
                   ->max();

        return $max;
    }

    # step03
    # 渡されたプランが、入力された時間で滞在可能な設定かどうかチェックする
    public function calcCheckoutDateTime($stayTime, $checkinTime, $checkinDate)
    {
        $checkinDateTime = $checkinDate . ' ' . $checkinTime;
        $checkoutTime = Carbon::parse($checkinDateTime)->addHours($stayTime)->format('H:i');

        return $checkoutTime; 
    }

    public function checkPlanTime($plans, $stayTime, $checkinDateTime, $checkoutDateTime, $checkinDate)
    {
        $plans = collect($plans)
                 ->reject(function($plan) use($stayTime, $checkinDateTime, $checkoutDateTime, $checkinDate) {
                     // 最低滞在時間を満たしているかどうか
                     $check = $this->checkMinStayTime($plan['min_stay_time'], $stayTime);
                     if (!$check) {
                         return !$check;
                     }

                     // チェックイン時間が、チェックイン開始時間〜最終チェックイン受け付け時間の間か
                     $checkinStart = Carbon::parse($checkinDate)->hour($plan['checkin_start_time'])->format('Y-m-d H:i');
                     $lastDate = $this->getChangeDate($plan['checkin_start_time'], $plan['last_checkin_time'], $checkinDate);
                     $lastCheckin = Carbon::parse($lastDate)->hour($plan['last_checkin_time'])->format('Y-m-d H:i');
                     $check = $this->checkCheckinTime($checkinStart, $lastCheckin, $checkinDateTime);
                     if (!$check) {
                        return !$check;
                     }

                     // チェックアウト時間が最終チェックアウト時間を超えていないか
                     $lastCheckoutDate = $this->getChangeDate($plan['checkin_start_time'], $plan['last_checkin_time'], $checkinDate);
                     $lastCheckoutDateTime = Carbon::parse($lastCheckoutDate)->hour($plan['last_checkout_time'])->format('Y-m-d H:i');
                     $check = $this->checkLastCheckoutTime($checkoutDateTime, $lastCheckoutDateTime);
                     if (!$check) {
                        return !$check;
                     }
                 })
                 ->toArray();

        return $plans;
    }

    // 最低滞在時間を満たしているかどうか
    public function checkMinStayTime($planMinStayTime, $postStayTime)
    {
        if ($planMinStayTime > $postStayTime) {
            return false;
        } else {
            return true;
        }
    }

    // チェックイン時間が、チェックイン開始時間〜最終チェックイン受け付け時間の間か
    public function checkCheckinTime($checkinStart, $lastCheckin, $checkinDateTime)
    {
        if (strtotime($checkinStart) > strtotime($checkinDateTime) || strtotime($lastCheckin) < strtotime($checkinDateTime)) {
            return false;
        } else {
            return true;
        }
    }

    // チェックアウト時間が最終チェックアウト時間を超えていないか
    public function checkLastCheckoutTime($postCheckoutDateTime, $planLastCheckout)
    {
        if (strtotime($postCheckoutDateTime) > strtotime($planLastCheckout)) {
            return false;
        } else {
            return true;
        }
    }

    // プランに登録されたチェックイン開始時間が最終チェックイン時間より早い時間の場合は、最終チェックイン時間の日付を１日進めて返す
    public function getChangeDate($checkinStart, $lastCheckinTime, $checkinDate)
    {
        $checkinStartTime = $checkinDate . ' ' . $checkinStart . ':00';
        $checkinEndTime = $checkinDate . ' ' . $lastCheckinTime . ':00';

        $checkinStartC = Carbon::parse($checkinStartTime);
        $checkinLastC = Carbon::parse($checkinEndTime);
        if ($checkinStartC > $checkinLastC) {
            $useDate = Carbon::parse($checkinDate)->modify('+1 day')->format('Y-m-d');
       } else {
           $useDate = $checkinDate;
       }

       return $useDate;
    }

}