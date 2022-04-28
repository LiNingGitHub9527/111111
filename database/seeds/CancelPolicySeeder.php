<?php

use Illuminate\Database\Seeder;
use App\Models\CancelPolicy;

class CancelPolicySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(CancelPolicy $cancelPolicy)
    {
        if (config('migrate.seeds.skip_cancel_policy')) {
            return;
        }

        $insertData = $this->getInsertData();
        $cancelPolicy->insert($insertData);
    }

    public function getInsertData()
    {
        $insertData = [
            [
                'name' => '7日前までキャンセル無料',
                'hotel_id' => 1,
                'is_free_cancel' => 0,
                'free_day' => 7,
                'free_time' => 0,
                'cancel_charge_rate' => 70,
                'no_show_charge_rate' => 100,
                'created_at' => now(),
            ],
        ];

        return $insertData;
    }
}
