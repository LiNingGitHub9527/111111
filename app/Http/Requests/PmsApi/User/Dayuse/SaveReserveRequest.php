<?php

namespace App\Http\Requests\PmsApi\User\Dayuse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class SaveReserveRequest extends FormRequest
{
    public function __construct()
    {
        $this->line_dayuse_service = app()->make('LineDayuseReserveService');
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
            'client_id' => 'required',
            'hotel_id' => 'required',
            'last_name' => 'required',
            'first_name' => 'required',
            'checkin_date' => 'required',
            'checkin_date_time' => 'required',
            'checkout_date_time' => 'required|after:checkin_date_time',
            'stay_time' => 'required|integer',
            'email' => 'required|email:strict,dns,spoof|max:64',
            'tel' => 'nullable|digits_between:10,11|regex:/^\d{10,11}$/',
            'payment_method' => 'required|integer|digits:1',
            'plan_id' => 'required|integer',
            'adult_num' => 'required|integer',
            'child_num' => 'nullable|integer',
            'accommodation_price' => 'required|integer',
            'reservation_date_time' => 'required|date_format:"Y-m-d H:i:s"',
        ];
    }

    public function checkMinStayTime($planMinStayTime, $postStayTime)
    {
        $check = $this->line_dayuse_service->checkMinStayTime($planMinStayTime, $postStayTime);
        if (!$check) {
            throw (new HttpResponseException(response()->json([
                'code' => 1422,
                'status'  => 'FAIL',
                'message' => 'このプランの最低滞在時間の' . $planMinStayTime . '時間を下回っています',
            ], 200)));
        }
    }

    public function checkCheckinTime($checkinStart, $lastCheckin, $checkinDateTime)
    {
        $check = $this->line_dayuse_service->checkCheckinTime($checkinStart, $lastCheckin, $checkinDateTime);
        if (!$check) {
            throw (new HttpResponseException(response()->json([
                'code' => 1422,
                'status'  => 'FAIL',
                'message' => 'チェックイン時間が' . $checkinStart . 'から' . $lastCheckin . 'の範囲外です',
            ], 200)));
        }
    }

    public function checkLastCheckoutTime($postCheckoutDateTime, $planLastCheckout)
    {
        $check = $this->line_dayuse_service->checkLastCheckoutTime($postCheckoutDateTime, $planLastCheckout);
        if (!$check) {
            throw (new HttpResponseException(response()->json([
                'code' => 1422,
                'status'  => 'FAIL',
                'message' => 'このプランの最終チェックアウト受付は' . $planLastCheckout . 'です',
            ], 200)));
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw (new HttpResponseException(response()->json([
            'code' => 1422,
            'status'  => 'FAIL',
            'message' => $validator->errors(),
        ], 200)));
    }
}
