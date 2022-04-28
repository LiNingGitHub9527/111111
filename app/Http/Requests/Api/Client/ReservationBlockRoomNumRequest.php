<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReservationBlockRoomNumRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'room_type_id' => 'integer|min:1',
            'num' => 'required|integer|not_in:0',
            'date' => 'array',
            'date.*' => 'date_format:Y-m-d',
        ];

        return $rules;
    }

    public function attributes()
    {
        $attributes = [
            'room_type_id' => '部屋タイプID',
            'num' => '部屋数',
            'date' => '日付',
            'date.*' => '日付',
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
