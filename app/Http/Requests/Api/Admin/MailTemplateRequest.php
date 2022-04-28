<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class MailTemplateRequest extends FormRequest
{

    public function rules()
    {
        return [
            'subject' => 'required|required_not_empty|max:40',
            'body' => 'required|required_not_empty',
            'type' => 'required|required_not_empty|numeric:gt:0',
            'bcc' => 'nullable|email'
        ];
    }

    public function attributes()
    {
        return [
            "subject" => "件名",
            "body" => "本文",
            "bcc" => "BCC"
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
