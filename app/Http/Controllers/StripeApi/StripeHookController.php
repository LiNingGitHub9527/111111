<?php

namespace App\Http\Controllers\StripeApi;

use App\Http\Requests\User\ReservePaymentRequest;
use App\Models\Hotel;
use App\Models\ReservationCancelPolicy;
use App\Models\ReservationCaptured;
use Carbon\Carbon;
use DB;

class StripeHookController extends ApiBaseController
{
    public function __construct()
    {
        $this->stripe_service = app()->make('StripeService');
        $this->reserve_service = app()->make('ReserveService');
        $this->canpoli_service = app()->make('CancelPolicyService');
        $this->receipt_service = app()->make('ReceiptService');
        $this->successUpdate = ['payment_status' => 1];
        $this->failUpdate = ['reservation_status' => 1, 'cancel_date_time' => now(), 'cancel_fee' => 0];
    }

    // オーソリ期限切れの際のWebhookエンドポイント
    // オーソリを再作成する
    public function chargeExpired(ReservePaymentRequest $request)
    {
        $charge = $request->data;
        $paymentId = $charge['object']['id'];

        // ペイメントのidから予約を取得する
        $reservation = $this->reserve_service->getReserveByPayId($paymentId);
        $request->isReserve($reservation);
        $hotel = Hotel::find($reservation->hotel_id);

        // 予約の決済ステータスが未決済かチェック
        $check = $request->checkPaymentStatus($reservation);

        $now = Carbon::now();

        // 未決済かつ事前決済の予約、でなければオーソリをキャンセルする
        if (!$check) {
            $message = $reservation->name . 'さんの' . $hotel->name . 'へ' . $reservation->checkin_time . 'にチェックイン予定の予約は、' . $now->format('Y-m-d') . 'に決済済みか現地決済のためオーソリをキャンセルしました。';
            return $this->success(true, $message);
        }

        // オーソリの再作成
        $authoryRes = $this->stripe_service->manageDoAuthoryPayByCId($reservation->id, $reservation->stripe_customer_id, $reservation->accommodation_price, $charge['object']['description']);

        // オーソリ作成に失敗したら、予約をキャンセルする
        if (!$authoryRes['res']) {
            // 無料キャンセル期間外の時点でオーソリはキャプチャされるため、
            // まだオーソリ段階なのであれば、タッチの差で上記のjobよりStripeからの通知が早かったと言える。そのためここでも同様にキャンセルしておく
            // $this->reserve_service->cancelFailPayBook($reservation, $this->failUpdate); // reservation_statusをキャンセルで更新する
            // $this->reserve_service->sendFailPayMail($reservation); // 決済失敗により、予約がキャンセルされたことを通知するメールを送信
            $message = $reservation->name . 'さんの' . $hotel->name . 'へ' . $reservation->checkin_time . 'にチェックイン予定の予約は、' . $now->format('Y-m-d') . 'にオーソリ期限切れのタイミングで登録されたクレジットカードでのオーソリ再作成が失敗したためキャンセルされました。';
            return $this->success(true, $message);
        }

        $chargeId = $authoryRes['data']->id;
        // 予約のstripe_payment_idを更新する
        $reservation->update(['stripe_payment_id' => $chargeId]);

        $refundData = [];
        $this->stripe_service->manageFullRefund($reservation->id, $paymentId, $reservation->accommodation_price, $refundData);

        $canCapturedDate = Carbon::parse($reservation->checkin_time)->addDays(3)->startOfDay();
        if ($now->gt($canCapturedDate)) {
            $reservationCaptured = new ReservationCaptured([
                'reservation_id' => $reservation->id,
                'payment_status' => 2
            ]);
            $reservationCaptured->save();

            $paymentData = [];
            $captureRes = $this->stripe_service->manageChargeAuthoryById($reservation->id, $reservation->stripe_payment_id, null, $paymentData);

            $reservationCaptured->update([
                'payment_amount' => $paymentData['payment_amount'] ?? 0,
                'amount_captured' => $paymentData['amount_captured'] ?? 0,
                'stripe_payment_id' => $reservation->stripe_payment_id,
                'captured_status' => $paymentData['captured_status'] ?? 0,
                'payment_status' => $captureRes,
                'handle_date' => Carbon::now(),
                'payment_information' => $paymentData['message']
            ]);

            if ($captureRes) {
                // payment success
                // 決済ステータスを更新する
                $reservation->update($this->successUpdate);
                $this->receipt_service->send($reservation->stripe_payment_id);
                $message = $reservation->name . 'さんの' . $hotel->name . 'へ' . $reservation->checkin_time . 'にチェックイン予定の予約は、' . $now->format('Y-m-d') . 'にオーソリ期限切れのタイミングで無料キャンセル期間を過ぎたため決済をcaptureしました。';
            } else {
                // payment fail
                // 予約をキャンセルする
                // $this->reserve_service->cancelFailPayBook($reservation, $this->failUpdate); // reservation_statusをキャンセルで更新する
                $this->reserve_service->sendFailPayMail($reservation); // 決済失敗により、予約がキャンセルされたことを通知するメールを送信
                $message = $reservation->name . 'さんの' . $hotel->name . 'へ' . $reservation->checkin_time . 'にチェックイン予定の予約は、' . $now->format('Y-m-d') . 'にオーソリ期限切れのタイミングで、再作成したオーソリのcaptureに失敗したためキャンセルされました。';
            }
        } else {
            $message = $reservation->name . 'さんの' . $hotel->name . 'へ' . $reservation->checkin_time . 'にチェックイン予定の予約は、' . $now->format('Y-m-d') . 'にオーソリ期限切れのため、オーソリを再作成しました。';
        }

        return $this->success(true, $message);
    }
}   