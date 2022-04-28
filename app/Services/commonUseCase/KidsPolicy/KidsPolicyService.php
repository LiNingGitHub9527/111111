<?php
namespace App\Services\commonUseCase\KidsPolicy;

use App\Models\HotelKidsPolicy;
use App\Models\Plan;

class KidsPolicyService
{
    public function __construct()
    {
        
    }

    public function getKidsPolicy($ages, $hotelId)
    {
        $kidsPolicies = [];

        if (collect($ages)->isNotEmpty()) {
            foreach ($ages as $index => $age) {
                $policy = HotelKidsPolicy::select('id as kids_policy_id', 'age_start', 'age_end', 'is_forbidden', 'is_all_room', 'room_type_ids', 'is_rate', 'fixed_amount', 'rate')
                                           ->where('hotel_id', $hotelId)
                                           ->where('age_start', $age->age_start)
                                           ->where('age_end', $age->age_end)
                                           ->first();

                if (!empty($policy)) {
                    $policy->num = $age->num;
                    $policy = $policy->toArray();
    
                    $kidsPolicies[$index] = $policy;
                }
            }
        }

        return $kidsPolicies; 
    }

    public function calcChildSum($roomTypeCapa, $kidsPolicies, $ageNums)
    {
        $kidsPolicies = collect($kidsPolicies)->keyBy('age_start')->toArray();

        $roomTypeId = $roomTypeCapa['room_type_id'];
        $childSum = 0;
        $childAsAdult = 0;
        foreach ($ageNums as $age) {
            $targetPolicy = $kidsPolicies[$age['age_start']];
            if (!$targetPolicy['is_all_room']) {
                // $includeRoomTypeIds = json_decode($targetPolicy['room_type_ids']);
                $includeRoomTypeIds = $targetPolicy['room_type_ids'];
                if (in_array($roomTypeId, $includeRoomTypeIds)) {
                    $childSum += $age['num'];
                } else {
                    $childAsAdult += $age['num'];
                }
            } else {
                $childSum += $age['num'];
            }
        }
        $res['child_sum'] = $childSum;
        $res['child_as_adult'] = $childAsAdult;
        return $res;

    }

    // キッズポリシーの金額算出スタート

    public function calcChildAmount($rates, $kidsPolicies, $personNums, $postChildSum, $planId)
    {
        $plan = Plan::find($planId);

        #####人数区分が実装されるまで固定で2をセットする####
        $plan->fee_class_type = 2;
        ##########################################

        foreach ($rates as $date => $rate) {
            if ($plan->fee_class_type == 2) {
                $kidsAmount = $this->calcAmountRcClass($rate['room_type_id'], $rate['class_person_num'], $rate['class_amount'], $kidsPolicies, $personNums, $postChildSum);
                $rates[$date]['kids_amount'] = $kidsAmount;
                $rates[$date]['all_amount'] = $rate['class_amount'] + $kidsAmount;
            } elseif ($plan->fee_class_type == 1) {
                // TODO:人数区分料金が実装され次第ここの処理を行う
                // 実装されるまでは、同様に、calcAmountRcClassを呼び出す
                $kidsAmount = $this->calcAmountRcClass($rate['room_type_id'], $rate['class_person_num'], $rate['class_amount'], $kidsPolicies, $personNums, $postChildSum);
                $rates[$date]['kids_amount'] = $kidsAmount;
                $rates[$date]['all_amount'] = $rate['class_amount'] + $kidsAmount;
            }
        }

        return $rates;
    }

    // キッズポリシーの金額を算出する
    // kids_policy_id, kids_policy_amount, amount
    public function calcAmountRcClass($roomTypeId, $classPersonNum, $classAmount, $kidsPolicies, $personNums, $postChildSum)
    {
        // 算出する部屋タイプが kids_policy のroom_type_idsの中に含まれている場合のみ算出

        // まずキッズポリシーそれぞれの子供人数の金額を算出する
        $kidsAmount = $this->calcKidsAmount($kidsPolicies, $classAmount, $roomTypeId, $personNums['adult_num']);

        // postされた子供人数の全員に対してキッスポリシーを適用できるかどうか
        if ($postChildSum <= $personNums['child_num']) {
            $kidsSumAmountSum = collect($kidsAmount)->pluck('kids_all_amount')->sum();
        } else {
            // できない場合は最も安くなるようにする
            $kidsSumAmountSum = $this->calcCheapestKidsAmount($kidsAmount, $personNums['child_num']);
        }
        return ceil($kidsSumAmountSum);
    }

    public function calcKidsAmount($kidsPolicies, $classAmount, $roomTypeId, $adultNum)
    {
        $kidsAmounts = collect($kidsPolicies)
                       ->transform(function($policy) use($classAmount, $roomTypeId, $adultNum){
                           // 全ての部屋に適用できるキッズポリシーか
                           if ($policy['is_all_room'] == 1) {
                               $kidsAmount = $this->calcKidsAmountAllRoom($policy['is_rate'], $policy['fixed_amount'], $policy['rate'], $policy['num'], $classAmount, $adultNum);
                           } else {
                                // $policyRoomTypeIds = json_decode($policy['room_type_ids']);
                                $policyRoomTypeIds = $policy['room_type_ids'];
                                // $roomTypeIdが、キッズポリシーの部屋タイプに含まれていない場合はキッズポリシー金額を算出しない
                                if (!in_array($roomTypeId, $policyRoomTypeIds)) {
                                    $kidsAmount = $classAmount / $adultNum; 
                                } else {
                                    $kidsAmount = $this->calcKidsAmountAllRoom($policy['is_rate'], $policy['fixed_amount'], $policy['rate'], $policy['num'], $classAmount, $adultNum);
                                }
                           }
                           $policy['kids_1_amount'] = $kidsAmount; 
                           $policy['kids_all_amount'] = $kidsAmount * $policy['num'];
                           return $policy;
                       })->toArray();

        return $kidsAmounts;
    }

    // キッズポリシーごとの 「子供一人当たり」 の料金を算出する
    public function calcKidsAmountAllRoom($isRate, $fixedAmount, $rate, $num, $classAmount, $adultNum)
    {
        if ($isRate == 1) {
            $kidsAmount = $fixedAmount;
        } else {
            $amountPerAdult = $classAmount / $adultNum;
            $kidsAmount = $amountPerAdult * ($rate / 100);
            $kidsAmount = $kidsAmount;
        }

        return $kidsAmount; 
    } 

    // 人数区分料金を実装するタイミングで実装
    public function calcAmountPersonClass($classPersonNum, $classAmount, $kidsPolicies, $personNums)
    {
        
    }

    // 渡されたキッポリシー配列から、最も金額が安いパターンの際の金額を算出する
    public function calcCheapestKidsAmount($kidsPolicyAmounts, $childNum)
    {
        $AmountCheapAsc = collect($kidsPolicyAmounts)
                          ->sortBy('kids_1_amount')
                          ->toArray();

        $remainChildNum = $childNum;
        $kidsAmount = 0;
        foreach ($AmountCheapAsc as $value) {
            for ($currentNum=1; $currentNum<=$value['num']; $currentNum++) {
                if ($remainChildNum > 0) {
                    $kidsAmount += $value['kids_1_amount'];
                    $remainChildNum -= 1;
                } else {
                    continue;
                }
            }
        }
        return $kidsAmount; 
    }

    //渡されたキッズポリシー配列から、最も金額が安くなるパターンを算出しつつ、適用されるキッズポリシーごとの人数と金額の配列を作成する
    public function makeReserveKidsPolicyData($kidsPolicyAmounts, $childNum, $reservePlanId)
    {
        $AmountCheapAsc = collect($kidsPolicyAmounts)
                          ->sortBy('kids_1_amount')
                          ->toArray();

        $kidsPolicyData = [];
        $remainChildNum = $childNum;
        $i = 0;
        foreach ($AmountCheapAsc as $value) {
            for ($currentNum=1; $currentNum<=$value['num']; $currentNum++) {
                if ($remainChildNum > 0) {
                    $kidsPolicyData[$i]['kids_policy_id'] = $value['kids_policy_id'];
                    $kidsPolicyData[$i]['reservation_plan_id'] = $reservePlanId;
                    $kidsPolicyData[$i]['child_num'] = 1;
                    $kidsPolicyData[$i]['kids_1_amount'] = $value['kids_1_amount'];
                    $remainChildNum -= 1;
                } else {
                    continue;
                }
                $i++;
            }
        }
        // 全て並列に格納した配列を、kids_policy_idごとにgroupByしてそれぞれの適用人数、合計額をまとめる
        $kidsPolicyData = $this->convertKidsPolicyData($kidsPolicyData, $reservePlanId);

        return $kidsPolicyData; 
    }

    public function convertKidsPolicyData($kidsPolicyData, $reservePlanId)
    {
        $kidsPolicyData = collect($kidsPolicyData)
                          ->groupBy('kids_policy_id')
                          ->transform(function($policy, $kidsPolicyId) use($reservePlanId) {
                              $res['amount'] = collect($policy)->sum('kids_1_amount');
                              $res['child_num'] = collect($policy)->sum('child_num');
                              $res['kids_policy_id'] = $kidsPolicyId;
                              $res['reservation_plan_id'] = $reservePlanId;

                              return $res;
                          })
                          ->toArray();

        return $kidsPolicyData;
    }

    public function getKidsPolicyAgeEndByIds($kidsPolicyIds)
    {
        $kidsPolicyAgeEnds = HotelKidsPolicy::select('id', 'age_end')
                                              ->whereIn('id', $kidsPolicyIds)
                                              ->get()
                                              ->keyBy('id')
                                              ->toArray();

        return $kidsPolicyAgeEnds;
    }
}

