<?php

namespace App\Jobs\Mail\User\Other;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Reservation;
use App\Models\ReservationBlock;
use App\Models\ReservedReservationBlock;
use Mail;

class ReserveOtherJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $email;
    private $subject;
    private $template;
    private $code;
    private $amount;
    private $payment_method;
    private $hotel;
    private $plan;
    private $plan_rooms;
    private $reserve_id;
    private $reservation;
    private $checkinDate;
    private $checkoutDate;
    private $url;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userShowUrl, $email, $reserveCode,
        $accommodationAmount, $paymentMethod, $hotel, $checkinDate, $checkoutDate,
        $reserveId, $subject, $template='user/booking/other/email/confirm', $stayType=NULL)
    {
        $this->email = $email;
        $this->subject = $subject;
        $this->template = $template;
        $this->code = $reserveCode;
        $this->amount = $accommodationAmount;
        $this->payment_method = $paymentMethod;
        $this->hotel = $hotel;

        $reservation = Reservation::find($reserveId);
        $this->reservation = $reservation;

        $this->checkinDate = $checkinDate;
        $this->checkoutDate = $checkoutDate;

        $this->reserved_rooms = ReservedReservationBlock::where('reservation_id', $reservation->id)->get()
            ->transform(function($reserved) {
                $block = ReservationBlock::with('roomType')->where('id', $reserved->reservation_block_id)->first();
                $reserved->name = $block->roomType->name;
                return $reserved;
            });

        $this->url = $userShowUrl;
        $this->stay_type = $stayType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $params = [
                'url' => $this->url,
                'code' => $this->code,
                'amount' => $this->amount,
                'paymentMethod' => $this->payment_method,
                'hotel' => $this->hotel,
                'reservedRooms' => $this->reserved_rooms,
                'reservation' => $this->reservation,
                'checkinDate' => $this->checkinDate,
                'checkoutDate' => $this->checkoutDate,
                'stayType' => $this->stay_type
            ];
            Mail::send($this->template, $params, function($message) {
                $message->to($this->email)->subject($this->subject);
            });
            if(count(Mail::failures()) > 0) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
