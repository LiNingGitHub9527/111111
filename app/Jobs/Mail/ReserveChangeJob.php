<?php

namespace App\Jobs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Plan;
use App\Models\Reservation;
use App\Models\HotelRoomType;
use Mail;

class ReserveChangeJob implements ShouldQueue
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
    public function __construct($userShowUrl, $email, $reserveCode, $accomonDationAmount, $paymentMethod, $hotel, $planId, $planRooms, $checkinDate, $checkoutDate, $reserveId, $stayType=NULL)
    {
        $this->email = $email;
        $this->subject = '【ご予約の変更】「' . $hotel->name .'」のご予約を変更しました';
        $this->template = 'user/booking/email/confirm';
        $this->code = $reserveCode;
        ddlog($this->code);
        $this->amount = $accomonDationAmount;
        $this->payment_method = $paymentMethod; 
        $this->hotel = $hotel;

        $plan = Plan::find($planId);
        $this->plan = $plan;

        $reservation = Reservation::find($reserveId);
        $this->reservation = $reservation;

        $this->checkinDate = $checkinDate;
        $this->checkoutDate = $checkoutDate;
        $planRooms = collect($planRooms)
                     ->transform(function($planRoom){
                         $roomTypeName = HotelRoomType::find($planRoom->room_type_id)->name;
                         $planRoom->name = $roomTypeName;
                         $childNum = collect($planRoom->child)->sum('child_num');
                         $planRoom->child_num = $childNum; 

                         return $planRoom; 
                     })
                     ->toArray();

        $this->plan_rooms = $planRooms;
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
            Mail::send($this->template, ['url' => $this->url, 'code' => $this->code, 'amount' => $this->amount, 'paymentMethod' => $this->payment_method, 'hotel' => $this->hotel, 'plan' => $this->plan, 'planRooms' => $this->plan_rooms, 'reservation' => $this->reservation, 'checkinDate' => $this->checkinDate, 'checkoutDate' => $this->checkoutDate, 'stayType' => $this->stay_type], function($message) {
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
