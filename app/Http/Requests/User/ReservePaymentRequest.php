<?php

namespace App\Http\Requests\User;

use Exception;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class ReservePaymentRequest extends FormRequest
{
    public function __construct()
    {
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    public function withValidator($validator)
    {
        if (!$this->webhookVerifySignature()) {
            throw (new HttpResponseException(response()->json([
                'code' => 1422,
                'status' => 'FAIL',
                'message' => 'シクレットキーの認証が失敗しました',
            ], 200)));
        }
        $charge = request()->data['object'];
        $validator->after(function ($validator) use ($charge) {
            if ($charge['captured'] == true || $charge['refunded'] == false) {
                throw (new HttpResponseException(response()->json([
                    'code' => 1422,
                    'status' => 'FAIL',
                    'message' => '既に決済済みか、キャンセル済みの予約です。',
                ], 200)));
            }
        });
    }

    public function isReserve($reservation)
    {
        if (empty($reservation)) {
            throw (new HttpResponseException(response()->json([
                'code' => 1422,
                'status' => 'FAIL',
                'message' => '予約が見つかりませんでした',
            ], 200)));
        }
    }

    public function checkPaymentStatus($reservation)
    {
        $paymentMethod = $reservation->payment_method;
        $paymentStatus = $reservation->payment_status;

        if ($paymentMethod != 1 || $paymentStatus == 1) {
            return false;
        }

        return true;
    }

    public function webhookVerifySignature(): bool
    {
        $endpoint_secret = config('prepay.webhook_expired.secret_key');
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        try {
            Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
            return true;
        } catch (UnexpectedValueException $e) {
            // Invalid payload
        } catch (SignatureVerificationException | Exception $e) {
            // Invalid signature
        }
        return false;
    }
}
