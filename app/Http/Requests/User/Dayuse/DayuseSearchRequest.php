<?php

namespace App\Http\Requests\User\Dayuse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class DayuseSearchRequest extends FormRequest
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
        $now = Carbon::now()->format('Y/m/d H:i');

        return [
            'url_param' => 'required',
            'checkin_date' => 'required|after:yesterday',
            'checkin_date_time' => 'required|after:' . $now,
            'stay_time' => 'required',
            'adult_num.*' => 'required|integer|min:1',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $childNums = request()->get('child_num');
        $validator->after(function($validator) use ($childNums){

        });
    }

    public function messages()
    {
        return [
            'checkin_date.after' => 'チェックイン日には、予約日以降の日付を指定してください。',
            'checkin_date_time.after' => 'チェックイン予定時間は現時点より大きく設定してください'
        ];

    }

    public function attributes()
    {
        return [
            'checkin_date' => 'ご利用日',
            'checkin_date_time' => 'チェックイン予定時間',
            'stay_time' => '滞在時間',
            'adult_num.*' => '大人人数'
        ];
    }

    public function checkFormDeadline($form, $checkinDate, $checkoutDate)
    {
        if ($form->is_deadline) {
            $deadlineStart = $form->deadline_start;
            $deadlineEnd = $form->deadline_end;
            $deadlineStartFormat = Carbon::parse($deadlineStart)->format('Y年n月j日');
            $deadlineEndFormat = Carbon::parse($deadlineEnd)->format('Y年n月j日');
            if (strtotime($checkinDate) < strtotime($deadlineStart) || strtotime($checkoutDate) > strtotime($deadlineEnd)) {
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
