<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class PasswordRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if(request()->route()->named('api.forgot.change')){
            $rules = [
                'newpwd' => 'required|between:6,20|confirmed',
                'newpwd_confirmation' => 'required|between:6,20',
            ];
        }else{
            $rules = [
                'oldpwd' => 'required|between:6,20',
                'newpwd' => 'required|between:6,20｜different:oldpwd|confirmed',
                'newpwd_confirmation' => 'required|between:6,20',
            ];
        }
        return $rules;
    }

    public function attributes()
    {
        return [
            'oldpwd' => '現在のパスワード',
            'newpwd' => '新しいパスワード',
            'newpwd_confirmation' => '新しいパスワード(確認用)',
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
