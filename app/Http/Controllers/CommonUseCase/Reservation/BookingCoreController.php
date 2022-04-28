<?php

namespace App\Http\Controllers\CommonUseCase\Reservation;

use Carbon\Carbon;
use App\Models\Plan;
use App\Models\CancelPolicy;
use App\Models\Form;
use App\Models\ReservationRefund;
use App\Jobs\Mail\ReserveConfirmJob;
use App\Jobs\Mail\ReserveChangeJob;
use App\Http\Controllers\Controller;
use App\Models\Hotel;

class BookingCoreController extends Controller
{

    public function __construct()
    {
        $this->reserve_service = app()->make('ReserveService');
        $this->browse_reserve_service = app()->make('BrowserReserveService');
        $this->reserve_session_service = app()->make('ReserveSessionService');
        $this->canpoli_service = app()->make('CancelPolicyService');
        $this->temairazu_service = app()->make('TemairazuService');
        $this->other_reserve_service = app()->make('OtherReserveService');
        $this->booking_session_key = 'booking';
    }

    protected function prePay(&$post, $reserveId)
    {
        $stripeService = app()->make('StripeService');
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);
            $hotel = Hotel::find($bookingData['base_info']['hotel_id']);
            $planRooms = $this->browse_reserve_service->makePlanRoomsFromSessionData($bookingData);
            $roomFees = $this->browse_reserve_service->transformStayFeePerRoom($bookingData['selected_rooms']);

            // 枝番号ごとの料金を算出
            $planRooms = $this->reserve_service->calcReserveBranchNum($planRooms);
            $priceDetails = $this->reserve_service->calcPriceDetailPerBranch($planRooms, $hotel->is_tax);
            $roomAmount['sum'] = collect($priceDetails)->sum();
            // stripe決済のデスクリプション作成
            $description = $stripeService->makePrePayDesc($bookingData, $post['name'], $post['email'], $post['tel']);

            // 無料キャンセル期間が存在するかどうか
            $planId = $bookingData['selected_rooms']['plan_id'];
            $plan = Plan::find($planId);
            $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow($plan->cancel_policy_id, $bookingData['base_info']['in_out_date'][0]);

            // オーソリを作成する
            $res = $stripeService->bookingPrePay($post, $roomAmount['sum'], $description, $post['name'], $post['email'], $isFreeCancel, $reserveId);

            // 処理結果をレスポンス
            if (!$res['res']) {
                return ['res' => 'error', 'message' => $res['message']];
            } else {
                return ['res' => 'ok'];
            }
        } catch (\Exception $e) {
            return ['res' => 'error', 'message' => '予期せぬエラーが発生しました。'];
        }
    }

    public function otherPrePay(array &$payData, int $reserveId, Form $form, int $sumRoomAmount)
    {
        $stripeService = app()->make('StripeService');
        try {
            $bookingData = $this->reserve_session_service->getSessionByKey($this->booking_session_key);

            // stripe決済のデスクリプション作成
            $checkinDate = Carbon::parse($payData['checkin_time'])->format('Y-m-d');
            $description = $stripeService->makeOtherPrePayDesc($bookingData, $payData['name'], $payData['email'], $payData['tel'], $checkinDate);

            // 無料キャンセル期間が存在するかどうか
            $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow($form->cancel_policy_id, $checkinDate);

            // オーソリを作成する
            $res = $stripeService->bookingPrePay($payData, $sumRoomAmount, $description, $payData['name'], $payData['email'], $isFreeCancel, $reserveId);

            // 処理結果をレスポンス
            if (!$res['res']) {
                return ['res' => 'error', 'message' => $res['message']];
            } else {
                return ['res' => 'ok'];
            }
        } catch (\Exception $e) {
            return ['res' => 'error', 'message' => '予期せぬエラーが発生しました。'];
        }
    }

    protected function updatePrePay($reserve, $bookingData, $post, int $businessType = 1, int $sumRoomAmount = 0)
    {
        $stripeService = app()->make('StripeService');

        try {
            if ($businessType == 1) {
                // ホテル用
                $planRooms = $this->browse_reserve_service->makePlanRoomsFromSessionData($bookingData);
                $roomFees = $this->browse_reserve_service->transformStayFeePerRoom($bookingData['selected_rooms']);
                $hotel = Hotel::find($bookingData['base_info']['hotel_id']);

                // 枝番号ごとの料金を算出
                $planRooms = $this->reserve_service->calcReserveBranchNum($planRooms);
                $priceDetails = $this->reserve_service->calcPriceDetailPerBranch($planRooms, $hotel->is_tax);
                $roomAmount['sum'] = collect($priceDetails)->sum();

                // 無料キャンセル期間が存在するかどうか
                $planId = $bookingData['selected_rooms']['plan_id'];
                $plan = Plan::find($planId);
                // $isFreeCancel = $this->canpoli_service->checkFreeCancelByNow($plan->cancel_policy_id, $bookingData['base_info']['in_out_date'][0]);
                // stripe決済のデスクリプション作成
                $post['name'] = $post['first_name'] . $post['last_name'];
                $description = $stripeService->makePrePayDesc($bookingData, $post['name'], $post['email'], $post['tel']);

                // if ($isFreeCancel) {
                $res = $stripeService->manageDoAuthoryPayByCId($reserve->id, $reserve->stripe_customer_id, $roomAmount['sum'], $description);
                $paymentStatus = config('prepay.payment_status.authory');
                // } else {
                //     $res = $stripeService->doPayByCId($reserve->stripe_customer_id, $roomAmount['sum'], $description);
                //     $paymentStatus = config('prepay.payment_status.pay');
                // }
            } else {
                // ホテル以外の業種用
                // 料金
                $amount = $sumRoomAmount;

                // stripe決済のデスクリプション作成
                $checkinDate = Carbon::parse($post['checkin_time'])->format('Y-m-d');
                $description = $stripeService->makeOtherPrePayDesc($bookingData, $post['name'], $post['email'], $post['tel'], $checkinDate);

                $res = $stripeService->manageDoAuthoryPayByCId($reserve->id, $reserve->stripe_customer_id, $amount, $description);
                $paymentStatus = config('prepay.payment_status.authory');
            }

            if (!$res['res']) {
                return $res;
            }

            $res['payment_id'] = $res['data']->id;
            $res['payment_status'] = $paymentStatus;

            return $res;
        } catch (\Exception $e) {
            return ['res' => 'error', 'message' => '予期せぬエラーが発生しました。'];
        }
    }

    // 決済の返金
    public function cancelRefund($reserve, $bookingData)
    {
        $stripeService = app()->make('StripeService');
        $refundData = [];
        $reservationRefund = new ReservationRefund([
            'reservation_id' => $reserve->id,
            'type' => 1,
            'status' => 2,
            'reservation_amount' => $reserve->accommodation_price
        ]);
        $reservationRefund->save();
        // 予約が事前決済なら、返金・もしくはオーソリのキャンセルを実行
        $cancelCommission = 0;
        if ($reserve->payment_method == 1) {
            if (!$bookingData['cancel_info']['is_free_cancel']) {
                // 無料キャンセル不可の場合
                if ($reserve->payment_status == 2) {
                    // オーソリ状態なら、オーソリをキャンセルし、キャンセル料金を即時決済する
                    $refund = $stripeService->manageFullRefund($reserve->id, $reserve->stripe_payment_id, $reserve->accommodation_price, $refundData);

                    $desc = $stripeService->makeCancelDesc($reserve);

                    $refundStatus = $stripeService->manageDoPayByCid($reserve->id, $reserve->stripe_customer_id, $bookingData['cancel_info']['cancel_fee'], $desc);

                    $cancelCommission = $this->reserve_service->calcCommission($bookingData['cancel_info']['cancel_fee'], config('commission.payment_rate'));

                } elseif ($reserve->payment_status == 1) {
                    $refundAmount = $reserve->accommodation_price - $bookingData['cancel_info']['cancel_fee'];

                    // オーソリ状態でないなら、cancel_feeを引いた分だけ払い戻す
                    $refundStatus = $stripeService->managePartialRefund($reserve->id, $refundAmount, $reserve->stripe_payment_id, $refundData);
                }
            } else {
                // 無料キャンセルの場合

                // 全額払い戻し or オーソリキャンセル
                $refundStatus = $stripeService->manageFullRefund($reserve->id, $reserve->stripe_payment_id, $reserve->accommodation_price, $refundData);
            }
            if (!$refundStatus) {
                return ['res' => false, 'message' => '決済ステータスの更新に失敗しました。恐れ入りますがカード会社、もしくは施設にお問い合わせください。'];
            }
        }

        $reservationRefund->update([
            'status' => $refundStatus,
            'refund_information' => $refundData['message'] ?? null,
            'refund_amount' => $refundAmount ?? $reserve->accommodation_price,
            'stripe_payment_id' => $reserve->stripe_payment_id,
            'refund_id' => $refundData['refund_id'] ?? null,
            'handle_date' => $refundData['handle_date'] ?? null
        ]);

        return ['res' => true, 'commission' => $cancelCommission];
    }

    protected function addFormRemarks($urlParam, $post)
    {
        $lp = $this->browse_reserve_service->getFormFromLpParam($urlParam);
        $form = $this->form_service->findForm($lp['form_id']);
        $post['special_request'] = str_replace(config('temairazu.remarks.special_price'), '', $post['special_request']);
        if ($form->is_special_price != 0) {
            $post['special_request'] .= config('temairazu.remarks.special_price');
        }

        return $post;
    }

    protected function addPriceRemarks($tax, $post)
    {
        $taxTx = config('temairazu.remarks.tax') . number_format($tax);
        $post['special_request'] .= $taxTx;

        return $post;
    }

    // 更新前の決済をキャンセルする
    protected function cancelPrepay($reserve)
    {
        $refundData = [];
        $stripeService = app()->make('StripeService');

        $paymentId = $reserve->stripe_payment_id;
        $res = $stripeService->manageFullRefund($reserve->id, $paymentId, $reserve->accommodation_price, $refundData);

        return $res;
    }

    // 予約直前に料金が0で更新されていた場合は予約不可
    public function checkIs0Price($planRooms)
    {
        foreach ($planRooms as $roomNum => $planRoom) {
            $check = $this->browse_reserve_service->get0RatesFromPlanRooms($planRoom->amount_breakdown, $planRoom->plan_id);
            if (!$check) {
                return ['res' => false, 'message' => '申し訳ございません、ご予約のお手続き中にご選択されたお部屋が満室となりました。大変恐れ入りますが、再度人数をご選択くださいませ。'];
            }
        }

        return ['res' => true];
    }

    // planRoomsを整形する
    protected function transformPlanRoom($planRooms, $post, $hotel=NULL)
    {
        if (!empty($hotel)) {
            $isTax = $hotel->is_tax;
        } else {
            $hotel = Hotel::find($post['hotel_id']);
            $isTax = $hotel->is_tax;
        }

        // 枝番号ごとの料金を算出
        $priceDetails = $this->reserve_service->calcPriceDetailPerBranch($planRooms, $isTax);
        $post['accommodation_price'] = collect($priceDetails)->sum();
        $post['accommodation_price_detail'] = json_encode($priceDetails);

        // 大人１人当たりの料金を算出
        $planRooms = $this->reserve_service->calc1AdultAmount($planRooms);

        return ['planRooms' => $planRooms, 'post' => $post];
    }

    // planRoomsに、既存の予約データの枝番号を割り当てる
    protected function assignReservedBranch($planRooms, $reserveId)
    {
        // 既存の予約データのbranchesを取得
        $branchData = $this->reserve_service->getBranchPlanRooms($reserveId);

        // branchesの枝番号をplanRoomsに割り当てる
        $planRooms = $this->reserve_change_service->assignBranchNumPlanRooms($planRooms, $branchData);

        return ['planRooms' => $planRooms, 'branchData' => $branchData];

    }

    protected function saveReserveCanPoli($planId, $reserveId, $hotelId)
    {
        $plan = Plan::find($planId);
        $policy = CancelPolicy::find($plan->cancel_policy_id);
        $this->reserve_service->saveReserveCanPoli($policy, $reserveId, $hotelId);

        return true;
    }

    protected function makeBranches($planRooms, $reserveId, $hotel, $planId, $priceDetail)
    {
        $planRoomPerBranch = $this->browse_reserve_service->makeGroupBranchData($planRooms);
        $planRoomPerBranch = $this->browse_reserve_service->makeInsertBranchData($planRoomPerBranch, $reserveId, $planId, json_decode($priceDetail));

        return $planRoomPerBranch;
    }

    // protected function compareBaseInfo($reservedData, $postData)
    // {
    //     $reservedData = $this->reserve_change_service->convertReserve4Compare($reservedData);
    //     $postData = $this->reserve_change_service->convertReserve4Compare($postData);

    //     $changeCount = count(array_diff_assoc($reservedData, $postData));
    //     $res = $changeCount > 0 ? true : false;

    //     return $res;
    // }

    // 予約変更時に、差分が生じる枝番号を特定する
    protected function compareBranchData($reservedBranchData, $branchData, $insertPlanRooms)
    {
        $reservedBranchData = $this->reserve_change_service->rejectDelAndCanBranch($reservedBranchData);
        $reservedBranchData = $this->reserve_change_service->convertReservedBranch4Compare($reservedBranchData->toArray());
        $insertPlanRooms = $this->reserve_change_service->convertPlanRoom4Compare($insertPlanRooms);
        $branchData = $this->reserve_change_service->convertBranch4Compare($branchData, $insertPlanRooms);
        $branchChangeMap = $this->reserve_change_service->checkBranchDataChange($reservedBranchData, $branchData);

        return $branchChangeMap;
    }

    // updateReserveCancelPolicyByIdに合わせたい
    protected function updateReserveCanPoli($planId, $reserveId, $hotelId)
    {
        $plan = Plan::find($planId);
        $policy = CancelPolicy::find($plan->cancel_policy_id);
        $this->reserve_service->updateReserveCanPoli($policy, $reserveId, $hotelId);
    }

    protected function updateReserveCancelPolicyById($policyId, $reserveId, $hotelId)
    {
        $policy = CancelPolicy::find($policyId);
        $this->reserve_service->updateReserveCanPoli($policy, $reserveId, $hotelId);
    }

    protected function updateReserveBranchPlan($planRooms, $reserveId, $hotel, $branchData, $branchChangeMap, $reserveDate)
    {
        // branchDataを新規or変更のものだけに整形する
        $insertBranchData = $this->reserve_change_service->convertNewAndChangeBranch($branchData, $branchChangeMap);
        $insertBranchData = $this->reserve_change_service->mapBranchReserveDate($insertBranchData, $reserveDate);

        // 在庫を元に戻す
        $res = $this->reserveIncreaseRoomStock($reserveId, $hotel, $branchChangeMap);

        // 既存のreservation_branchesの中で、キャンセルのものをステータス更新する
        $this->reserve_change_service->updateCancelBranch($reserveId, $branchChangeMap);

        // 既存のreservation_branchesの中で、変更のものを論理削除する
        // 既存のreservation_plansを論理削除する
        $this->reserve_change_service->deleteChangeBranch($reserveId, $branchChangeMap);

        // 新規のreservation_branchesを保存する
        // branchNumIdMapには変更のあった枝番号に対するreservation_branchesのidが格納される

        $branchNumIdMap = $this->browse_reserve_service->saveBranchData($insertBranchData);
        // 空の場合はbranches、plansレコードに変更がないためスルーする
        if (empty($branchNumIdMap)) return ['res' => true];

        // 新規のreservation_plansを保存する
        $result = $this->browse_reserve_service->savePlanRooms($planRooms, $reserveId, $hotel, $branchNumIdMap);

        return $result;
    }

    protected function convertUpdateReserveData($saveReserveData)
    {
        unset($saveReserveData['created_at'],
            $saveReserveData['reservation_date'],
            $saveReserveData['reservation_code'],
            $saveReserveData['payment_method'],
            $saveReserveData['verify_token']);
        $saveReserveData['change_date_time'] = Carbon::now()->format('Y-m-d H:i:s');
        $saveReserveData['updated_at'] = now();

        return $saveReserveData;
    }

    public function reserveIncreaseRoomStock($reserveId, $hotel, $branchChangeMap=NULL)
    {
        $increaseRoomStockData = $this->reserve_service->getIncreaseStockData($reserveId, $branchChangeMap);
        $result = $this->reserve_service->reserveIncreaseRoomStock($increaseRoomStockData, $hotel->client_id, $hotel->id);
        if (!$result['res']) {
            return ['res' => false, 'error' => '予期せぬエラーが発生しました。'];
        }

        return ['res' => true];
    }

    // 予約完了時に確認メール送信
    protected function sendConfirmMail($verifyToken, $saveReserveData, $post, $hotel, $bookingData, $planRooms, $reserveId, $stayType)
    {
        try {
            $userShowUrl = route('user.booking_show', $verifyToken);
            $res = dispatch_now(
                        new ReserveConfirmJob(
                            $userShowUrl,
                            $saveReserveData['email'],
                            $post['reservation_code'], $saveReserveData['accommodation_price'],
                            $saveReserveData['payment_method'], $hotel, $bookingData['selected_rooms']['plan_id'],
                            $planRooms, $post['checkin_date'], $post['checkout_date'],
                            $reserveId, $stayType));

                            // ->onQueue('mail-job')

            return $userShowUrl;
        } catch (\Exception $e)  {
            return $userShowUrl;
        }
    }

    protected function sendChangeMail($verifyToken, $saveReserveData, $post, $hotel, $bookingData, $planRooms, $reserveId, $stayType, $paymentMethod, $reservation)
    {
        try {
            $userShowUrl = route('user.booking_show', $verifyToken);
            $res = dispatch_now(
                        new ReserveChangeJob(
                            $userShowUrl,
                            $saveReserveData['email'],
                            $reservation->reservation_code, $saveReserveData['accommodation_price'],
                            $paymentMethod, $hotel, $bookingData['selected_rooms']['plan_id'],
                            $planRooms, $post['checkin_date'], $post['checkout_date'],
                            $reserveId, $stayType));

                            // ->onQueue('mail-job')

            return $userShowUrl;
        } catch (\Exception $e)  {
            return $userShowUrl;
        }
    }

    public function increaseReserveBlockByCancel(
        \App\Models\Reservation $reservation
    ){
        $res = $this->other_reserve_service->increaseReserveBlockByReservation($reservation);

        return $res;
    }
}
