<?php

namespace App\Services;

use App\Models\Hotel;
use App\Models\ReservationPayment;
use Carbon\Carbon;
use Stripe\Stripe;

class StripeService
{
    private $stripe_api_key;

    public function __construct()
    {
        if (config('app.env') != 'production') {
            $this->stripe_api_key = config('prepay.stripe_api_key.test');
        } else {
            $this->stripe_api_key = config('prepay.stripe_api_key.production');
        }

        Stripe::setApiKey($this->stripe_api_key);
    }

    public function bookingPrePay(&$cardData, $amount, $description, $name, $email, $isFreeCancel, $reserveId)
    {
        // カスタマIDを取得するためのToken
        $cardToken = $this->createCardToken($cardData['card_number'], $cardData['expiration_month'], $cardData['expiration_year'], $cardData['cvc']);
        if (!$cardToken['status']) {
            return ['res' => false, 'message' => 'カード情報が不正です'];
        }
        $customer = $this->createCustomerId($cardToken['token'], $name, $email);
        if (!$customer['status']) {
            return ['res' => false, 'message' => $customer['message']];
        }
        $cardData['stripe_customer_id'] = $customer['data']->id;
        // 決済を実行するためのToken
        $cardToken = $this->createCardToken($cardData['card_number'], $cardData['expiration_month'], $cardData['expiration_year'], $cardData['cvc']);
        $charge = $this->doAuthoryPay($cardToken['token'], $amount, $description, $reserveId);
        $paymentStatus = config('prepay.payment_status.authory');

        // if ($isFreeCancel) {
        //     // 無料キャンセル期間があれば、オーソリを作成する
        //     $charge = $this->doAuthoryPay($cardToken['token'], $amount, $description);
        //     $paymentStatus = config('prepay.payment_status.authory');
        // } else {
        //     // 無料キャンセル期間がなければ、決済を実行する
        //     $charge = $this->doPay($cardToken['token'], $amount, $description);
        //     $paymentStatus = config('prepay.payment_status.pay');
        // }

        if (!$charge['res']) {
            return $charge;
        }

        $cardData['payment_status'] = $paymentStatus;
        $cardData['stripe_payment_id'] = $charge['data']->id;
        // session()->put('booking.payment_status', $paymentStatus);
        // session()->put('booking.stripe_payment_id', $charge['data']->id);
        session()->put('booking.message', $charge['data']->status);
        session()->put('booking.is_free_cancel', $isFreeCancel);

        return ['res' => true];
    }

    public function createCardToken($cardNumber, $expirationMonth, $expirationYear, $cvc)
    {
        try {
            $card_token = \Stripe\Token::create([
                "card" => [
                    "number" => $cardNumber,
                    "exp_month" => $expirationMonth,
                    "exp_year" => $expirationYear,
                    "cvc" => $cvc,
                ]
            ]);

            return ['status' => true, 'token' => $card_token];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'カード情報が不正です'];
        }
    }

    public function createCustomerId($cardToken, $name, $email)
    {
        try {
            $customer = \Stripe\Customer::create([
                'source' => $cardToken,
                'email' => $email,
                'name' => $name,
            ]);
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'カード情報、もしくはメールアドレスが不正です'];
        }

        return ['status' => true, 'data' => $customer];
    }

    public function doPay($cardToken, $amount, $description)
    {
        try {
            $charge = \Stripe\Charge::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'source' => $cardToken,
                'description' => $description,
            ]);
            return ['res' => true, 'data' => $charge];
        } catch (\Exception $e) {
            return ['res' => false, 'message' => '決済に失敗しました。カード会社にお問い合わせください。'];
        }
    }

    public function doAuthoryPay($cardToken, $amount, $description, $reserveId)
    {
        $reservationPayment = $this->saveReservationPayment($reserveId, 1, 2, null, $amount);
        try {
            $charge = \Stripe\Charge::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'source' => $cardToken,
                'description' => $description,
                'capture' => false
            ]);

            $res = true;
            $message = $charge->status;
        } catch (\Exception $e) {
            $res = false;
            $message = $e->getMessage();
        }

        $reservationPayment->update([
            'status' => $res,
            'stripe_payment_id' => $charge->id ?? '',
            'message' => $message,
            'handle_time' => Carbon::now()
        ]);

        if ($res) {
            return ['res' => $res, 'data' => $charge];
        }
        return ['res' => $res, 'message' => $message];
    }

    public function doAuthoryPayByCId($cid, $amount, $description)
    {
        try {
            $charge = \Stripe\Charge::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'customer' => $cid,
                'description' => $description,
                'capture' => false
            ]);
            return ['res' => true, 'data' => $charge];
        } catch (\Exception $e) {
            return ['res' => false, 'message' => '決済に失敗しました。カード会社にお問い合わせください。'];
        }
    }

    public function makePrePayDesc($bookingData, $name, $email, $tel)
    {
        $hotelId = $bookingData['base_info']['hotel_id'];
        $hotel = Hotel::find($hotelId);
        $description = '【宿泊料金】　ホテル名:' . $hotel->name . ' / 予約者名:' . $name . ' / メールアドレス:' . $email . ' / 電話番号:' . $tel . ' / チェックイン日:' . $bookingData['base_info']['in_out_date'][0] . ' / 予約日:' . Carbon::now()->format('Y-m-d');

        return $description;
    }

    public function makeOtherPrePayDesc($bookingData, $name, $email, $tel, $checkinDate)
    {
        $hotelId = $bookingData['base_info']['hotel_id'];
        $hotel = Hotel::find($hotelId);
        $description = '【利用料金】　施設名:' . $hotel->name . ' / 予約者名:' . $name . ' / メールアドレス:' . $email . ' / 電話番号:' . $tel . ' / チェックイン日:' . $checkinDate . ' / 予約日:' . Carbon::now()->format('Y-m-d');

        return $description;
    }

    public function makeCancelDesc($reserve)
    {
        $hotelId = $reserve->hotel_id;
        $hotel = Hotel::find($hotelId);
        $description = '【キャンセル料金】　ホテル名:' . $hotel->name . '/ 予約ID: ' . $reserve->id . '/ 予約コード: ' . $reserve->reservation_code . ' / 予約者名:' . $reserve->name . ' / メールアドレス:' . $reserve->email . ' / 電話番号:' . $reserve->tel . ' / チェックイン日:' . $reserve->checkin_time . ' / 予約日:' . Carbon::now()->format('Y-m-d');

        return $description;
    }

    // 全額返金&オーソリキャンセルの場合のロジック
    public function fullRefund($paymentId)
    {
        try {
            $refund = \Stripe\Refund::create([
                'charge' => $paymentId,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // 一部返金のロジック
    public function partialRefund($accommodationPrice, $cancelFee, $paymentId)
    {
        $refundAmount = $accommodationPrice - $cancelFee;

        try {
            $refund = \Stripe\Refund::create([
                'charge' => $paymentId,
                'amount' => $refundAmount,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function doPayByCId($cid, $amount, $desc)
    {
        try {
            $charge = \Stripe\Charge::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'customer' => $cid,
                'description' => $desc,
            ]);
            return ['res' => true, 'data' => $charge];
        } catch (\Exception $e) {
            return ['res' => false, 'message' => '決済に失敗しました。カード会社にお問い合わせください。'];
        }
    }

    // オーソリをチャージする
    public function chargeAuthoryById($paymentId, $amount = NULL)
    {
        try {
            $charge = new \Stripe\Charge($paymentId);
            $charge = $charge->capture();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function manageFullRefund($reserveId, $paymentId, $amount, &$refundData)
    {
        $reservationPayment = $this->saveReservationPayment($reserveId, 2, 2, null, $amount);

        try {
            $refund = \Stripe\Refund::create([
                'charge' => $paymentId,
            ]);
            $res = true;
            $message = $refund->status;
            $refundData = [
                'refund_id' => $refund->id,
                'handle_date' => Carbon::parse($refund->created),
                'message' => $message
            ];
        } catch (\Exception $e) {
            $res = false;
            $message = $e->getMessage();
            $refundData = [
                'message' => $message
            ];
        }

        $reservationPayment->update([
            'status' => $res,
            'stripe_payment_id' => $paymentId,
            'refund_id' => $refund->id ?? null,
            'message' => $message,
            'handle_time' => Carbon::now()
        ]);

        return $res;
    }

    public function managePartialRefund($reserveId, $refundAmount, $paymentId, &$refundData = null)
    {
        $reservationPayment = $this->saveReservationPayment($reserveId, 5, 2, null, $refundAmount);

        try {
            $refund = \Stripe\Refund::create([
                'charge' => $paymentId,
                'amount' => $refundAmount,
            ]);
            $res = true;
            $message = $refund->status;
            $refundData = [
                'refund_id' => $refund->id,
                'handle_date' => Carbon::parse($refund->created),
                'message' => $message
            ];
        } catch (\Exception $e) {
            $res = false;
            $message = $e->getMessage();
            $refundData = [
                'message' => $message
            ];
        }

        $reservationPayment->update([
            'status' => $res,
            'refund_id' => $refund->id ?? null,
            'message' => $message,
            'stripe_payment_id' => $paymentId,
            'handle_time' => Carbon::now()
        ]);

        return $res;
    }

    public function manageDoPayByCid($reserveId, $cid, $amount, $desc)
    {
        $reservationPayment = $this->saveReservationPayment($reserveId, 3, 2, null, $amount);

        try {
            $charge = \Stripe\Charge::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'customer' => $cid,
                'description' => $desc,
            ]);
            $res = true;
            $message = $charge->status;
        } catch (\Exception $e) {
            $res = false;
            $message = $e->getMessage();
        }

        $reservationPayment->update([
            'status' => $res,
            'stripe_payment_id' => $charge->id ?? null,
            'message' => $message,
            'handle_time' => Carbon::now()
        ]);

        return $res;
    }

    public function auditAccounts($refundId, &$refundData)
    {
        try {
            $refund = \Stripe\Refund::retrieve([
                'id' => $refundId
            ]);
            $refundData = [
                'refund_id' => $refund->id,
                'amount' => $refund->amount,
                'status' => $refund->status
            ];
            return true;
        } catch (\Exception $e) {
            $refundData = [
                'message' => $e->getMessage()
            ];
            return false;
        }
    }

    public function manageChargeAuthoryById($reserveId, $paymentId, $amount = NULL, &$paymentData)
    {
        $reservationPayment = ReservationPayment::where('reservation_id', $reserveId)->where('type', 4)->first();
        if (empty($reservationPayment)) {
            $reservationPayment = $this->saveReservationPayment($reserveId, 4, 2, null, $amount);
        }

        try {
            $charge = new \Stripe\Charge($paymentId);
            $charge = $charge->capture();
            $paymentData = [
                'stripe_payment_id' => $charge->id,
                'payment_amount' => $charge->amount,
                'amount_captured' => $charge->amount_captured,
                'captured_status' => $charge->captured,
                'payment_date' => Carbon::parse($charge->created),
                'message' => $charge->status
            ];
            $res = true;
            $message = $charge->status;
        } catch (\Exception $e) {
            $res = false;
            $message = $e->getMessage();
            $paymentData = [
                'message' => $message
            ];
        }

        $reservationPayment->update([
            'status' => $res,
            'stripe_payment_id' => $charge->id ?? null,
            'message' => $message,
            'amount' => $charge->amount_captured ?? null,
            'handle_time' => Carbon::now()
        ]);

        return $res;
    }

    public function captureAuditAccounts($stripe_payment_id, &$paymentData)
    {
        try {
            $retrieveCharge = \Stripe\Charge::retrieve([
                'id' => $stripe_payment_id
            ]);
            $paymentData = [
                'stripe_payment_id' => $retrieveCharge->id,
                'payment_amount' => $retrieveCharge->amount,
                'amount_captured' => $retrieveCharge->amount_captured,
                'message' => $retrieveCharge->status
            ];
            return $retrieveCharge->captured;
        } catch (\Exception $e) {
            $paymentData = [
                'message' => $e->getMessage()
            ];
            return false;
        }
    }

    public function manageDoAuthoryPayByCId($reserveId, $cid, $amount, $description)
    {
        $reservationPayment = $this->saveReservationPayment($reserveId, 1, 2, null, $amount);
        try {
            $charge = \Stripe\Charge::create([
                'amount' => $amount,
                'currency' => 'jpy',
                'customer' => $cid,
                'description' => $description,
                'capture' => false
            ]);
            $res = true;
            $message = $charge->status;
        } catch (\Exception $e) {
            $res = false;
            $message = $e->getMessage();
            // $message = '決済に失敗しました。カード会社にお問い合わせください。';
        }

        $reservationPayment->update([
            'status' => $res,
            'message' => $message,
            'stripe_payment_id' => $charge->id ?? null,
            'handle_time' => Carbon::now()
        ]);
        if (!$res) {
            return ['res' => false, 'message' => $message];
        }

        return ['res' => true, 'data' => $charge];
    }

    public function saveReservationPayment($reservationId, $type, $status, $refundData, $amount)
    {
        $reservationPayment = new ReservationPayment([
            'reservation_id' => $reservationId,
            'type' => $type,
            'status' => $status,
            'message' => $refundData['message'] ?? null,
            'amount' => $amount,
            'stripe_payment_id' => $refundData['stripe_payment_id'] ?? null,
            'refund_id' => $refundData['refund_id'] ?? null,
            'handle_time' => Carbon::now()
        ]);
        $reservationPayment->save();

        return $reservationPayment;
    }

    public function manageMakePrePayDesc($reserve, $name, $email, $tel)
    {
        $hotelId = $reserve->hotel_id;
        $hotel = Hotel::find($hotelId);
        $description = '【宿泊料金】　ホテル名:' . $hotel->name . ' / 予約者名:' . $name . ' / メールアドレス:' . $email . ' / 電話番号:' . $tel . ' / チェックイン日:' . $reserve->checkin_time . ' / 予約日:' . Carbon::now()->format('Y-m-d');

        return $description;
    }

}