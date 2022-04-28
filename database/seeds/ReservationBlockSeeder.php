<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use App\Models\Hotel;

class ReservationBlockSeeder extends Seeder
{
    #TODO: 途中です

    private $hotelIds;

    public function __construct()
    {
        $this->hotelIds = [18, 19];
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $hotelIdCheck = $this->_checkHotels();
        if (!$hotelIdCheck) {
            return false;
        }

        $roomTypes = $this->_getHotelRoomTypes();
    }

    private function _checkHotels(): bool
    {
        $isTargetHotelExist = true;
        $existHotelCount = Hotel::whereIn('id', $this->hotelIds)->count();
        $targetHotelCount = count($this->hotelIds);
        if ($existHotelCount !== $targetHotelCount) {
            $isTargetHotelExist = false;
            Log::error('ReservationBlockSeederで、$this->hotelIdsに存在しないホテルのIDが指定されています');
        }

        return $isTargetHotelExist;
    }

    private function _getHotelRoomTypes(): \Illuminate\Support\Collection
    {
        $hotelRoomTypes = \DB::table('hotel_room_types')->whereIn('hotel_id', $this->hotelIds)->get()->groupBy('hotel_id');
        return $hotelRoomTypes;
    }
}
