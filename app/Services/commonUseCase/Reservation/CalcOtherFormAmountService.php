<?php
namespace App\Services\commonUseCase\Reservation;

use App\Models\Form;

class CalcOtherFormAmountService
{

    public function __construct()
    {
    }

    public function calcReservationBlockFormSettingAmount(Form $form, array $reserveBlock): int
    {
        $roomTypeId = $reserveBlock['room_type_id'];
        $roomOriginalPrice = $reserveBlock['price'];

        // 特別価格を設定しないフォームの場合は早期リターン
        if ($form->is_special_price == 0) {
            return $roomOriginalPrice;
        }

        // ①全ての部屋タイプの金額を手動入力(is_hand_inputが1)
        if ($form->is_hand_input == 1) {
            $priceSettings = $form->hand_input_room_prices ?? [];
            return $this->calcHandInput($roomTypeId, $form->hand_input_room_prices);
        }

        if ($form->is_hand_input == 0) {
            if ($form->is_all_room_price_setting == 0) {
                // ②選択した部屋タイプの金額の割引・割増をそれぞれに登録(is_hand_input, is_all_room_price_settingが共に0)
                $priceSettings = $form->special_room_price_settings ?? [];
                return $this->calcPerForm($roomTypeId, $roomOriginalPrice, $priceSettings);
            } elseif ($form->is_all_room_price_setting == 1) {
                // ③選択した部屋タイプの割引・割増を一括登録する(is_hand_inputが0、is_all_room_price_settingが1)
                $priceSettings = $form->all_room_price_setting ?? [];
                return $this->calcAllForm($roomOriginalPrice, $priceSettings);
            }
        }
    }

    // ①全ての部屋タイプの金額を手動入力(is_hand_inputが1)
    private function calcHandInput(int $roomTypeId, array $handInputPrices): int
    {
        $handInputPrices = $this->transformKeyHandInputs($handInputPrices);
        $price = $handInputPrices[$roomTypeId]['price'];
        if (!empty($price) && $price > 0) {
            return $price;
        }
        return 0;
    }

    // ②選択した部屋タイプの金額の割引・割増をそれぞれに登録(is_hand_input, is_all_room_price_settingが共に0)
    private function calcPerForm(int $roomTypeId, int $roomOriginalPrice, array $priceSettings): int
    {
        $priceSetting = [];
        $arr = collect($priceSettings)->keyBy('room_type_id')->toArray();
        if (!empty($arr[$roomTypeId])) {
            $priceSetting = $arr[$roomTypeId];
        }
        return $this->calcPrice($roomOriginalPrice, $priceSetting);
    }

    // ③選択した部屋タイプの割引・割増を一括登録する(is_hand_inputが0、is_all_room_price_settingが1)
    private function calcAllForm(int $roomOriginalPrice, array $priceSetting): int
    {
        return $this->calcPrice($roomOriginalPrice, $priceSetting);
    }

    private function calcPrice(int $originalPrice, array $priceSetting): int
    {
        if (!empty($priceSetting)) {
            $calcYen = $priceSetting['num'];
            $price = $originalPrice;
            if ($priceSetting['unit'] == 0) {
                $calcYen = $this->calcYen($price, $priceSetting['num']);
            }
            if ($priceSetting['up_off'] == 1) {
                $price += $calcYen;
                return $price > 0 ? $price : 0;
            } elseif ($priceSetting['up_off'] == 2) {
                $price -= $calcYen;
                return $price > 0 ? $price : 0;
            }
        }
        return 0;
    }

    private function transformKeyHandInputs(array $handInputPrices): array
    {
        return collect($handInputPrices)->keyBy('room_type_id')->toArray();
    }

    private function calcYen(int $amount, int $percentage): int
    {
        return ceil( $amount * ( $percentage * 0.01 ) );
    }

}