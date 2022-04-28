<?php
namespace App\Services\commonUseCase\Reservation;

use App\Jobs\Mail\HotelSendMail;
use Carbon\Carbon;
use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\HotelRoomType;
use App\Models\ReservationPlan;
use PDF;
use View;
use App;

class HotelEmailService
{
	public function send($hotelId, $reserveId, $state, $reservationBranches = []) {
 		if (empty($hotelId) || empty($reserveId)) {
           	ddlog([
        		'success' => false,
        		'error' => 'hotelIdは存在しません。'
        	]);
        	return;
        }

        $hotel = Hotel::find($hotelId);
        $reservation = Reservation::where('id', $reserveId)->first();

        if (empty($reservation)) {
           	ddlog([
        		'success' => false,
        		'error' => 'reservationは存在しません。'
        	]);
        	return;
        }

        if ($state !== 0) {
            $reservationBranches = $reservation->reservationBranches->where('reservation_status', 0);
        }

        foreach ($reservationBranches as $branch) {
            $planName = $branch->plan->name;
        }
        
        $url = route('user.booking_show', $reservation->verify_token);

        $data = [];
        $data = collect($reservationBranches)->transform(function ($branch, $key) use ($data) {
        	$data['room_type_name'] = $branch->roomType->name;
            $reservationBranches = ReservationPlan::where('reservation_branch_id', $branch->id)->first();
        	$data['adult_num'] = $reservationBranches->adult_num;
            $data['child_num'] = $reservationBranches->child_num;
            $data['accommodation_price'] = $branch->accommodation_price;
        	return $data;
        })->toArray();

        $accommodationPrice = 0;
        if ($state == 0) {
            $accommodationPrice = $reservation->cancel_fee;
        } else {
            $accommodationPrice = $reservation->accommodation_price;
        }

        $checkinTime = Carbon::parse($reservation->checkin_time)->format('Y/m/d');
        $checkoutTime = Carbon::parse($reservation->checkout_time)->format('Y/m/d');

        $emailContent = [
        	'hotel_name' => $hotel->name,
        	'reservation_name' => $reservation->name,
        	'checkin_time' => $checkinTime,
        	'tel' => $reservation->tel,
        	'reservation_email' => $reservation->email,
        	'address' => $reservation->address,
        	'reservation_code' => $reservation->reservation_code,
        	'checkout_time' => $checkoutTime,
        	'room_type_detail' => $data,
            'payment_method' => $reservation->payment_method ? '事前決済' : '現地決済',
            'accommodation_price' => $accommodationPrice,
            'url' => $url,
            'plan_name' => $planName,
            'state' => $reservation->statusReservation($state),
        ];

        $html = View::make('user.booking.email.hotel_email', compact('emailContent'))->render();

        try {
            $email = $hotel->email;
            $title = $reservation->statusReservation($state) . 'のお知らせ【' . $reservation->name . '様' . '(' . $checkinTime . '~' . $checkoutTime . ')】';
            dispatch(new HotelSendMail($html, $email, $title))->onQueue('mail-job');

        } catch (\Exception $e)  {
            ddlog([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        return;  
	}
}