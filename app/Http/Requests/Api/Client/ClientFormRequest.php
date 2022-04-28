<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Models\Hotel;

class ClientFormRequest extends FormRequest
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
        ];
        $id = $this->get('id');
        if (empty($id)) {
            $rules['hotel_id'] = 'required|required_not_empty';
        }

        $isDeadline = request()->get('is_deadline');
        if ($isDeadline == 1) {
            $rules['deadline_start'] = 'required|required_not_empty|date';
            $rules['deadline_end'] = 'required|required_not_empty|date|after:deadline_start';
        }

        $isSalePeriod = request()->get('is_sale_period');
        if ($isSalePeriod == 1) {
            $rules['sale_period_start'] = 'required|required_not_empty|date';
            $rules['sale_period_end'] = 'required|required_not_empty|date|after:sale_period_start';
        }

        $rules['hotel_id'] = 'required|required_not_empty|Integer|min:1';
        $hotelId = $this->get('hotel_id');
        $hotel = Hotel::find($hotelId);

        if (!empty($hotel) && $hotel->business_type == 1) {
            $isPlan = request()->get('is_plan');
            if ($isPlan == 1) {
                $rules['plan_ids'] = 'required|required_not_empty';
            }
        }

        $isRoomType = request()->get('is_room_type');
        if ($isRoomType == 1) {
            $rules['room_type_ids'] = 'required|required_not_empty';
        }

        // 特別価格に関するバリデーション
        $isSpecialPrice = request()->get('is_special_price');
        $isAllRoomPriceSet = request()->get('is_all_room_price_setting');
        $allRoomPriceSet = request()->get('all_room_price_setting');
        $specialRoomPriceSet = request()->get('special_room_price_settings');
        if($isSpecialPrice == 1){
            $isHandInput = request()->get('is_hand_input');
            if ($isHandInput == 1 && $isRoomType == 1 || ($isHandInput == 1 && isReservationBusiness($hotel))) {
                $rules['hand_input_room_prices.*.price'] = 'required|required_not_empty|Integer|min:1';
            } elseif ($isHandInput == 1 && $isRoomType == 0 && !isReservationBusiness($hotel)){
                $rules['all_room_type_price.num'] = 'required|required_not_empty|Integer|min:1';
            } elseif ($isAllRoomPriceSet == 1) {
                $rules['all_room_price_setting.num'] = "required|required_not_empty|Integer|min:1";
            } else {
                if ($hotel->business_type != 1) {
                    $rules['special_room_price_settings.*.num'] = 'required|required_not_empty|Integer|min:1'; 
                } else {
                    $isAllPlan = request()->get('is_all_plan');
                    if($isAllPlan == 1){
                        $rules['all_plan_price.num'] = 'required|required_not_empty|Integer|min:1';
                    }else{
                        if ($isPlan == 1) {
                            $rules['special_plan_prices.*.num'] = 'required|required_not_empty|Integer|min:1';
                        } else {
                            $rules['all_special_plan_prices.*.num'] = 'required|required_not_empty|Integer|min:1';
                        }
                    }
                }
            }
        }

        // ホテル以外の業種の場合の追加項目
        if (isReservationBusiness($hotel)) {
            $rules['cancel_policy_id'] = 'required|required_not_empty|integer|min:1';
            $rules['prepay'] = 'required|required_not_empty|integer|min:0';
        }

        return $rules;
    }

    public function attributes()
    {
        return [
            'name' => '名前',
            'plan_ids' => '宿泊プラン',
            'room_type_ids' => '部屋タイプ',
            'deadline_start' => '予約可能開始時間',
            'deadline_end' => '予約可能終了時間',
            'sale_period_start' => '販売開始時間',
            'sale_period_end' => '販売終了時間',
            'hand_input_room_prices.*.price' => 'お部屋の金額',
            'all_plan_price.num' => 'プランの金額',
            'special_plan_prices.*.num' => 'プランの金額',
            'all_room_type_price.num' => '全部屋の金額',
            'all_special_plan_prices.*.num' => 'プランの金額',
            'all_room_price_setting.num' => 'お部屋の金額設定',
            'special_room_price_settings.*.num' => 'お部屋の金額設定'
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
