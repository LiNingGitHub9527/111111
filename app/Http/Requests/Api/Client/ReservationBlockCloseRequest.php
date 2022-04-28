<?php

namespace App\Http\Requests\Api\Client;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReservationBlockCloseRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $today = Carbon::now()->format('Y-m-d');
        $rules = [
            'reservation_block_ids' => 'required|array',
            'reservation_block_ids.*' => 'integer|min:1',
            'is_closed' => 'required|integer|min:0|max:1'
        ];

        return $rules;
    }

    public function attributes()
    {
        $attributes = [
            'reservation_block_ids' => '予約枠ID',
            'reservation_block_ids.*' => '予約枠ID',
            'is_closed' => '手仕舞いフラグ'
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
