<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\Hotel;

class CapturedController extends ApiBaseController
{
	public function list()
    {	
    	$reservations = Reservation::all();
    	$captureData = [];
    	$authorData = [];
    	foreach ($reservations as $reservation) {
    		$captured = $reservation->reservationCaptured->where('captured_status', 0)->first();
    		$hotel = Hotel::find($reservation->hotel_id);
    		if (!empty($captured)) {
	    		$raw = [
	    			'id' => $reservation->id,
	    			'name' => $reservation->name,
	    			'hotel_name' => $hotel->name,
	    			'email' => $reservation->email,
	    			'captured_status' => '未決済',
	    			'checkin_time' => dateText($reservation->checkin_time),
	    			'type' => 'キャプチャー'
	    		];
    			$captureData[] = $raw;
    		}

    		$reservationPayment = $reservation->reservationPayment
						    		->where('reservation_id', $reservation->id)
									->where('type', 1)
									->where('status', 0)
									->first();
    		if (!empty($reservationPayment)) {
    			$author = [
    				'id' => $reservation->id,
    				'name' => $reservation->name,
    				'hotel_name' => $hotel->name,
    				'email' => $reservation->email,
    				'status' => '未決済',
    				'checkin_time' => dateText($reservation->checkin_time),
    				'type' => 'オーソリ作成'
    			];
    			$authorData[] = $author;
    		}
    	}
    	$data = [
    		'captured' => $captureData,
    		'author' => $authorData
    	];
    	return $this->success($data);
    }
}