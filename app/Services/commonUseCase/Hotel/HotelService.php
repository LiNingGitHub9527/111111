<?php

namespace App\Services\commonUseCase\Hotel;

use App\Models\Hotel;

class HotelService
{

    public static function getHotelsWithReservationWithDateRange($start, $end)
    {
        return Hotel::with(['reservations' => function ($reservationQuery) use ($start, $end) {
            $reservationQuery
                ->whereBetween('reservations.checkin_start', [$start, $end]);
        }])->whereHas('reservations', function ($reservationQuery) use ($start, $end) {
            $reservationQuery
                ->whereBetween('reservations.checkin_start', [$start, $end]);
        });
    }

    public static function getSalePdf($companyName, $hotelName, $transferAmount)
    {
        $pdf = app()->make('snappy.pdf.wrapper');
        $pdf->setOption('encoding', 'utf-8');
        $pdf->loadHTML(view('admin.email.sale', compact('companyName', 'hotelName', 'transferAmount')));
        return $pdf;
    }
}
