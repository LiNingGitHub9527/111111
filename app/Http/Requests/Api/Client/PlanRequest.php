<?php

namespace App\Http\Requests\Api\Client;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class PlanRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $post = request()->all();
        $rules = [
            'name' => 'required|required_not_empty|max:40',
            'description' => 'required|required_not_empty|max:1000',
            'cancel_policy_id' => 'required|required_not_empty|integer|min:1',
            'room_type_ids' => 'required|required_not_empty',
            'meal_types' => 'required_if:is_meal,1',
            'calculate_num' => 'exclude_unless:is_new_plan,0|required|required_not_empty|numeric|gt:0',
            'up_or_down' => 'exclude_unless:is_new_plan,0|required|required_not_empty|numeric',
            'calculate_method' => 'exclude_unless:is_new_plan,0|required|required_not_empty|numeric',
        ];

        $id = $this->get('id');
        if (empty($id)) {
            $rules['hotel_id'] = 'required|required_not_empty';
        }

        if ($post['is_new_plan'] == 0) {
            $rules['existing_plan_id'] = 'required|required_not_empty|integer|min:1';
        }

        if ($post['stay_type'] == 1) {
            $rules['min_stay_days'] = 'exclude_unless:is_min_stay_days,1|required|required_not_empty|numeric|gt:0';
            $rules['max_stay_days'] = 'exclude_unless:is_max_stay_days,1|required|required_not_empty|numeric|min:1';
            if (!empty($post['min_stay_days'])) {
                $rules['max_stay_days'] = 'exclude_unless:is_max_stay_days,1|required|required_not_empty|numeric|gte:min_stay_days';
            }

        } else {
            $rules['checkin_start_time'] = 'required|required_not_empty|numeric';
            $rules['last_checkin_time'] = 'required|required_not_empty|numeric|gt:checkin_start_time';
            $rules['last_checkout_time'] = 'required|required_not_empty|numeric|gt:last_checkin_time';

            $stayTime = $post['last_checkout_time'] - $post['last_checkin_time'];
            $rules['min_stay_time'] = 'required|required_not_empty|lte:' . $stayTime;
        }

        return $rules;
    }

    public function attributes()
    {
        return [
            'name' => 'プラン名',
            'meal_type' => '食事',
            'description' => 'プラン説明',
            'cancel_policy_id' => 'キャンセルポリシー',
            'room_type_ids' => '部屋タイプ',
            'meal_types' => '食事条件',
            'existing_plan_id' => '料金プラン',
            'calculate_num' => '料金計算の数値',
            'up_or_down' => '「高い」「低い」',
            'calculate_method' => '計算方法',
            'min_stay_days' => '最低宿泊日数',
            'max_stay_days' => '最大宿泊日数',
            'checkin_start_time' => 'チェックイン開始時間',
            'last_checkin_time' => 'チェックイン終了時間',
            'last_checkout_time' => '最終チェックアウト時間',
            'is_meal' => '食事付き',
            'min_stay_time' => '最低滞在時間'
        ];
    }

    public function messages()
    {
        return [
            'is_meal.required_if' => '食事付きの場合、食事条件を選択してください',
            'last_checkin_time.gt' => 'チェックイン終了時間には、チェックイン受付の開始時間より大きな値を指定してください。',
            'last_checkout_time.gt' => '最終チェックアウト時間には、チェックイン受付の終了時間より大きな値を指定してください。',
            'min_stay_time.lte' => '最低滞在時には、チェックイン受付の終了時間とチェックアウトの最終時間の期間内の値を指定してください。',
            'max_stay_days.min' => '最大宿泊日数には、0より大きな値を指定してください。',
            'max_stay_days.gt' => '最大宿泊日数には、最低宿泊日数より大きな値を指定してください。',
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
