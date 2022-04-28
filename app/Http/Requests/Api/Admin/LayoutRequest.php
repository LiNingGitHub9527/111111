<?php

namespace App\Http\Requests\Api\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LayoutRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        if(empty($this->request->get('id'))){
            return [
                'name' => 'required|required_not_empty|max:40',
                'component_id' => 'required|required_not_empty',
                'css_file' => 'nullable|file|max:5120',
                'js_file' => 'nullable|file|max:5120',
                'preview_image' => 'required',
                'html' => 'required|required_not_empty',
                'sort_num' => 'required|integer|min:0'
            ];
        }else{
            return [
                'name' => 'required|required_not_empty|max:40',
                'component_id' => 'required|required_not_empty',
                'css_file' => 'nullable|file|max:5120',
                'js_file' => 'nullable|file|max:5120',
                'html' => 'required|required_not_empty',
                'sort_num' => 'required|integer|min:0'
            ];
        }

    }

    public function attributes()
    {
        return [
            'name' => '名前',
            'component_id' => 'コンポーネント選択',
            'css_file' => 'CSSファイルを選択する',
            'js_file' => 'JSファイルを選択する',
            'preview_image' => 'プレビュー画像	',
            'html' => 'HTML',
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
