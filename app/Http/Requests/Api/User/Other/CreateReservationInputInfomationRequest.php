<?php

namespace App\Http\Requests\Api\User\Other;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateReservationInputInfomationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'selected_blocks.*.reservation_block_token' => 'required|string',
            'selected_blocks.*.person_num' => 'required|array',
            'selected_blocks.*.person_num.*' => 'integer|min:1',
        ];

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'selected_blocks.*.reservation_block_token' => '予約枠のトークン',
            'selected_blocks.*.person_num' => '部屋ごとの利用人数',
            'selected_blocks.*.person_num.*' => '部屋ごとの利用人数',
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
