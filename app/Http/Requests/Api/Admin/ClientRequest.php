<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ClientRequest extends FormRequest
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
                'company_name' => 'required|required_not_empty|max:40',
                'tel' => 'nullable|digits_between:10,11|regex:/^\d{10,11}$/',
                'email' => 'required|required_not_empty|email|unique:clients,email,NULL,email',
                'person_in_charge' => 'required|required_not_empty|max:40',
            ];
        } else {
            //edit
            return [
                'company_name' => 'required|required_not_empty|max:40',
                'tel' => 'nullable|digits_between:10,11|regex:/^\d{10,11}$/',
                'email' => 'required|required_not_empty|email|unique:clients,email,' . $id . ',id',
                'person_in_charge' => 'required|required_not_empty|max:40',
            ];
        }
    }

    public function attributes()
    {
        return [
            'company_name' => '会社名',
            'tel' => '電話番号',
            'email' => 'メールアドレス',
            'person_in_charge' => '担当者様氏名',
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
