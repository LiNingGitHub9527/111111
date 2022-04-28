<?php
namespace App\Services\Browser\User;

use Carbon\Carbon;
use App\Models\Reservation;
use App\Models\ReservationPlan;
use App\Models\ReservationBranch;
use App\Models\ReservationCancelPolicy;

class ReserveChangeService
{

    public function __construct()
    {
        $this->canpoli_service = app()->make('CancelPolicyService');
        $this->calc_cancel_policy_service = app()->make('CalcCancelPolicyService');
    }

    public function findReservationByToken($token)
    {
        $targetReservation = Reservation::select('reservation_status', 'checkin_start', 'id', 'reservations.stay_type',
                                                 'reservations.hotel_id', 'accommodation_price', 'reservation_status',
                                                 'reservations.checkin_time', 'reservations.checkout_time', 'reservations.checkout_end',
                                                 'reservations.lp_url_param', 'reservations.verify_token', 'reservations.payment_method',
                                                 'reservations.email', 'approval_status', 'is_request')
                                          ->where('verify_token', $token)
                                          ->first();

        return $targetReservation;
    }

    // 一泊ごとのreservation_plansのレコードで、同じroom_numberを持つレコードをグループでまとめる
    // ※一泊ごとに1レコード作成しているのは手間いらず仕様に対応するためなので、ユーザー表示の際はここで、グループ化する
    public function summarizeSameRooms($reservationPlans)
    {
        $reservationPlans = $reservationPlans
                            ->groupBy('room_number')
                            ->transform(function($rooms, $roomNumber){
                                $useRoom['amount'] = $rooms->sum('amount');
                                $useRoom['adult_num'] = $rooms->first()->adult_num;
                                $useRoom['child_num'] = $rooms->first()->child_num;
                                $useRoom['room_type_id'] = $rooms->first()->room_type_id;
                                $useRoom['plan_id'] = $rooms->first()->plan_id;

                                $kidsPolicies = $rooms->first()->kidsPolicies;
                                if (!empty($kidsPolicies->toArray())) {
                                    $kidsPolicyId = $kidsPolicies->first()->kids_policy_id;
                                    $kidsPolicyNum = $kidsPolicies->sum('child_num');
                                    $kidsPolicyAmount = $kidsPolicies->sum('amount');
                                }
                                $useRoom['kids_policy_id'] = !empty($kidsPolicies->toArray()) ? $kidsPolicyId : NULL;
                                $useRoom['kids_policy_child_num'] = !empty($kidsPolicies->toArray()) ? $kidsPolicyNum : NULL;
                                $useRoom['kids_policies_amount'] = !empty($kidsPolicies->toArray()) ? $kidsPolicyAmount : NULL;

                                return $useRoom;
                            })
                            ->toArray();

        return $reservationPlans;
    }

    public function mergeReservePlanAgeEnd($reservationPlans, $kidsPolicyAgeEnds)
    {
        $reservationPlans = collect($reservationPlans)
                            ->transform(function($room, $roomNumber) use($kidsPolicyAgeEnds) {
                                $room['kids_policy_age_end'] = $kidsPolicyAgeEnds[$room['kids_policy_id']]['age_end'];
                                return $room;
                            })
                            ->toArray();

        return $reservationPlans;
    }

    // public function convertReserve4Compare($reserve)
    // {
    //     unset(
    //         $reserve['id'],
    //         $reserve['client_id'],
    //         $reserve['hotel_id'],
    //         $reserve['reservation_code'],
    //         $reserve['payment_method'],
    //         $reserve['stripe_customer_id'],
    //         $reserve['stripe_payment_id'],
    //         $reserve['payment_status'],
    //         $reserve['reservation_status'],
    //         $reserve['tema_reservation_type'],
    //         $reserve['reservation_date'],
    //         $reserve['stay_type'],
    //         $reserve['cancel_date_time'],
    //         $reserve['change_date_time'],
    //         $reserve['cancel_fee'],
    //         $reserve['payment_commission_rate'],
    //         $reserve['payment_commission_price'],
    //         $reserve['verify_token'],
    //         $reserve['created_at'],
    //         $reserve['updated_at'],
    //         $reserve['deleted_at'],
    //         $reserve['lp_url_param']
    //     );

    //     ###test###
    //     unset($reserve['name_kana']);
    //     ##########

    //     return $reserve;
    // }

    // 予約変更時に、既存のreservation_branchesの部屋タイプと同じ枝番号をplanRooomsに割り当てる
    public function assignBranchNumPlanRooms($planRooms, $branchData)
    {
        $branchNumMax = $branchData->pluck('reservation_branch_num')->max();
        $newBranchRoomType = [];
        foreach ($planRooms as &$planRoom) {
            $targetBranch = $branchData->where('room_type_id', $planRoom->room_type_id)->first();
            if (!empty($targetBranch) && $targetBranch['reservation_status'] == 0) {
                // 既存予約で同じ部屋タイプがあり、キャンセルされていない場合
                $planRoom->reservation_branch_num = $targetBranch->reservation_branch_num;
            } else {
                // 既存予約にない部屋タイプの予約か、既存予約で一度同じ部屋タイプがキャンセルされていた場合
                if (!empty($newBranchRoomType[$planRoom->room_type_id])) {
                    $planRoom->reservation_branch_num = $newBranchRoomType[$planRoom->room_type_id];
                    continue;
                }
                $branchNumMax += 1;
                $newBranchRoomType[$planRoom->room_type_id] = $branchNumMax;
                $planRoom->reservation_branch_num = $branchNumMax;
            }
        }

        return $planRooms;
    }

    public function convertReservedBranch4Compare($reservedBranchData)
    {
        foreach ($reservedBranchData as $branchNum => &$branchData) {
            unset(
                $branchData['id'],
                $branchData['reservation_status'],
                $branchData['tema_reservation_type'],
                $branchData['cancel_date_time'],
                $branchData['change_date_time'],
                $branchData['created_at'],
                $branchData['updated_at'],
                $branchData['deleted_at'],
            );
            foreach ($branchData['reservation_plan'] as &$planData) {
                unset(
                    $planData['id'],
                    $planData['reservation_branch_id'],
                    $planData['created_at'],
                    $planData['updated_at'],
                    $planData['deleted_at']
                );
            }
        }
        return $reservedBranchData;
    }

    public function convertPlanRoom4Compare($insertPlanRooms)
    {
        $data = [];
        foreach ($insertPlanRooms as $planRooms) {
            foreach ($planRooms as $planRoom) {
                $data[] = $planRoom;
            }
        }

        return $data;
    }

    public function convertBranch4Compare($branchData, $insertPlanRooms)
    {
        foreach ($branchData as $branchNum => &$branch) {
            unset($branch['created_at']);
            $planRoomData = collect($insertPlanRooms)->where('reservation_branch_num', $branchNum)->values()->toArray();
            $branch['reserve_plans'] = $planRoomData;
        }

        return $branchData;
    }

    public function rejectDelAndCanBranch($reservedBranchData)
    {
        $reservedBranchData = $reservedBranchData
                              ->reject(function($branch){
                                  if ($branch->reservation_status != 0) return true;
                                  if ((!empty($branch->deleted_at))) return true;
                              });

        return $reservedBranchData;
    }

    // 既存の予約データと新たにPOSTされた予約データを比較し、各枝番号が、新規・変更・キャンセルのいずれかを特定する
    # B: 新規
    # M: 変更
    # C: キャンセル
    # N: 変更なし
    public function checkBranchDataChange($reservedBranchData, $branchData)
    {
        $branchChangeMap = [];
        foreach ($reservedBranchData as $branchNum => $reservedData) {
            if (!empty($branchData[$branchNum])) {
                $data = $branchData[$branchNum];
                $reservedExceptPlanData = collect($reservedData)->except('reserve_plans')->toArray();
                $exceptPlanData = collect($data)->except('reserve_plans')->toArray();

                if (count(array_diff_assoc($reservedExceptPlanData, $exceptPlanData)) > 0) {
                    // 変更
                    $branchChangeMap[$branchNum] = 'M';
                    continue;
                }

                $reservedPlanData = $reservedData['reserve_plans'];
                $planData = $data['reserve_plans'];

                if (count($reservedPlanData) != count($planData)) {
                    // 変更
                    $branchChangeMap[$branchNum] = 'M';
                    continue;
                }

                foreach ($reservedPlanData as $reservedData) {
                    $targetPlanData = collect($planData)->where('date', $reservedData['date'])->first();
                    if (empty($targetPlanData)) {
                        // 変更
                        $branchChangeMap[$branchNum] = 'M';
                        continue;
                    }

                    unset(
                        $targetPlanData['reservation_branch_num'],
                        $targetPlanData['room_number'],
                        $reservedData['room_number']
                    );

                    if ( count( array_diff_assoc($reservedData, $targetPlanData) ) > 0) {
                        // 変更
                        $branchChangeMap[$branchNum] = 'M';
                        continue;
                    }
                }

                if (!empty($branchChangeMap[$branchNum])) continue;

                $branchChangeMap[$branchNum] = 'N';

            } else {
                // キャンセル
                $branchChangeMap[$branchNum] = 'C';
            }
        }

        foreach ($branchData as $branchNum => $branch) {
            if (empty($reservedBranchData[$branchNum])) {
                // 新規
                $branchChangeMap[$branchNum] = 'B';
            }
        }
        return $branchChangeMap;
    }

    public function deleteChangeBranch($reserveId, $branchChangeMap)
    {
        $changeBranchNums = $this->filterBranchMapByStatus($branchChangeMap, ['M']);
        $query = ReservationBranch::where('reservation_id', $reserveId)
                                    ->whereIn('reservation_branch_num', $changeBranchNums);

        $branchIds = $query->pluck('id')->toArray();

        $query->delete();
        ReservationPlan::whereIn('reservation_branch_id', $branchIds)->delete();

        return true;
    }

    public function convertNewAndChangeBranch($branchData, $branchChangeMap)
    {
        $branchData = collect($branchData)
                      ->transform(function($data, $branchNum) use($branchChangeMap){
                          $status = $branchChangeMap[$branchNum];
                          if ($status == 'M') {
                              $data['change_date_time'] = now();
                              $data['tema_reservation_type'] = 1;
                              return $data;
                          } elseif ($status == 'B') {
                              return $data;
                          }
                      })
                      ->whereNotNull()
                      ->toArray();

        return $branchData;
    }

    public function updateCancelBranch($reserveId, $branchChangeMap=NULL)
    {
        $update = [
            'reservation_status' => 1,
            'cancel_date_time' => now()
        ];

        $query = ReservationBranch::where('reservation_id', $reserveId);
        if(!empty($branchChangeMap)) {
            $cancelBranchNums = $this->filterBranchMapByStatus($branchChangeMap, ['C']);
            $query = $query->whereIn('reservation_branch_num', $cancelBranchNums);
        }
        $query->update($update);

        return true;
    }

    public function filterBranchMapByStatus($branchChangeMap, array $targetStatus)
    {
        $targetBranchNums = collect($branchChangeMap)
                            ->filter(function($status, $branchNum) use($targetStatus){
                                return in_array($status, $targetStatus);
                            })->keys()->toArray();

        return $targetBranchNums;
    }

    public function mapBranchReserveDate($insertBranchData, $reserveDate)
    {
        $insertBranchData = collect($insertBranchData)
                            ->transform(function($data) use($reserveDate){
                                $data['reservation_date_time'] = $reserveDate;
                                return $data;
                            })
                            ->toArray();

        return $insertBranchData;
    }

    public function calcCancelFeeData($reservation)
    {
        $cancelPolicy = ReservationCancelPolicy::where('reservation_id', $reservation->id)->first();
        $checkinDate = Carbon::parse($reservation->checkin_time)->format('Y-m-d');
        $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow(NULL, $checkinDate, json_decode(json_encode($cancelPolicy)));
        $nowDateTime = Carbon::now()->format('Y-m-d H:i');
        $cancelFeeData = $this->calc_cancel_policy_service->getCancelFee($cancelPolicy, $checkinDate, $nowDateTime, $reservation->accommodation_price, $isFreeCancel);

        return $cancelFeeData;
    }

    public function calcNoShowFeeData($reservation)
    {
        $cancelPolicy = ReservationCancelPolicy::where('reservation_id', $reservation->id)->first();
        $nowShowChargeRate = $cancelPolicy->no_show_charge_rate;
        $nowShowChargeRate = $nowShowChargeRate / 100;
        $cancelFeeData['cancel_fee'] = $reservation->accommodation_price * $nowShowChargeRate;

        return $cancelFeeData;
    }

}