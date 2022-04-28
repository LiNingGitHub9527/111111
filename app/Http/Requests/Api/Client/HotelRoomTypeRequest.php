<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Hotel;

class HotelRoomTypeRequest extends FormRequest
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
        $rules['name'] = 'required|required_not_empty';
        $rules['adult_num'] = 'required|required_not_empty|Integer|min:1';

        $hotelId = $this->get('hotel_id');
        if (!empty($hotelId)) {
            $hotel = Hotel::find($hotelId);

            if (!empty($hotel)) {
                if ($hotel->business_type == 1) {
                    $rules['child_num'] = 'Integer|min:0';
                    $rules['room_size'] = 'required|required_not_empty|Integer|min:1';
                    $rules['hotelRoomTypeBeds.*.bed_size'] = 'required|required_not_empty|Integer|min:1';
                    $rules['hotelRoomTypeBeds.*.bed_num'] = 'required|required_not_empty|Integer|min:1';
                } else {
                    $rules['room_num'] = 'Integer|min:0';
                }
            }
        }

        $rules['hotelRoomTypeImages'] = 'required';
        return $rules;
    }

    public function attributes()
    {
        $attrs = [];
        $attrs['name'] = '部屋タイプ名';
        $attrs['adult_num'] = '大人人数';
        $attrs['child_num'] = '子供人数';
        $attrs['room_size'] = '平米数';
        $attrs['room_num'] = '部屋数';
        $attrs['hotelRoomTypeImages'] = '画像';
        $attrs['hotelRoomTypeBeds.*.bed_size'] = 'ベッドサイズ';
        $attrs['hotelRoomTypeBeds.*.bed_num'] = '台数';

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
