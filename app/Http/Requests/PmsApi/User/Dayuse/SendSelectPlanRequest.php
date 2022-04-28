<?php

namespace App\Http\Requests\PmsApi\User\Dayuse;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;

class SendSelectPlanRequest extends FormRequest
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
            'url_param' => 'required',
            'checkin_date' => 'required',
            'checkin_date_time' => 'required',
            'checkout_date_time' => 'required',
            'stay_time' => 'required',
            'person_nums' => 'required',
        ];
    }

    public function withValidator(Validator $validator)
    {
        $childNums = request()->get('child_num');
        $validator->after(function($validator) use ($childNums){

        });
    }

    public function checkFormDeadline($form, $checkinDate, $checkoutDate)
    {
        if ($form->is_deadline) {
            $deadlineStart = $form->deadline_start;
            $deadlineEnd = $form->deadline_end;
            $deadlineStartFormat = Carbon::parse($deadlineStart)->format('Y年n月j日');
            $deadlineEndFormat = Carbon::parse($deadlineEnd)->format('Y年n月j日');
            if (strtotime($checkinDate) < strtotime($deadlineStart) || strtotime($checkoutDate) > strtotime($deadlineEnd)) {
                throw (new HttpResponseException(response()->json([
                    'code' => 1422,
                    'status'  => 'FAIL',
                    'message' => '予約可能期間は、' . $deadlineStartFormat . '〜' . $deadlineEndFormat . 'です',
                ], 200)));
            }
        } else {
            return true;
        }
    }

    protected function failedValidation(Validator $validator)
    {
        throw (new HttpResponseException(response()->json([
            'code' => 1422,
            'status'  => 'FAIL',
            'message' => $validator->errors(),
        ], 200)));
    }
}
