<?php

namespace App\Services\Api\Client\Reservation;

use App\Models\Reservation;
use App\Models\ReservationBranch;

class HotelReservationService
{
    public function __construct()
    {
    }

    public function getRoomsBy($reservation): array
    {
        $rooms = [];
        $reservationBranchs = ReservationBranch::where('reservation_id', $reservation->id)
            ->orderBy('id', 'DESC')
            ->limit($reservation->room_num)
            ->get();

        foreach ($reservationBranchs as $branch) {
            if ($branch->roomType) {
                $rooms[] = $branch->roomType->name;
            }
        }

        return $rooms;
    }

    public function addStatusByBranchStatus(
        \App\Models\Reservation &$reservation
    ): void {
        if ($reservation->reservationBranches->isNotEmpty()) {
            $canceledStatus = true;
            $reservationStatus = false;
            $reservationChangeStatus = false;
            foreach ($reservation->reservationBranches as $branch) {
                if ($branch->reservation_status !== 1) {
                    $canceledStatus = false;
                }
                if ($branch->reservation_status !== 0) {
                    $reservationChangeStatus = true;
                }
                if ($branch->reservation_status == 0) {
                    $reservationStatus = true;
                }

                if ($branch->reservation_status == 2) {
                    $reservation->reservation_status = 5;
                }
            }
            if (!$reservationChangeStatus) {
                $reservation->reservation_status = 0;
            }
            if ($reservationChangeStatus && $reservationStatus || $branch->tema_reservation_type == 1) {
                $reservation->reservation_status = 3;
            }
            if ($canceledStatus) {
                $reservation->reservation_status = 4;
            }
        }
    }

    public function getPlanAndRoomTypeNameFromBranch(
        \App\Models\Reservation $reservation
    ): array {
        $planNames = '';
        $roomTypeNames = [];

        $reservationBranchs = ReservationBranch::where('reservation_id', $reservation->id)->orderBy('id', 'DESC')->limit($reservation->room_num)->get();
        foreach ($reservationBranchs as $reservationBranch) {
            $plan = $reservationBranch->plan;
            $roomType = $reservationBranch->roomType;
            if ($plan) {
                $planNames = $plan->name;
            }
            if ($roomType) {
                $roomTypeNames[] = $roomType->name;
            }
        }

        return [
            $planNames,
            $roomTypeNames
        ];
    }
}
