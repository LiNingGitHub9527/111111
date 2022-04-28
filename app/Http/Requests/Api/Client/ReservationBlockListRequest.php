<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReservationBlockListRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'room_type_id' => 'array',
            'room_type_id.*' => 'integer|min:1',
            'start_date' => 'required|required_not_empty|date',
            'end_date' => 'required|required_not_empty|date|after_or_equal:start_date',
        ];

        return $rules;
    }

    public function attributes()
    {
        $attributes = [
            'room_type_id' => '部屋タイプID',
            'room_type_id.*' => '部屋タイプID',
            'start_date' => '予約枠取得の開始日',
            'end_date' => '予約枠取得の終了日',
        ];

        return $attributes;
    }

    protected function failedValidation(Validator $validator)
    {
        throw (new HttpResponseException(response()->json([
            'code' => 1422,
            'status' => 'FAIL',
            'message' => $validator->errors(),
        ], 200)));
    }
}
