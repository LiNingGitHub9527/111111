<?php

namespace App\Services\commonUseCase\Reservation;

use App\Http\Controllers\CommonUseCase\Reservation\BookingCoreController;
use App\Jobs\Mail\FailPayNotificationJob;
use App\Models\HotelRoomType;
use App\Models\Reservation;
use App\Models\ReservationBranch;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservationKidsPolicy;
use App\Models\ReservationRefund;
use App\Models\RoomStock;
use Carbon\Carbon;
use DB;

class ReserveService
{

    public function __construct()
    {
        $this->temairazu_service = app()->make('TemairazuService');
        $this->kids_policy_service = app()->make('KidsPolicyService');
        $this->stripe_service = app()->make('StripeService');
        $this->reserve_change_service = app()->make('ReserveChangeService');
        $this->canpoli_service = app()->make('CancelPolicyService');
    }

    public function calcCommission($amount, $rate)
    {
        return round($amount * $rate);
    }

    public function makeReserveCode()
    {
        $prefix = config('reserve.reserve_code_prefix');

        $isUnique = true;
        while ($isUnique) {
            $code = $prefix . substr(str_shuffle('abcdefghijklmnopqrstuvwxyz'), 0, 5);
            $isUnique = $this->checkCodeUnique($code);
        }

        return $code;
    }

    public function checkCodeUnique($code)
    {
        $check = Reservation::where('reservation_code', $code)->get()->isNotEmpty();
        return $check;
    }

    public function insertStayReserveData($insertData)
    {
        $insertData['payment_status'] = 3;
        DB::table('reservations')->insert($insertData);
        $reserveId = DB::getPdo()->lastInsertId();

        return $reserveId;
    }

    public function updateReservation($updateData, $reserveId)
    {
        Reservation::where('id', $reserveId)->update([
            'payment_method' => $updateData['payment_method'] ?? null,
            'stripe_payment_id' => $updateData['stripe_payment_id'] ?? null,
            'stripe_customer_id' => $updateData['stripe_customer_id'] ?? null,
            'payment_status' => $updateData['payment_status'] ?? 0
        ]);

        return true;
    }

    public function updateStayReserveData($reserveId, $updateData)
    {
        Reservation::where('id', $reserveId)->update($updateData);
        return true;
    }

    public function getIncreaseStockData($reserveId, $branchChangeMap = NULL)
    {
        $stockQuery = ReservationBranch::with('reservationPlan')
            ->where('reservation_status', 0)
            ->where('reservation_id', $reserveId);

        if (!empty($branchChangeMap)) {
            $exceptBranchNums = $this->reserve_change_service->filterBranchMapByStatus($branchChangeMap, ['N']);
            $stockQuery->whereNotIn('reservation_branch_num', $exceptBranchNums);
        }

        $returnStockInfo = $stockQuery->get();

        return $returnStockInfo;
    }

    public function reserveIncreaseRoomStock($increaseRoomStockData, $clientId, $hotelId)
    {
        foreach ($increaseRoomStockData as $increaseData) {
            $reservePlans = $increaseData->reservationPlan->toArray();
            $date = collect($reservePlans)->pluck('date')
                ->map(function ($d) {
                    return Carbon::parse($d)->format('Y-m-d');
                })
                ->toArray();

            $query = RoomStock::select('id', 'date_stock_num')
                ->whereIn('date', $date)
                ->where('client_id', $clientId)
                ->where('hotel_id', $hotelId)
                ->where('hotel_room_type_id', $increaseData->room_type_id);

            $roomNum = $increaseData->room_num;
            $query->increment('date_stock_num', $roomNum);
            $query->decrement('date_reserve_num', $roomNum);
        }

        return ['res' => true];
    }

    // 予約の部屋タイプが複数存在するかどうか？
    public function isReserveBranchNum($planRooms)
    {
        $branchCount = collect($planRooms)->pluck('room_type_id')->unique()->count();
        return $branchCount > 1 ? true : false;
    }

    // １つの予約に複数の部屋タイプがある場合に、枝番号を算出する
    public function calcReserveBranchNum($planRooms)
    {
        $branchNumMap = $this->mapNumPerRoomTypeIds($planRooms);
        foreach ($planRooms as &$planRoom) {
            $branchNum = $branchNumMap[$planRoom->room_type_id]['branch_num'];
            $planRoom->reservation_branch_num = $branchNum;
        }

        return $planRooms;
    }

    // 部屋タイプごとに割り当てる枝番号をまとめた配列を作成する
    private function mapNumPerRoomTypeIds($planRooms)
    {
        $roomTypeIds = collect($planRooms)->pluck('room_type_id')->unique();
        $branchNumMap = $roomTypeIds
            ->transform(function ($roomTypeId, $index) {
                $map['room_type_id'] = $roomTypeId;
                $map['branch_num'] = $index + 1;
                return $map;
            })->keyBy('room_type_id')->toArray();

        return $branchNumMap;
    }

    public function calcPriceDetailPerBranch($planRooms, $isTax = NULL)
    {
        $priceDetails = [];
        foreach ($planRooms as $planRoom) {
            $branchNum = $planRoom->reservation_branch_num;
            if (!empty($priceDetails[$branchNum])) {
                $priceDetails[$branchNum] += $planRoom->amount;
            } else {
                $priceDetails[$branchNum] = $planRoom->amount;
            }
        }

        if (isset($isTax) && $isTax == 1) {
            $priceDetails = collect($priceDetails)
                ->map(function ($amount) {
                    return floor($amount * 1.1);
                })
                ->toArray();
        }

        return $priceDetails;
    }

    public function makeInsertPlanRoomData($planRooms, $reserveId, $branchId = NULL)
    {
        $insertData = [];
        $insertData = collect($planRooms->amount_breakdown)
            ->transform(function ($planRoom, $date) use ($insertData, $reserveId, $planRooms, $branchId) {
                $insertData['room_number'] = $planRooms->room_number;
                $insertData['date'] = $planRoom->date;
                $insertData['reservation_id'] = $reserveId;
                $insertData['adult_num'] = $planRooms->adult_num;
                $insertData['amount'] = $planRoom->all_amount;
                if (!empty($branchId)) {
                    $insertData['reservation_branch_id'] = $branchId;
                }
                if (!empty($planRooms->child)) {
                    $childNum = $this->calcChildNumPerRoom($planRooms->child);
                } else {
                    $childNum = 0;
                }
                $insertData['child_num'] = $childNum;

                return $insertData;
            })
            ->toArray();

        return $insertData;
    }

    public function calcChildNumPerRoom($ages)
    {
        $childSum = 0;
        foreach ($ages as $age) {
            $childSum += $age->child_num;
        }

        return $childSum;
    }

    public function insertPlanRoomData($insertData)
    {
        $reservePlanIds = [];
        $i = 0;
        foreach ($insertData as $data) {
            DB::table('reservation_plans')->insert($data);
            $reservePlanId = DB::getPdo()->lastInsertId();
            $reservePlanIds[$i] = $reservePlanId;
            $i++;
        }
        return $reservePlanIds;
    }

    public function insertReserveKidsPolicy($insertData)
    {
        ReservationKidsPolicy::insert($insertData);
        return true;
    }

    public function doPrepay($paymentMethod, $amount)
    {
        if ($paymentMethod == 0) {
            return ['status' => true];
        } else {
            $status = $this->stripe_service->pay();
        }
    }

    public function makeReduceRoomStockData($reduceData, $reserveBreakDown)
    {
        $data = [];
        foreach ($reserveBreakDown as $date => $breakdown) {
            $data['room_type_id'] = $breakdown->room_type_id;
            $data['date'] = $breakdown->date;
            array_push($reduceData, $data);
        }

        return $reduceData;
    }

    // 予約時に在庫を減らす
    public function ReserveReduceRoomStock($reduceRoomStockData, $clientId, $hotelId)
    {
        foreach ($reduceRoomStockData as $reduceData) {
            $roomStock = RoomStock::select('id', 'date_stock_num', 'date_sale_condition')
                ->where('date', $reduceData['date'])
                ->where('client_id', $clientId)
                ->where('hotel_id', $hotelId)
                ->where('hotel_room_type_id', $reduceData['room_type_id'])
                ->first();

            if (!empty($roomStock->date_stock_num) && $roomStock->date_stock_num > 0 && $roomStock->date_sale_condition != 1) {
                $roomStock->decrement('date_stock_num', 1);
                $roomStock->increment('date_reserve_num', 1);
            } else {
                $roomType = HotelRoomType::find($reduceData['room_type_id']);
                $message = '申し訳ございません。 ' . Carbon::parse($reduceData['date'])->format('n月j日') . ' の「' . $roomType->name . '」はご予約のお手続き中に満室となりました。恐れ入りますが、再度ご予約のお手続きをお願い致します。';
                return ['res' => false, 'message' => $message];
            }
        }

        return ['res' => true];
    }

    public function getReservePlansByReserveId($reservationId)
    {
        $reservationPlans = ReservationBranch::with('reservationPlan')
            ->with('reservationPlan.kidsPolicies')
            ->where('reservation_id', $reservationId)
            ->get();

        return $reservationPlans;
    }

    public function convertBranchPlanRltn($reservePlans)
    {
        $planData = [];
        foreach ($reservePlans as $reservePlan) {
            $plans = $reservePlan->reservationPlan;
            foreach ($plans as $plan) {
                $plan->reservation_status = $reservePlan->reservation_status;
                $plan->room_type_id = $reservePlan->room_type_id;
                $plan->plan_id = $reservePlan->plan_id;
                $planData[] = $plan;
            }
        }

        return $planData;
    }

    public function rejectDelAndCanPlan($reservationPlans)
    {
        $reservationPlans = collect($reservationPlans)
            ->reject(function ($reservationPlan) {
                // キャンセル
                if ($reservationPlan->reservation_status != 0) return true;
            })
            ->values();

        return $reservationPlans;
    }

    public function confirmCancel($reserve, $cancelDateTime, $cancelFee, $cancelCommission)
    {
        $reserve->cancel_date_time = $cancelDateTime;
        $reserve->cancel_fee = $cancelFee;
        $reserve->reservation_status = 1;
        $reserve->commission_price = ceil($cancelFee * config('commission.reserve_rate'));
        if ($cancelCommission > 0) {
            $reserve->payment_commission_price = $cancelCommission;
        }
        $reserve->update();

        $this->reserve_change_service->updateCancelBranch($reserve->id);

        return true;
    }

    public function getStayInOutTime($hotel, $postCheckinDate, $postCheckoutDate)
    {
        $checkinStartTime = Carbon::parse($hotel->checkin_start)->format('H:i');
        $checkinEndTime = Carbon::parse($hotel->checkin_end)->format('H:i');
        $checkoutEndTime = Carbon::parse($hotel->checkout_end)->format('H:i');
        $checkinStart = $postCheckinDate . ' ' . $checkinStartTime;
        $checkinEnd = $postCheckinDate . ' ' . $checkinEndTime;
        if (strtotime($checkinEnd) < strtotime($checkinStart)) {
            $checkinEnd = Carbon::parse($checkinEnd)->modify('+1 day')->format('Y-m-d H:i');
        }
        $checkoutEnd = $postCheckoutDate . ' ' . $checkoutEndTime;

        $res = [
            'checkin_start' => $checkinStart,
            'checkin_end' => $checkinEnd,
            'checkout_end' => $checkoutEnd,
        ];

        return $res;
    }

    // オーソリをキャンセルし、reservation_statusをキャンセルで更新する
    public function cancelFailPayBook($reservation, $failUpdate)
    {
        $refundData = [];
        $this->stripe_service->manageFullRefund($reservation->id, $reservation['stripe_payment_id'], $reservation->accommodation_price, $refundData);
        $reservation->update($failUpdate);

        return true;
    }

    // 決済失敗により、予約がキャンセルされたことを通知するメールを送信
    public function sendFailPayMail($reservation)
    {
        dispatch_now(new FailPayNotificationJob($reservation));
    }

    // payment idを元に予約を取得する
    public function getReserveByPayId($paymentId)
    {
        $reservation = Reservation::with('reservationPlans', 'reservationBranches')
            ->where('stripe_payment_id', $paymentId)
            ->first();

        return $reservation;
    }

    // $planRoomsから各部屋の大人１人当たりの料金を算出
    public function calc1AdultAmount($planRooms)
    {
        foreach ($planRooms as $roomNum => &$planRoom) {
            $adultNum = $planRoom->adult_num;
            $adultAmounts = collect($planRoom->amount_breakdown)->sum('class_amount');
            $adult1Amount = floor($adultAmounts / $adultNum);
            $planRoom->adult_1_amount = $adult1Amount;
        }

        return $planRooms;
    }

    public function saveReserveCanPoli($policy, $reserveId, $hotelId)
    {
        $insertData['hotel_id'] = $hotelId;
        $insertData['cancel_policy_id'] = $policy->id;
        $insertData['reservation_id'] = $reserveId;
        $insertData['is_free_cancel'] = $policy->is_free_cancel;
        $insertData['free_day'] = $policy->free_day;
        $insertData['free_time'] = $policy->free_time;
        $insertData['cancel_charge_rate'] = $policy->cancel_charge_rate;
        $insertData['no_show_charge_rate'] = $policy->no_show_charge_rate;
        $insertData['created_at'] = now();
        ReservationCancelPolicy::insert($insertData);

        return true;
    }

    public function updateReserveCanPoli($policy, $reserveId, $hotelId)
    {
        $updateData['cancel_policy_id'] = $policy->id;
        $updateData['is_free_cancel'] = $policy->is_free_cancel;
        $updateData['free_day'] = $policy->free_day;
        $updateData['free_time'] = $policy->free_time;
        $updateData['cancel_charge_rate'] = $policy->cancel_charge_rate;
        $updateData['no_show_charge_rate'] = $policy->no_show_charge_rate;
        $updateData['updated_at'] = now();

        ReservationCancelPolicy::where('reservation_id', $reserveId)->update($updateData);

        return true;
    }

    // reservation_branchesとreservation_plansのリレーションを取得する
    public function getBranchPlanRooms($reserveId)
    {
        $branchData = ReservationBranch::with('reservationPlan')
            ->where('reservation_id', $reserveId)
            ->get()
            ->keyBy('reservation_branch_num');

        return $branchData;
    }

    public function cancelReservation(
        &$reservation, 
        $type,
        ?bool $isHotel=true
    ) {
        $reservationId = $reservation->id;
        $totalAmount = $reservation->accommodation_price;
        $reservationRefund = new ReservationRefund([
            'reservation_id' => $reservationId,
            'type' => $type,
            'status' => 2,
            'reservation_amount' => $totalAmount
        ]);
        $reservationRefund->save();
        $reservationCancelPolicy = ReservationCancelPolicy::where('reservation_id', $reservationId)->first();

        $checkinTime = Carbon::parse($reservation->checkin_time)->format('Y-m-d');
        $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow(NULL, $checkinTime, $reservationCancelPolicy);

        $refundData = [];
        $stripePaymentId = $reservation->stripe_payment_id;
        $cancelAmount = 0;
        if ($isFreeCancel) {
            $refundAmount = $totalAmount;
            $result = $this->stripe_service->manageFullRefund($reservationId, $stripePaymentId, $refundAmount, $refundData);
        } else {
            if ($type == 1) { 
                $chargeRate = $reservationCancelPolicy->cancel_charge_rate / 100;
            } else {
                $chargeRate = $reservationCancelPolicy->no_show_charge_rate / 100;
            }

            $cancelAmount = round($totalAmount * $chargeRate);
            $refundAmount = $totalAmount - $cancelAmount;
            if ($reservation->payment_status == 1) {
                if ($refundAmount > 0) {
                    $result = $this->stripe_service->managePartialRefund($reservationId, $refundAmount, $stripePaymentId, $refundData);
                } else {
                    $result = true;
                }
            } else {
                $this->stripe_service->manageFullRefund($reservationId, $stripePaymentId, $refundAmount, $refundData);
                $desc = $this->stripe_service->makeCancelDesc($reservation);
                $result = $this->stripe_service->manageDoPayByCid($reservationId, $reservation->stripe_customer_id, $cancelAmount, $desc);
            }
        }
        $reservation->cancel_fee = $cancelAmount;
        $reservation->cancel_date_time = Carbon::now();
        $reservation->payment_commission_price = $this->calcCommission($cancelAmount, config('commission.payment_rate'));
        $reservation->commission_price = ceil($cancelAmount * config('commission.reserve_rate'));
 
        $reservationRefund->update([
            'status' => $result,
            'refund_information' => $refundData['message'],
            'refund_amount' => $refundAmount,
            'stripe_payment_id' => $stripePaymentId,
            'refund_id' => $refundData['refund_id'] ?? null,
            'handle_date' => $refundData['handle_date'] ?? null
        ]);

        return $result;
    }

    public function undoStockByCancel(
        $isHotel,
        \App\Models\Reservation $reservation
    ): void {
        $bookingCoreController = new BookingCoreController;
        if ($isHotel) {
            $bookingCoreController->reserveIncreaseRoomStock($reservation->id, $reservation->hotel);
        } else {
            $bookingCoreController->increaseReserveBlockByCancel($reservation);
        }
    }
} 