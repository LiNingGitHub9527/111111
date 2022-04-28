<?php

namespace App\Http\Requests\User;

use Validator;
use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;

class StaySearchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'checkin_date' => 'required|after:yesterday',
            'checkout_date' => 'required|after:checkin_date',
            'url_param' => 'required',
            'adult_num.*' => 'required|integer|min:1',
        ];
    }

    public function attributes()
    {
        return [
            'checkin_date' => 'チェックイン日',
            'checkout_date' => 'チェックアウト日',
            'adult_num.*' => '大人人数',
        ];
    }

    public function messages()
    {
        return [
            'checkin_date.after' => 'チェックイン日には、予約日以降の日付を指定してください。',
        ];

    }

    public function checkFormDeadline($form, $checkinDate, $checkoutDate)
    {
        $validator = Validator::make(request()->all(), []);

        if (empty($form)) {
            return ['res' => false, 'message' => 'こちらのページからの予約受付は現在停止しております。恐れ入りますが別のページからご予約くださいませ。'];
        }

        if ($form->is_deadline) {
            $deadlineStart = $form->deadline_start;
            $deadlineEnd = $form->deadline_end;
            $deadlineStartFormat = Carbon::parse($deadlineStart)->format('Y/n/j');
            $deadlineEndFormat = Carbon::parse($deadlineEnd)->format('Y/n/j');
            if (strtotime($checkinDate) < strtotime($deadlineStartFormat) || strtotime($checkoutDate) > strtotime($deadlineEndFormat)) {
                $message = 'こちらのページの予約可能期間を過ぎています。予約可能期間は、' . $deadlineStartFormat . '〜' . $deadlineEndFormat . 'です';
                return ['res' => false, 'message' => $message];
            } else {
                return ['res' => true];
            }
        } else {
            return ['res' => true];
        }
    }
}
