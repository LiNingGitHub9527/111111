<?php

namespace App\Http\Requests\Api\Admin;

use App\Rules\RequiredReplaceSpace;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RatePlanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|required_not_empty|max:40',
            'fee' => 'required|required_not_empty|integer',
        ];
    }

    public function attributes()
    {
        return [
            'name' => '名前',
            'fee' => '金額（月額）',
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
