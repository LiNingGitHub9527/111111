<?php

namespace App\Jobs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Hotel;
use Mail;

class FailPayNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $subject;
    private $userTemplate;
    private $hotelTemplate;
    private $reservation;
    private $url;
    private $hotel;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($reservation)
    {
        $hotel = Hotel::find($reservation->hotel_id);
        $this->hotel = $hotel;
        $this->userSubject = '【キャンセルのお知らせ】「' . $hotel->name .'」のご予約がキャンセルされました';
        $this->hotelSubject = '【キャンセルのお知らせ】決済失敗によりご予約がキャンセルされました';
        $this->userTemplate = 'user/booking/email/fail_pay_user';
        $this->hotelTemplate = 'user/booking/email/fail_pay_hotel';
        $this->reservation = $reservation;

        #TODO: 他業種の場合URLを変える必要あり
        $this->url = route('user.booking_show', $reservation->verify_token);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // ユーザーへの通知
            Mail::send($this->userTemplate, ['url' => $this->url, 'reservation' => $this->reservation, 'hotel' => $this->hotel], function($message) {
                $message->to($this->reservation->email)->subject($this->userSubject);
            });

            // ホテルへの通知
            Mail::send($this->hotelTemplate, ['reservation' => $this->reservation, 'hotel' => $this->hotel], function($message) {
                $message->to($this->hotel->email)->subject($this->hotelSubject);
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
