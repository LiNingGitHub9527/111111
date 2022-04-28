<?php

use Illuminate\Database\Seeder;
use App\Models\RoomStock;
use Carbon\Carbon;

class RoomStockSeeder extends Seeder
{
    private $startDate;
    private $period;

    public function __construct()
    {
        $this->start_date = Carbon::now()->format('Y-m-d');
        $this->period = 90;
    }   

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(RoomStock $roomStock)
    {
        if (config('migrate.seeds.skip_room_stock')) {
            return;
        }

        $insertData = $this->getInsertData($this->start_date, $this->period);
        $roomStock->insert($insertData);
    }

    public function getInsertData($startDate, $period)
    {
        $insertData = [];
        for ($i=0; $i < $period; $i++) {
            $dateData = [];
            $dateData = [
                'client_id' => 1,
                'hotel_id' => 1,
                'hotel_room_type_id' => 6,
                'date' => $startDate,
                'date_sale_condition' => 0,
                'date_stock_num' => rand(0, 10),
                'date_reserve_num' => rand(0, 10),
                'created_at' => now(),
            ];
            $insertData[] = $dateData;
            $startDate = Carbon::parse($startDate)->modify('+1 day')->format('Y-m-d');
        }

        return $insertData;
    }
}
