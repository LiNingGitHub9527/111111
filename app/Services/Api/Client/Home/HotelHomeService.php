<?php

namespace App\Services\Api\Client\Home;

use Carbon\Carbon;

class HotelHomeService
{
    public function __construct()
    {
    }

    public function buildListRecord(
        \Illuminate\Pagination\LengthAwarePaginator $list
    ): array {
        $records = [];
        foreach ($list as $reservation) {
            $adultNum = $reservation->adult_num;
            $childNum = $reservation->child_num;
            $checkinDate = Carbon::parse($reservation->checkin_start)->startOfDay();
            $checkoutDate = Carbon::parse($reservation->checkout_end)->startOfDay();
            $accommodationNight = $checkoutDate->diffInDays($checkinDate);

            $reservationBranches = [];
            foreach ($reservation->reservationBranches as $reservationBranch) {
                if ($reservationBranch) {
                    $rb = [
                        'plan_name' => $reservationBranch->plan->name ?? '',
                        'room_type_name' => $reservationBranch->roomType->name ?? ''
                    ];
                    $reservationBranches[] = $rb;
                }
            }

            $row = [
                'id' => $reservation->id,
                'name' => $reservation->name,
                'adult_num' => $adultNum,
                'child_num' => $childNum,
                'accommodation_price' => $reservation->accommodation_price,
                'reservation_date' => $reservation->reservationDisplayDate(),
                'checkin_date' => $reservation->checkinDisplayDate(),
                'checkout_date' => $reservation->checkoutDisplayDate(),
                'accommodation_night' => $accommodationNight,
                'reservationBranches' => $reservationBranches,
                'checkin_time' => Carbon::parse($reservation->checkin_time)->format('H:i'),
                'checkout_time' => Carbon::parse($reservation->checkout_time)->format('H:i')
            ];

            $records[] = $row;
        }
        return $records;
    }
}