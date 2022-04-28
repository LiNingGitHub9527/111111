<?php

namespace App\Http\Requests\PmsApi\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class SaveReserveRequest extends FormRequest
{
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
            'checkin_date' => 'required|after:yesterday',
            'checkout_date' => 'required|after:checkin_date',
            'checkin_time' => 'required',
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

    protected function failedValidation(Validator $validator)
    {
        throw (new HttpResponseException(response()->json([
            'code' => 1422,
            'status'  => 'FAIL',
            'message' => $validator->errors(),
        ], 200)));
    }
}
