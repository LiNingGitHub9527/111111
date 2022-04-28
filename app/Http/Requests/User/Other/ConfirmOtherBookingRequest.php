<?php

namespace App\Http\Requests\User\Other;

use Illuminate\Foundation\Http\FormRequest;

class ConfirmOtherBookingRequest extends FormRequest
{
    public function __construct()
    {
        $this->reserve_session_service = app()->make('ReserveSessionService');
        $bookingData = $this->reserve_session_service->getSessionByKey('booking_other');
        $this->baseCustomerItems = $bookingData['base_customer_items'] ?? [];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function validationData()
    {
        $all = $this->all();

        foreach($this->baseCustomerItems as $idx => $item) {
            // 予約時の入力項目でない場合
            if ($item['is_reservation_item'] != 1) {
                continue;
            }
            switch($item['data_type']) {
                case 14: // 予約開始時間
                case 15: // 予約終了時間
                    $itemId = 'item_' . $item['id'];
                    $hour = $all[$itemId . '_hour'] ?? '';
                    $minute = $all[$itemId . '_minute'] ?? '';
                    if (!empty($hour) && !empty($minute)) {
                        $all['item_' . $item['id']] = sprintf('%02d:%02d', $hour, $minute);
                    }
                    break;
            }
        }
        return $all;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        $payment_method = request()->get('payment_method');
        if ($payment_method == 1) {
            $rules['card_number'] = 'required|check_numeric|digits_between:13,17';
            $rules['expiration_month'] = 'required';
            $rules['expiration_year'] = 'required';
            $rules['cvc'] = 'required|check_numeric|digits_between:1,4';
        }
        // CRMから取得した予約時の入力項目のrulesを作成する
        foreach($this->baseCustomerItems as $idx => $item) {
            // 予約時の入力項目でない場合
            if ($item['is_reservation_item'] != 1) {
                continue;
            }
            $arr = [];
            $dataType = $item['data_type'];
            if ($item['is_required'] == 1 || $dataType == 10) {
                $arr[] = 'required';
            } else {
                $arr[] = 'nullable';
            }
            switch($dataType) {
                case 1: // 短文テキスト
                    $arr[] = 'max:50';
                    break;
                case 2: // 長文テキスト
                    $arr[] = 'max:1000';
                    break;
                case 3: // 数値
                    $arr[] = 'check_numeric|digits_between:1,50';
                    break;
                case 4: // 日付
                case 13: // チェックイン日
                    $arr[] = 'date_format:Y-m-d';
                    break;
                case 5: // 時間
                    $arr[] = 'date_format:H:i:s';
                    break;
                case 6: // 日付+時間
                    $arr[] = 'date_format:Y-m-d\TH:i:s';
                    break;
                case 7: // 性別
                    $arr[] = 'integer|min:1|max:3';
                    break;
                case 8: // 氏名
                    $arr[] = 'max:100';
                    break;
                case 9: // 電話番号
                    $arr[] = 'between:10,11';
                    break;
                case 10: // メールアドレス
                    $arr[] = 'email|max:64';
                    break;
                case 11: // 住所
                    $arr[] = 'max:255';
                    break;
                case 12: // 部屋タイプ名
                    $arr[] = 'max:255';
                    break;
                case 14: // 予約開始時間
                case 15: // 予約終了時間
                    $arr[] = 'regex:/^[0-9][0-9]:[0-5][0-9]$/';
                    break;
            }
            $id = $item['id'];
            $rules['item_' . $id] = implode('|', $arr);
            if ($dataType == 10) {
                // メールアドレス確認用
                $rules['item_' . $id . '_confirm'] = 'required|email|max:64|same:item_' . $id;
            }
        }

        // data_type=10のbaseCustomerItemがなくても必須とする
        if ($this->isNotExistsEmail()) {
            $rules['email'] = 'required|email|max:64';
            $rules['email_confirm'] = 'required|email|max:64|same:email';
        }

        return $rules;
    }

    private function isNotExistsEmail(): bool
    {
        return collect($this->baseCustomerItems)->filter(function($item) {
            return $item['data_type'] == 10 && $item['is_reservation_item'] == 1;
        })->isEmpty();
    }

    public function attributes()
    {
        $attributes = [];
        // CRMから取得した予約時の入力項目のattributesを作成する
        foreach($this->baseCustomerItems as $idx => $item) {
            // 予約時の入力項目でない場合
            if ($item['is_reservation_item'] != 1) {
                continue;
            }
            $id = $item['id'];
            $attributes['item_' . $id] = $item['name'];
            $dataType = $item['data_type'];
            if ($dataType == 10) {
                // メールアドレス確認用
                $attributes['item_' . $id . '_confirm'] = 'メールアドレス確認用';
            }
        }

        // data_type=10のbaseCustomerItemがない場合
        if ($this->isNotExistsEmail()) {
            $attributes['email'] = 'メールアドレス';
            $attributes['email_confirm'] = 'メールアドレス確認用';
        }

        return $attributes;
    }

    /**
     * 定義済みバリデーションルールのエラーメッセージ取得
     *
     * @return array
     */
    public function messages()
    {
        return [
            'regex' => ':attributeには時刻を指定してください。',
        ];
    }

}
