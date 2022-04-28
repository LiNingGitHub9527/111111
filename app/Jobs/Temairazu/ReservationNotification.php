<?php

namespace App\Jobs\Temairazu;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Services\ClientLogService;
use App\Models\Reservation;
use App\Support\Temairazu\Http\Request;

class ReservationNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $clientId;

    private $reservationId;

    /**
     * Create a new job instance.
     *
     * @param $reservationId
     */
    public function __construct($clientId, $reservationId)
    {
        $this->clientId = $clientId;
        $this->reservationId = $reservationId;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['reservation-notification', 'reservationId:'.$this->reservationId];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $logger = ClientLogService::instance($this->clientId, 'jobs/reservation-notification');

        $logger->info('handle begin: ' . $this->reservationId);

        $reservation = Reservation::with('hotel')->find($this->reservationId);
        if (empty($reservation)) {
            $logger->warning("reservation({$this->reservationId}) not exists");
            return true;
        }
        $hotel = $reservation->hotel;
        if (empty($hotel)) {
            $logger->warning("reservation({$this->reservationId}) hotel not exists");
            return true;
        }
        if (empty($hotel->tema_login_id) || empty($hotel->tema_login_password)) {
            $logger->warning("reservation({$this->reservationId}) tema_login_id/tema_login_password is empty");
            return true;
        }

        $parterId = config('temairazu.partner.id');
        $parterPwd = config('temairazu.partner.password');
        if (empty($parterId) || empty($parterPwd)) {
            $logger->warning("reservation({$this->reservationId}) PartnerID/Password is empty");
            return true;
        }

        $data = [
            'PartnerID' => $parterId,                           //パートナーID
            'Password' => $parterPwd,                           //パスワード
            'LoginID' => $hotel->tema_login_id,                 //施設ユーザ ID
            'BookingID' => $reservation->reservation_code,      //予約番号
        ];

        $sandbox = app()->isProduction() ? false : true;
        $request = new Request();
        $result = $request->setSandBox($sandbox)->post('tema/notification/bookings/', $data);
        $logger->info($result);

        $logger->info('handle end');
    }
}
