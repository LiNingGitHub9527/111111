<?php

namespace App\Http\Requests\Temairazu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Support\Temairazu\Http\Responese;
use App\Models\Hotel;
use App\Models\HotelRoomType;
use App\Models\Plan;

class AuthRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'LoginID' => 'required',
            'LoginPass' => 'required',
        ];
    }

    public function attributes()
    {
        return [
            'LoginID' => 'ログイン ID',
            'LoginPass' => 'ログインパスワード',
        ];
    }

    public function hotel()
    {
        $id = $this->get('LoginID');
        $password = $this->get('LoginPass');
        $hotel = Hotel::where('tema_login_id', $id)->where('tema_login_password', $password)->first();
        if (empty($hotel)) {
            $message = 'ログインIDまたはパスワードが違います。';
            throw (new HttpResponseException(Responese::error($message)));
        }

        return $hotel;
    }

    public function room($hotelId)
    {
        $roomCode = $this->get('HeyaID');
        $room = HotelRoomType::where('hotel_id', $hotelId)->where('id', $roomCode)->first();
        if (empty($room)) {
            $message = '[404]' . $roomCode . 'リクエストされたお部屋が見つかりませんでした。';
            throw (new HttpResponseException(Responese::error($message)));
        }

        return $room;
    }

    public function plan($hotelId)
    {
        $planCode = $this->get('PlanID');
        $plan = Plan::where('hotel_id', $hotelId)->where('id', $planCode)->first();
        if (empty($plan)) {
            $message = '[404]' . $planCode . 'リクエストされたプランが見つかりませんでした。';
            throw (new HttpResponseException(Responese::error($message)));
        }

        return $plan;
    }

    protected function failedValidation(Validator $validator)
    {
        $message = $validator->errors()->first();
        throw (new HttpResponseException(Responese::error($message)));
    }
}
