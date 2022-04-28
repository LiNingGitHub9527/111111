<?php

namespace App\Http\Requests\Api\User\Other;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;
use App\Models\Form;

class GetReservationBlockRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $rules = [
            'hotel_id' => 'required|integer|min:1',
            'room_type_token' => 'required|string',
            'start_date' => 'required|after:yesterday',
            'end_date' => 'required|after:start_date',
        ];

        if($this->has('is_available')) {
            $rules['is_available'] = 'required|in:true,false';
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'hotel_id' => '施設ID',
            'room_type_token' => '部屋タイプトークン',
            'start_date' => '予約枠取得の開始日',
            'end_date' => '予約枠取得の終了日',
            'is_available' => '空室チェック',
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.after' => '予約枠取得の開始日には、現在日以降の日付を指定してください。',
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

    /**
     * forms.room_type_idsに$roomTypeIdが含まれるかチェック
     *
     * @param Form $form
     * @param integer $roomTypeId
     * @return array
     */
    public function checkFormRoomTypeIds(Form $form, int $roomTypeId): array
    {
        if (empty($form)) {
            return ['res' => false, 'message' => 'こちらのページからの予約受付は現在停止しております。恐れ入りますが別のページからご予約くださいませ。'];
        }
        if (!in_array($roomTypeId, $form->room_type_ids)) {
            $message = '申し訳ありません。指定された部屋タイプは有効ではございません。恐れ入りますが、別のページからお手続きくださいませ。';
            return ['res' => false, 'message' => $message];
        }
        return ['res' => true];
    }

}
