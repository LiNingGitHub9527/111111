<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class HotelHardRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [];
        $id = $this->get('id');
        if (empty($id)) {
            $rules['hotel_id'] = 'required|required_not_empty';
        }
        $originalHotelHardCategories = $this->get('originalHotelHardCategories');
        foreach ($originalHotelHardCategories as $key => $originalHotelHardCategory) {
            foreach ($originalHotelHardCategory['originalHotelHardItems'] as $k => $originalHotelHardItem) {
                if ($originalHotelHardItem['is_all_room'] == 0) {
                    $rules['originalHotelHardCategories.' . $key . '.originalHotelHardItems.' . $k . '.room_type_ids'] = 'required|required_not_empty';
                }
            }
        }

        return $rules;
    }

    public function attributes()
    {
        $attrs = [];
        $originalHotelHardCategories = $this->get('originalHotelHardCategories');
        foreach ($originalHotelHardCategories as $key => $originalHotelHardCategory) {
            foreach ($originalHotelHardCategory['originalHotelHardItems'] as $k => $originalHotelHardItem) {
                if ($originalHotelHardItem['is_all_room'] == 0) {
                    $attrs['originalHotelHardCategories.' . $key . '.originalHotelHardItems.' . $k . '.room_type_ids'] = '部屋タイプ';
                }
            }
        }
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
