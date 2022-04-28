<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

use Carbon\Carbon;

class UpdateStayBookingRequest extends FormRequest
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
        return [
            'last_name' => 'required|max:50',
            'first_name' => 'required|max:50',
            'last_name_kana' => 'required|max:50',
            'first_name_kana' => 'required|max:50',
            'email' => 'required|email|max:64',
            'email_confirm' => 'required|email|max:64|same:email',
            'address' => 'required|max:255',
            'tel' => 'required|between:10,11',
            'checkin_time' => 'required|date_format:"H:i',
            'remarks' => 'max:1000',
        ];
    }

    public function attributes()
    {
        return [
            'last_name' => '氏名(名)',
            'first_name' => '氏名(姓)',
            'email' => 'メールアドレス',
            'email_confirm' => '確認用メールアドレス',
            'address1' => '住所',
            'address2' => '番地',
            'tel' => '電話番号',
            'checkin_time' => 'チェックイン予定時間',
            'remarks' => '特別リクエスト',
        ];
    }

    // $validator->errors()->add('client_id', 'クライアントは、必ず指定してください。');

    public function withValidator($validator)
    {
        $canPoliService = app()->make('CancelPolicyService');

        $validator->after(function($validator) use ($canPoliService){
            $bookingConfirm = session()->get('booking_confirm', []);
            if (!empty($bookingConfirm['change_info'])) {
                $checkinDate = Carbon::parse($bookingConfirm['reservation']['checkin_start'])->format('Y-m-d');
                $canPoli = $bookingConfirm['reservation']['reservation_cancel_policy'];
                $isFreeCancel = $canPoliService->checkFreeCancelByNow(NULL, $checkinDate, json_decode(json_encode($canPoli)));
                if (!$isFreeCancel) {
                    $validator->errors()->add('error', '無料キャンセル期間でないため、ご予約を変更できません。');
                }
            } else {
                $validator->errors()->add('error', '操作がされないまま一定時間が経過しました。再読み込みをしてください。');
            }
        });
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
