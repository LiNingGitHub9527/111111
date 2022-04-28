<?php

use Illuminate\Database\Seeder;
use App\Models\Plan;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Plan $plan)
    {
        if (config('migrate.seeds.skip_plan')) {
            return;
        }

        $insertData = $this->getInsertData();
        $plan->insert($insertData);
    }

    public function getInsertData()
    {
        $insertData = [
            // 宿泊
            [
                'hotel_id' => 1,
                'stay_type' => 1,
                'name' => '新規料金 | 素泊まりプラン',
                'description' => '「私はただ泊まりたいだけなんだ！」福岡という街を思う存分楽しみたいなら何も付け加えずに素泊まりプランが一番！！',
                'cancel_policy_id' => 1,
                'sort_num' => 1,
                'is_meal' => 0,
                'meal_types' => json_encode([]),
                'is_min_stay_days' => 0,
                'is_day_ago' => 0,
                'day_ago' => NULL,
                'checkin_start_time' => NULL,
                'last_checkin_time' => NULL,
                'last_checkout_time' => NULL,
                'min_stay_time' => NULL,
                'min_stay_days' => NULL,
                'is_new_plan' => 1,
                'existing_plan_id' => NULL,
                'up_or_down' => NULL,
                'calculate_method' => NULL,
                'calculate_num' => NULL,
                'room_type_ids' => json_encode([1, 2, 3]),
                'prepay' => 0,
                'public_status' => 1,
                'is_max_stay_days' => 2,
                'created_at' => now(),
            ],
            [
                'hotel_id' => 1,
                'stay_type' => 1,
                'name' => '既存料金 | ラグジュアリープラン',
                'description' => '大きなキッチン付きのお部屋だからこそのご利用方法で様々なシーンに合わせてご利用いただけます。',
                'cancel_policy_id' => 1,
                'sort_num' => 1,
                'is_meal' => 0,
                'meal_types' => json_encode([1,2]),
                'is_min_stay_days' => 0,
                'is_day_ago' => 0,
                'day_ago' => NULL,
                'checkin_start_time' => NULL,
                'last_checkin_time' => NULL,
                'last_checkout_time' => NULL,
                'min_stay_time' => NULL,
                'min_stay_days' => 1,
                'is_new_plan' => 0,
                'existing_plan_id' => 2,
                'up_or_down' => 2,
                'calculate_method' => 0,
                'calculate_num' => 70,
                'room_type_ids' => json_encode([1, 2, 3]),
                'prepay' => 0,
                'public_status' => 1,
                'is_max_stay_days' => 2,
                'created_at' => now(),
            ],

            //dayuse
            [
                'hotel_id' => 1,
                'stay_type' => 2,
                'name' => '新規料金 | テレワーク応援プラン',
                'description' => 'シンプルなデイユースプランです',
                'cancel_policy_id' => 1,
                'sort_num' => 1,
                'is_meal' => 0,
                'meal_types' => json_encode([]),
                'is_min_stay_days' => 0,
                'is_day_ago' => 0,
                'day_ago' => NULL,
                'checkin_start_time' => NULL,
                'last_checkin_time' => NULL,
                'last_checkout_time' => NULL,
                'min_stay_time' => 2,
                'min_stay_days' => NULL,
                'is_new_plan' => 1,
                'existing_plan_id' => NULL,
                'up_or_down' => NULL,
                'calculate_method' => NULL,
                'calculate_num' => NULL,
                'room_type_ids' => json_encode([1, 2, 3]),
                'prepay' => 0,
                'public_status' => 1,
                'is_max_stay_days' => 0,
                'created_at' => now(),
            ],
            [
                'hotel_id' => 1,
                'stay_type' => 2,
                'name' => '既存料金 | 【福岡STAY】8時から！テレワーク応援プラン！',
                'description' => 'meiと同じタイトルのデイユースプランです',
                'cancel_policy_id' => 1,
                'sort_num' => 1,
                'is_meal' => 0,
                'meal_types' => json_encode([1,2]),
                'is_min_stay_days' => 0,
                'is_day_ago' => 0,
                'day_ago' => NULL,
                'checkin_start_time' => 10,
                'last_checkin_time' => 21,
                'last_checkout_time' => 23,
                'min_stay_time' => 3,
                'min_stay_days' => NULL,
                'is_new_plan' => 0,
                'existing_plan_id' => 2,
                'up_or_down' => 2,
                'calculate_method' => 0,
                'calculate_num' => 70,
                'room_type_ids' => json_encode([1, 2, 3]),
                'prepay' => 0,
                'public_status' => 1,
                'is_max_stay_days' => 0,
                'created_at' => now(),
            ],

        ];
        return $insertData;
    }
}
