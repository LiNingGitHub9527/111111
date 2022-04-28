<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmStayBookingRequest extends FormRequest
{
    public function __construct()
    {
        $this->dayuse_service = app()->make('LineDayuseReserveService');
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
        $rules = [
            'last_name' => 'required|max:50',
            'first_name' => 'required|max:50',
            'last_name_kana' => 'required|max:50',
            'first_name_kana' => 'required|max:50',
            'email' => 'required|email|max:64',
            'email_confirm' => 'required|email|max:64|same:email',
            'address1' => 'required|max:255',
            'address2' => 'required|max:255',
            'tel' => 'required|between:10,11',
            'checkin_time' => 'required|date_format:"H:i',
            'remarks' => 'max:1000',
        ];
        $payment_method = request()->get('payment_method');
        if ($payment_method == 1) {
            $rules['card_number'] = 'required|check_numeric|digits_between:13,17';
            $rules['expiration_month'] = 'required';
            $rules['expiration_year'] = 'required';
            $rules['cvc'] = 'required|check_numeric|digits_between:1,4';
        }

        return $rules;
    }

    public function attributes()
    {
        $rules = [
            'last_name' => '氏名(名)',
            'first_name' => '氏名(姓)',
            'email' => 'メールアドレス',
            'email_confirm' => '確認用メールアドレス',
            'address1' => '住所',
            'address2' => '番地',
            'tel' => '電話番号',
            'checkin_time' => 'チェックイン予定時間',
            'remarks' => '特別リクエスト',
            'card_number' => 'カード番号',
            'expiration_month' => '有効期限',
            'expiration_year' => '有効期限',
            'cvc' => 'セキュリティコード'
        ];

        return $rules;
    }

    public function checkMinStayTime($planMinStayTime, $postStayTime)
    {
        $check = $this->dayuse_service->checkMinStayTime($planMinStayTime, $postStayTime);
        if (!$check) {
            $message = 'このプランの最低滞在時間の' . $planMinStayTime . '時間を下回っています';
            return ['res' => false, 'message' => $message];
        } else {
            return ['res' => true];
        }
    }

    public function checkCheckinTime($checkinStart, $lastCheckin, $checkinDateTime)
    {
        $check = $this->dayuse_service->checkCheckinTime($checkinStart, $lastCheckin, $checkinDateTime);
        if (!$check) {
            $message = 'チェックイン時間が' . $checkinStart . 'から' . $lastCheckin . 'の範囲外です';
            return ['res' => false, 'message' => $message];
        } else {
            return ['res' => true];
        }
    }

    public function checkLastCheckoutTime($postCheckoutDateTime, $planLastCheckout)
    {
        $check = $this->dayuse_service->checkLastCheckoutTime($postCheckoutDateTime, $planLastCheckout);
        if (!$check) {
            $message = 'このプランの最終チェックアウト受付は' . $planLastCheckout . 'です';
            return ['res' => false, 'message' => $message];
        } else {
            return ['res' => true];
        }
    }
}
