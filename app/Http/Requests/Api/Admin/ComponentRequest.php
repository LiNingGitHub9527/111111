<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class ComponentRequest extends FormRequest
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
            'html' => 'required|required_not_empty',
            'data.desktop.width' => 'exclude_unless:type,2|required|numeric|gt:0',
            'data.desktop.height' => 'exclude_unless:type,2|required|numeric|gt:0',
            'data.tablet.width' => 'exclude_unless:type,2|required|numeric|gt:0',
            'data.tablet.height' => 'exclude_unless:type,2|required|numeric|gt:0',
            'data.mobile.width' => 'exclude_unless:type,2|required|numeric|gt:0',
            'data.mobile.height' => 'exclude_unless:type,2|required|numeric|gt:0',
            'business_types' => 'required',
            'sort_num' => 'required|integer|min:0',
        ];
    }

    public function attributes()
    {
        return [
            'name' => '名前',
            'html' => 'HTML',
            'data.desktop.width' => 'デスクトップの幅',
            'data.desktop.height' => 'デスクトップの高さ',
            'data.tablet.width' => 'タブレットの幅',
            'data.tablet.height' => 'タブレットの高さ',
            'data.mobile.width' => 'モバイルの幅',
            'data.mobile.height' => 'モバイルの高さ',
            'business_types' => '業界',
            'sort_num' => 'ソート',
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
