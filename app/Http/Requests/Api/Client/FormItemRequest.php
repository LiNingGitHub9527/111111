<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class FormItemRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'hotel_id' => 'required|required_not_empty',
            'name' => 'required|required_not_empty|max:40',
        ];
        $itemType = $this->get('item_type');
        if (in_array($itemType, [3, 5, 6])) {
            $rules['option_default'] = 'required|required_not_empty';
            $options = $this->get('options');
            if (empty($options) || count($options) == 0) {
                $rules['options'] = 'required|required_not_empty';
            }
            $rules['options.*'] = 'required|required_not_empty';
        }
        return $rules;
    }

    public function attributes()
    {
        $attrs = [
            'name' => 'formItem名',
            'options.*' => 'option',
        ];
        return $attrs;
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
