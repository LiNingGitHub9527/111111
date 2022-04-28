<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Hotel;
use Illuminate\Validation\Rule;

class HotelRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $id = request()->get('id');
        $rules = [
            'name' => 'required|required_not_empty|max:40',
            'address' => 'required|required_not_empty',
            'email' => 'required|required_not_empty|email',
            'person_in_charge' => 'required|required_not_empty|max:40',
            'tel' => 'required|required_not_empty|digits_between:10,11|regex:/^\d{10,11}$/',
            'checkin_start' => 'required|required_not_empty',
            'checkin_end' => 'required|required_not_empty',
            'checkout_end' => 'required|required_not_empty',
            'tema_login_id' => [
                'max:40',  
                Rule::unique('hotels', 'tema_login_id')->where(function ($query) use ($id) {
                    if ($query->where('id', '!=', $id) && $query->where('tema_login_id', '!=', null)) {
                        return $query;
                    }
                }),
            ],
            'tema_login_password' => 'max:128',
        ];

        
        $hotelId = $this->get('hotel_id');
        if (!empty($hotelId)) {
            $hotel = Hotel::find($hotelId);

            if (!empty($hotel) && $hotel->business_type == 1) {
                $hotelKidsPolicies = $this->get('hotelKidsPolicies');
                foreach ($hotelKidsPolicies as $key => $hotelKidsPolicy) {
                    $rules['hotelKidsPolicies.' . $key . '.age_start'] = 'required|required_not_empty|Integer|min:0';
                    $rules['hotelKidsPolicies.' . $key . '.age_end'] = 'required|required_not_empty|Integer|min:0|gt:hotelKidsPolicies.' . $key . '.age_start';
                    $rateType = $hotelKidsPolicy['rate_type'];
                    if ($rateType == 1) {
                        $rules['hotelKidsPolicies.' . $key . '.fixed_amount'] = 'required|required_not_empty|Integer|min:0';
                    }
                    if ($rateType == 2) {
                        $rules['hotelKidsPolicies.' . $key . '.rate'] = 'required|required_not_empty|Integer|min:0';
                    }
                    $isAllRoom = $hotelKidsPolicy['is_all_room'];
                    if ($isAllRoom != 1) {
                        $rules['hotelKidsPolicies.' . $key . '.room_type_ids'] = 'required|required_not_empty';
                    }
                }
            }
        }

        $hotelNotes = $this->get('hotelNotes');
        if (!empty($hotelNotes)) {
            $rules['hotelNotes.*.title'] = 'required|required_not_empty';
            $rules['hotelNotes.*.content'] = 'required|required_not_empty';
        }
        
        return $rules;
    }

    public function attributes()
    {
        $attributes = [
            'name' => 'ホテル名',
            'address' => '住所',
            'email' => 'メールアドレス',
            'person_in_charge' => '担当者様氏名',
            'tel' => '電話番号',
            'tema_login_id' => 'ログイン ID(Temairazu)',
            'tema_login_password' => 'ログインパスワード(Temairazu)'
        ];
        $hotelKidsPolicies = $this->get('hotelKidsPolicies');
        foreach ($hotelKidsPolicies as $key => $hotelKidsPolicy) {
            $attributes['hotelKidsPolicies.' . $key . '.age_start'] = '开始年龄';
            $attributes['hotelKidsPolicies.' . $key . '.age_end'] = '结束年龄';
            $attributes['hotelKidsPolicies.' . $key . '.fixed_amount'] = '定額料金';
            $attributes['hotelKidsPolicies.' . $key . '.rate'] = '大人料金の%の値';
            $attributes['hotelKidsPolicies.' . $key . '.room_type_ids'] = '部屋タイプ';
        }
        $attributes['hotelNotes.*.title'] = 'タイトル';
        $attributes['hotelNotes.*.content'] = '内容';
        return $attributes;
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
