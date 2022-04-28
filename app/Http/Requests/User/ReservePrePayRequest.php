<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ReservePrePayRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'last_name' => 'required|max:50',
            'first_name' => 'required|max:50',
            'last_name_kana' => 'required|max:50',
            'first_name_kana' => 'required|max:50',
            'email' => 'required|email|max:64',
            'email_confirm' => 'required|email|max:64|same:email',
            'address1' => 'required|max:255',
            'address2' => 'required|max:255',
            'tel' => 'required|between:10,11',
            'checkin_time' => 'required|date_format:"H:i',
            'remarks' => 'max:1000',
            'card_number' => 'required|check_numeric|digits_between:13,17',
            'expiration_month' => 'required',
            'expiration_year' => 'required',
            'cvc' => 'required|check_numeric|digits_between:1,4',
        ];
    }

    public function attributes()
    {
        return [
            'last_name' => '氏名(名)',
            'first_name' => '氏名(姓)',
            'email' => 'メールアドレス',
            'email_confirm' => '確認用メールアドレス',
            'address1' => '住所',
            'address2' => '番地',
            'tel' => '電話番号',
            'checkin_time' => 'チェックイン予定時間',
            'remarks' => '特別リクエスト',
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
