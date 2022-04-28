<?php

namespace App\Services\Api\Client\Home;

use Carbon\Carbon;

class OtherHomeService
{
    public function __construct()
    {
        $this->other_reservation_service = app()->make('ApiOtherReservationService');
    }

    public function buildListRecord(
        \Illuminate\Pagination\LengthAwarePaginator $list
    ): array {
        $records = [];
        foreach ($list as $reservation) {
            $adultNum = $reservation->adult_num;
            $checkinDate = Carbon::parse($reservation->checkin_start)->startOfDay();
            $checkoutDate = Carbon::parse($reservation->checkout_end)->startOfDay();

            $reservationRoomTypeNames = [];
            $roomTypeNames = $this->other_reservation_service->extractRoomTypesBy($reservation);

            $row = [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'adult_num' => $adultNum,
                'child_num' => 0,
                'accommodation_price' => $reservation->accommodation_price,
                'reservation_date' => $reservation->reservationDisplayDate(),
                'checkin_date' => $reservation->checkinDisplayDate(),
                'checkout_date' => $reservation->checkoutDisplayDate(),
                'accommodation_night' => 0,
                'reservationBranches' => [],
                'checkin_time' => Carbon::parse($reservation->checkin_time)->format('H:i'),
                'checkout_time' => Carbon::parse($reservation->checkout_time)->format('H:i'),
                'roomTypeNames' => $roomTypeNames
            ];

            $records[] = $row;
        }
        return $records;
    }
}
