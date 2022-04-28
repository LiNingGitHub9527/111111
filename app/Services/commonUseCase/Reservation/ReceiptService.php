<?php
namespace App\Services\commonUseCase\Reservation;

use App\Jobs\Mail\ReceiptMail;
use App\Models\Reservation;
use App\Models\Hotel;
use Carbon\Carbon;
use PDF;
use View;
use App;

class ReceiptService
{
    public function __construct()
    {
    }

	public function send($stripePaymentId) {
 		if (empty($stripePaymentId)) {
           	ddlog([
        		'success' => false,
        		'error' => 'stripePaymentIdは存在しません。'
        	]);
        	return;
        }

        $reservation = Reservation::where('stripe_payment_id', $stripePaymentId)->first();

        if (empty($reservation)) {
           	ddlog([
        		'success' => false,
        		'error' => 'reservationは存在しません。'
        	]);
        	return;
        }

        $hotel = Hotel::find($reservation->hotel_id);
        $isHotel = isHotel($hotel);

        list($roomTypes, $maxPrice) = $this->_getRoomTypeAndPrice($reservation, $isHotel);

        $now = Carbon::now()->format('Y年m月d日');

        $data = [
            'reservation_code' => $reservation->reservation_code,
            'reservation_name' => $reservation->name,
            'reservation_address' => $reservation->address,
            'reservation_email' => $reservation->email,
            'checkin_time' => $reservation->checkin_time,
            'checkout_time' => $reservation->checkout_time,
            'room_num' => $reservation->room_num,
            'accommodation_price' => $reservation->accommodation_price,
            'hotel_name' => $hotel->name,
            'nowData' => $now,
            'maxPrice' => $maxPrice
        ];

        $html = view('user.booking.captured', compact('data', 'roomTypes', 'isHotel'));
        $pdf = App::make('snappy.pdf.wrapper');
        $pdf->setOption('encoding', 'utf-8');
        $pdf->loadHTML($html);

        $hotelName = $hotel->name;
        $reservationName = $reservation->name;
		$contents = View::make('user.booking.email.receipt', compact('hotelName', 'reservationName'))->render();

        try {
        	$email = $reservation->email;
        	$subject = '【' . $hotelName . '】 ご滞在の領収書のご送付';
            dispatch(new ReceiptMail($contents,$email, $subject, $pdf))->onQueue('mail-job');
        } catch (\Exception $e)  {
        	ddlog([
        		'success' => false,
        		'error' => $e->getMessage()
        	]);
        }  
        return;      
    }

    /**
     * Undocumented function
     *
     * @param \App\Models\Reservation $reservation
     * @param boolean $isHotel
     * @return array
     */
    private function _getRoomTypeAndPrice(
        \App\Models\Reservation $reservation,
        bool $isHotel
    ): array {
        $roomTypes = [];
        $maxPrice = 0;
        if ($isHotel) {
            list($roomTypes, $maxPrice) = $this->_hotelExtractRoomTypeAndPriceBy($reservation);
        } else {
            $roomTypes = $this->_otherExtractRoomTypeRecordsBy($reservation);
            $maxPrice = ($reservation->accommodation_price) - ceil($reservation->accommodation_price / 10);
        }

        return [
            $roomTypes, 
            $maxPrice
        ];
    }
    
    /**
     * Undocumented function
     *
     * @param \App\Models\Reservation $reservation
     * @return array
     */
    private function _otherExtractRoomTypeRecordsBy(
        \App\Models\Reservation $reservation
    ): array {
        $roomTypes = [];
        foreach ($reservation->reservedBlocks as $reservedBlock) {
            $roomType = $reservedBlock->reservationBlock->roomType;
            $roomTypes[] = $roomType;
        }

        return $roomTypes;
    }

    /**
     * Undocumented function
     *
     * @param \App\Models\Reservation $reservation
     * @return array
     */
    private function _hotelExtractRoomTypeAndPriceBy(
        \App\Models\Reservation $reservation
    ): array {
        $roomTypes = [];
        $maxPrice = 0;
        $reservationBranches = $reservation->reservationBranches->where('reservation_status', 0);
        foreach ($reservationBranches as $branch) {
            $roomTypes[] = $branch->roomType;
            $maxPrice += $branch->accommodation_price;
        }

        return [
            $roomTypes,
            $maxPrice
        ];
    }
}