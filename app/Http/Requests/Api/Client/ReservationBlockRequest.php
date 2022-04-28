<?php

namespace App\Http\Requests\Api\Client;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReservationBlockRequest extends FormRequest
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
            'room_type_id' => 'required|integer|min:1',
            'date' => 'required|required_not_empty|date|after_or_equal:' . $today,
            'person_capacity' => 'required|integer|min:1',
            'room_num' => 'required|integer|min:1',
            'price' => 'required|integer|min:0',
            'start_time' => 'required|required_not_empty|regex:/^[0-9][0-9]:[0-5][0-9]$/',
            'end_time' => 'required|required_not_empty|regex:/^[0-9][0-9]:[0-5][0-9]$/',
            'repeat_interval_type' => 'required|integer|min:0|max:2'
        ];

        if ($this->has('repeat_interval_type') && in_array($this->get('repeat_interval_type'), [1, 2])) {
            $rules['repeat_end_date'] = 'required|required_not_empty|date|after_or_equal:date';
        }

        return $rules;
    }

    public function attributes()
    {
        $attributes = [
            'room_type_id' => '部屋タイプID',
            'date' => '予約枠設定日',
            'person_capacity' => '定員',
            'room_num' => '部屋数',
            'price' => '料金',
            'start_time' => '開始時間',
            'end_time' => '終了時間',
            'repeat_interval_type' => '繰り返し設定',
            'repeat_end_date' => '繰り返し設定終了日',
        ];

        return $attributes;
    }

    /**
     * 定義済みバリデーションルールのエラーメッセージ取得
     *
     * @return array
     */
    public function messages()
    {
        return [
            'regex' => ':attributeには時刻を指定してください。',
        ];
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
