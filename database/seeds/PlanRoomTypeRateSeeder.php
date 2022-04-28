<?php

use Illuminate\Database\Seeder;
use App\Models\PlanRoomTypeRate;
use Carbon\Carbon;

class PlanRoomTypeRateSeeder extends Seeder
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
    public function run(PlanRoomTypeRate $rate)
    {
        if (config('migrate.seeds.skip_plan_room_type_rates')) {
            return;
        }

        $insertData = $this->getInsertData($this->start_date, $this->period);
        $rate->insert($insertData);
    }

    public function getInsertData($startDate, $period)
    {
        $insertData = [];
        for ($i = 0; $i < $period; $i++) {
            $dateData = [
                'client_id' => 1,
                'hotel_id' => 1,
                'room_type_id' => 3,
                'plan_id' => 3,
                'date' => $startDate,
                'created_at' => now(),
            ];
            $insertData[$i] = $dateData;
            $startDate = Carbon::parse($startDate)->modify('+1 day')->format('Y-m-d');
        }
        return $insertData;
    }
}
