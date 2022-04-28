<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Carbon\Carbon;
use App\Models\Form;
use Validator;

class RenderSearchPanelRequest extends FormRequest
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
        ];
    }

    public function cancelPolicy()
    {
        $canPoliService = app()->make('CancelPolicyService');
        $bookingConfirm = session()->get('booking_confirm', []);
        if (!empty($bookingConfirm['change_info'])) {
            $checkinDate = Carbon::parse($bookingConfirm['reservation']['checkin_start'])->format('Y-m-d');
            $canPoli = $bookingConfirm['reservation']['reservation_cancel_policy'];
            $isFreeCancel = $canPoliService->checkFreeCancelByNow(NULL, $checkinDate, json_decode(json_encode($canPoli)));
            if (!$isFreeCancel) {
                $redirectTo = route('user.booking_show', ['token' => $bookingConfirm['reservation']['verify_token']]);
                return ['res' => false, 'url' => $redirectTo];
            }
        }

        return ['res' => true];
    }

    #TODO: validateSalePeriodとcheckFormSalePeriodは対応がかぶっているためどちらかに統一

    /**
     * 販売期間のバリデーション
     *
     * @param Form $form
     * @param Carbon $accessDate アクセス日時
     * @return array
     */
    public function validateSalePeriod(Form $form, Carbon $accessDate): array
    {
        $salePeriodStart = Carbon::parse($form->sale_period_start);
        $salePeriodEnd = Carbon::parse($form->sale_period_end);
        if ($form->is_sale_period === 1 && !$accessDate->between($salePeriodStart, $salePeriodEnd)) {
            $message = '申し訳ありません。アクセスされたURLからは現在利用のご予約を受け付けておりません。恐れ入りますが、別のページからお手続きくださいませ。';
            return ['res' => false, 'message' => $message];
        }
        return ['res' => true];
    }

    /**
     * 
     *
     * @param [type] $form
     * @return void
     */
    public function checkFormSalePeriod($form)
    {
        $validator = Validator::make(request()->all(), []);

        if (empty($form)) {
            return ['res' => false, 'message' => 'こちらのページからの予約受付は現在停止しております。恐れ入りますが別のページからご予約くださいませ。'];
        }

        if ($form->is_sale_period) {
            $now = Carbon::now()->format('Y/n/j');
            $salePeriodStart = $form->sale_period_start;
            $salePeriodEnd = $form->sale_period_end;

            $salePeriodStartFormat = Carbon::parse($salePeriodStart)->format('Y/n/j');
            $salePeriodEndFormat = Carbon::parse($salePeriodEnd)->format('Y/n/j');
            if (strtotime($now) < strtotime($salePeriodStartFormat) || strtotime($now) > strtotime($salePeriodEndFormat)) {
                $message = 'こちらのページの販売期間を過ぎています。販売期間は、' . $salePeriodStartFormat . '〜' . $salePeriodEndFormat . 'です';
                return ['res' => false, 'message' => $message];
            } else {
                return ['res' => true];
            }
        }
        return ['res' => true];
    }

}
