<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
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
        $id = $this->get('id');
        if (empty($id)) {
            //create
            return [
                'name' => 'required|required_not_empty|max:40',
                'address' => 'required|required_not_empty',
                'email' => 'required|required_not_empty|email',
                'person_in_charge' => 'required|required_not_empty|max:40',
                'tel' => 'required|required_not_empty|digits_between:10,11|regex:/^\d{10,11}$/',
                'tema_login_id' => 'nullable|max:40|unique:hotels,tema_login_id,NULL,tema_login_id',
                'tema_login_password' => 'nullable|max:128',
                'business_type' => 'required',
                'bank_code' => 'required|regex:/^\d+$/',
                'branch_code' => 'required|regex:/^\d+$/',
                'deposit_type' => ['required','regex:/[1|2|4]/'],
                'account_number' => 'required|regex:/^\d+$/',
                'recipient_name' => 'required',

            ];
        } else {
            //edit
            return [
                'name' => 'required|required_not_empty|max:40',
                'address' => 'required|required_not_empty',
                'email' => 'required|required_not_empty|email',
                'person_in_charge' => 'required|required_not_empty|max:40',
                'tel' => 'required|digits_between:10,11|regex:/^\d{10,11}$/',
                'tema_login_id' => 'nullable|max:40|unique:hotels,tema_login_id,' . $id . ',id',
                'tema_login_password' => 'nullable|max:128',
                'business_type' => 'required',
                'bank_code' => 'required|regex:/^\d+$/',
                'branch_code' => 'required|regex:/^\d+$/',
                'deposit_type' => ['required','regex:/[1|2|4]/'],
                'account_number' => 'required|regex:/^\d+$/',
                'recipient_name' => 'required',
            ];
        }

    }

    public function attributes()
    {
        return [
            'name' => 'ホテル名',
            'address' => '住所',
            'email' => 'メールアドレス',
            'person_in_charge' => '担当者様氏名',
            'tel' => '電話番号',
            'tema_login_id' => 'ログイン ID(Temairazu)',
            'tema_login_password' => 'ログインパスワード(Temairazu)',
            'business_type' => '業界',
            'bank_code' => "銀行コード",
            'branch_code' => " 支店コード",
            'account_number' => "口座番号",
            'recipient_name' => " 受取人名",
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
