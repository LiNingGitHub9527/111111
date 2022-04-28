<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CancelPolicyRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'name' => 'required|required_not_empty|max:40',
            'cancel_charge_rate' => 'required|required_not_empty|Integer',
        ];
        $id = $this->get('id');
        if (empty($id)) {
            $rules['hotel_id'] = 'required|required_not_empty';
        }

        return $rules;
    }

    public function attributes()
    {
        return [
            'name' => 'キャンセルポリシー名'
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
