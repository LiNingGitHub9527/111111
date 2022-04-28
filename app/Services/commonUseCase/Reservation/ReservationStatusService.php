<?php

namespace App\Services\commonUseCase\Reservation;

use App\Jobs\Mail\ReservationStatusMail;
use App\Jobs\Mail\User\Other\ReserveOtherJob;
use App\Models\Reservation;
use App\Models\Hotel;
use Carbon\Carbon;
use View;
use App;

class ReservationStatusService {
	public function __construct() {
	}

	public function send($reservationId) {
		if (empty($reservationId)) {
			return;
		}

		$reservation = Reservation::find($reservationId);

		if (empty($reservation)) {
			return;
		}
		$subject = '';
		$hotel = Hotel::find($reservation->hotel_id);

		if ($reservation->reservation_status == 0) {
			$subject = "【予約確定のお知らせ】「{$hotel->name}」のご予約が承認されました";
		}

		if ($reservation->reservation_status == 1) {
			$subject = "【予約キャンセルのお知らせ】「{$hotel->name}」のご予約がキャンセルされました";
		}

		$now = Carbon::now()->format('Y年m月d日');

		$userShowUrl = route('user.other.booking_show', $reservation->verify_token);


		try {
			$res = dispatch_now(
				new ReserveOtherJob(
					$userShowUrl,
					$reservation->email,
					$reservation->reservation_code, $reservation->accommodation_price,
					$reservation->payment_method, $hotel,
					$reservation->checkin_time, $reservation->checkout_time,
					$reservationId, $subject,($reservation->reservation_status == 0)?'user/booking/other/email/reservation_apply':'user/booking/other/email/reservation_cancel'));

		} catch (\Exception $e) {
			ddlog([
				'success' => false,
				'error' => $e->getMessage()
			]);
		}
		return;
	}

}
