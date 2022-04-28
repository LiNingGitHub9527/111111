<?php
namespace App\Services\commonUseCase\Reservation;

use Carbon\Carbon;
use DB;
use App\Models\Plan;

class CalcFormAmountService
{

    public function __construct()
    {

    }

    public function calcFormSettingAmount($planRoomRates, $form, $plan)
    {
        // 特別価格を設定しないフォームの場合は早期リターン
        if ($form->is_special_price == 0) {
            return $planRoomRates;
        }

        // ①全ての部屋タイプの金額を手動入力(is_hand_inputが1)
        if ($form->is_hand_input == 1 && $form->is_room_type == 1) {
            $planRoomRates = $this->calcHandInput($planRoomRates, $form->hand_input_room_prices);
            return $planRoomRates;
        } elseif ($form->is_hand_input == 1 && $form->is_room_type == 0) {
            $planRoomRates = $this->calcAllRoomPrice($planRoomRates, $form->all_room_type_price);
            return $planRoomRates;
        }

        // ②選択した宿泊プランの金額を一括登録(is_all_planが1)
        if ($form->is_all_plan == 1) {
            $planRoomRates = $this->calcAllPlan($planRoomRates, $form->all_plan_price);
            return $planRoomRates; 
        }

        // ③宿泊プランそれぞれの金額を入力(is_hand_input, is_all_planが共に0)
        if ($form->is_hand_input == 0 && $form->is_hand_input == 0 && $form->is_plan == 1) {
            $planRoomRates = $this->calcPerPlan($planRoomRates, $form->special_plan_prices, $plan);
            return $planRoomRates; 
        } elseif ($form->is_hand_input == 0 && $form->is_hand_input == 0 && $form->is_plan == 0) {
            $planRoomRates = $this->calcPerPlan($planRoomRates, $form->all_special_plan_prices, $plan);
            return $planRoomRates;
        }
    }

    // ①全ての部屋タイプの金額を手動入力(is_hand_inputが1)
    public function calcHandInput($planRoomRates, $handInputPrices)
    {
        $handInputPrices = $this->transformKeyHandInputs($handInputPrices);
        $planRoomRates = collect($planRoomRates)
                         ->transform(function($rate) use($handInputPrices){
                             if (!empty($handInputPrices[$rate['room_type_id']]['price'])) {
                                $handAmount = $handInputPrices[$rate['room_type_id']]['price'];
                                $rate['class_amount'] = $handAmount;
                             }
                             
                             return $rate;
                         })
                         ->toArray();

        return $planRoomRates;
    }

    public function calcAllRoomPrice($planRoomRates, $roomTypePrices)
    {
        $planRoomRates = collect($planRoomRates)
                         ->transform(function($rate) use($roomTypePrices){
                            $rate['class_amount'] = $roomTypePrices['num'];
                             
                             return $rate;
                         })
                         ->toArray();

        return $planRoomRates;
    }

    // ②選択した宿泊プランの金額を一括登録(is_all_planが1)
    public function calcAllPlan($planRoomRates, $allPlanPrices)
    {
        $planRoomRates = collect($planRoomRates)
                         ->transform(function($rate) use($allPlanPrices){
                                $calcYen = $allPlanPrices['num'];
                                if ($allPlanPrices['unit'] == 0) {
                                    $calcYen = $this->calcYen($rate['class_amount'], $allPlanPrices['num']);
                                }

                                if ($allPlanPrices['up_off'] == 1) {
                                    $rate['class_amount'] += $calcYen;
                                    return $rate;
                                }

                                if ($allPlanPrices['up_off'] == 2) {
                                    $rate['class_amount'] -= $calcYen;
                                    return $rate;
                                }

                                return $rate;
                         })
                         ->toArray();

        return $planRoomRates;
    }

    // ③宿泊プランそれぞれの金額を入力(is_hand_input, is_all_planが共に0)
    public function calcPerPlan($planRoomRates, $specialPlanPrices, $plan)
    {
        $specialPlanPrices = $this->transformKeySpecialPrices($specialPlanPrices);
        $planRoomRates = collect($planRoomRates)
                         ->transform(function($rate) use($specialPlanPrices, $plan) {
                             if (!empty($specialPlanPrices[$plan->id])) {
                                $specialPrice = $specialPlanPrices[$plan->id];
                                $calcYen = $specialPrice['num'];
                                if ($specialPrice['unit'] == 0) {
                                    $calcYen = $this->calcYen($rate['class_amount'], $specialPrice['num']);
                                }

                                if ($specialPrice['up_off'] == 1) {
                                    $rate['class_amount'] += $calcYen;
                                    return $rate;
                                }

                                if ($specialPrice['up_off'] == 2) {
                                    $rate['class_amount'] -= $calcYen;
                                    return $rate;
                                }
                             }

                            return $rate;
                         })
                         ->toArray();

        return $planRoomRates;
    }

    public function transformKeyHandInputs($handInputPrices)
    {
        $handInputPrices = collect($handInputPrices)->keyBy('room_type_id')->toArray();
        return $handInputPrices; 
    }

    public function calcYen($amount, $percentage)
    {
        return ceil( $amount * ( $percentage * 0.01 ) );
    }

    public function transformKeySpecialPrices($specialPlanPrices)
    {
        $specialPlanPrices = collect($specialPlanPrices)->keyBy('plan_id')->toArray();
        return $specialPlanPrices; 
    }
} 