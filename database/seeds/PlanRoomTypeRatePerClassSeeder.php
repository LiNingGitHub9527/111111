<?php

use Illuminate\Database\Seeder;
use App\Models\PlanRoomTypeRate;
use App\Models\PlanRoomTypeRatePerClass;

class PlanRoomTypeRatePerClassSeeder extends Seeder
{
    private $startDate;
    private $period;

    public function __construct()
    {
        $this->start_date = '2021-06-15';
        $this->period = 90;
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(PlanRoomTypeRatePerClass $rate)
    {
        if (config('migrate.seeds.skip_plan_room_type_rates_per_class')) {
            return;
        }

        $insertData = $this->getInsertData();
        $rate->insert($insertData);
    }

    public function getInsertData()
    {
        $insertData = [];
        $planRoomRatesIds = PlanRoomTypeRate::select('id')->whereIn('plan_id', [1,2,3,4])->where('room_type_id', '<',5)->get()->pluck('id')->toArray();
        $i = 0;
        foreach ($planRoomRatesIds as $id) {
            $dateData = [];
            $dateData = [
                'plan_room_type_rate_id' => $id,
                'class_type' => 1, // 0: 人数区分料金, 1: RC（部屋単価）
                'class_person_num' => 6, // 人数
                'class_amount' => rand(7000, 25000),
                'created_at' => now(),
            ];
            $insertData[$i] = $dateData;
            $i++;
        }

        return $insertData; 
    }
}
