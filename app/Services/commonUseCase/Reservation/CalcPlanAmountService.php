<?php
namespace App\Services\commonUseCase\Reservation;

use Carbon\Carbon;
use DB;
use App\Models\Plan;

class CalcPlanAmountService
{

    public function __construct()
    {

    }

    public function calcPlanSettingAmount($planRoomRates, $plan)
    {
        // calculate_method
        // calculate_num
        // up_or_down
        // ※0円以下になるときにどうするか？
        $planRoomRates = collect($planRoomRates)
                         ->transform(function($rate) use($plan){
                             $classAmount = $this->calcYen($rate['class_amount'], $plan->up_or_down, $plan->calculate_num, $plan->calculate_method);
                             $rate['class_amount'] = $classAmount;
                             return $rate;
                         })
                         ->toArray();

        return $planRoomRates; 
    }

    public function calcYen(int $amount, int $upOrDown, int $calcNum, int $method)
    {
        if ($method == 0) {
            $calcNum = ceil($amount * $this->convertFew($calcNum));
        }

        if ($upOrDown == 1) {
            return $amount += $calcNum;
        }

        if ($upOrDown == 2) {
            return $amount -= $calcNum;
        }

        return $amount;
    }

    public function convertFew($calcNum)
    {
        return $calcNum * 0.01;
    }
} 