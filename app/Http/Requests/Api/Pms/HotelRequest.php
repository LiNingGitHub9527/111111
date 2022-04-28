<?php

namespace App\Http\Requests\Api\Pms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class HotelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'logo_img' => 'nullable',
            'client_id' => 'required|integer|min:1',
            'pms_base_id' => 'required|integer|min:1',
            'bussiness_type' => 'required|integer|min:1'
        ];
    }

    public function attributes()
    {
        return [
            'name' => 'ホテル名',
            'logo_img' => '画像',
            'client_id' => 'クライアントのID',
            'pms_base_id' => 'PMSのベースID',
            'bussiness_type' => '業種'
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
