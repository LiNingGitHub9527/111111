<?php

namespace App\Console\Commands;

use App\Models\Hotel;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Services\ScEndPoint\Temairazu\TemairazuService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RemakeAuthory extends Command
{
    /**
     *
     * @var array
     */
    private $successUpdate;

    /**
     *
     * @var array
     */
    private $failUpdate;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shareg:remake-authory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->stripe_service = app()->make('StripeService');
        $this->reserve_service = app()->make('ReserveService');
        $this->failUpdate = ['reservation_status' => 1, 'cancel_date_time' => now(), 'cancel_fee' => 0, 'commission_price' => 0, 'payment_commission_price' => 0];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $threeDaysAgo = Carbon::now()->addDays(3)->format('Y-m-d');

        $reservations = Reservation::where('payment_method', 1)
            ->where('payment_status', 2)
            ->where('reservation_status', 0)
            ->where('checkin_time', 'like', $threeDaysAgo . '%')
            ->get();

        foreach ($reservations as $reservation) {
            $reservationPayment = ReservationPayment::where('reservation_id', $reservation->id)
                ->where('stripe_payment_id', $reservation->stripe_payment_id)
                ->orderBy('id', 'DESC')
                ->first();

            if (empty($reservationPayment) || $reservationPayment->type != 1) {
                continue;
            }

            $hotel = Hotel::find($reservation->hotel_id);
            $isHotel = isHotel($hotel);

            $description = $this->stripe_service->manageMakePrePayDesc($reservation, $reservation->name, $reservation->email, $reservation->tel);

            // author の再作成
            $authoryRes = $this->stripe_service->manageDoAuthoryPayByCId($reservation->id, $reservation->stripe_customer_id, $reservation->accommodation_price, $description);

            if (!$authoryRes['res']) {
                try {
                    \DB::transaction(function () use ($reservation) {
                        $reservation->update($this->failUpdate);
                    });
                } catch (\Exception $e) {
                    Log::info('reservation cancel :' . $e);
                    continue;
                }
                if ($isHotel) {
                    TemairazuService::instance()->sendReservationNotification($reservation->client_id, $reservation->id);
                }
                #TODO: ここに他業種の場合は、CRMへ同期する処理を入れるか？
                // $this->reserve_service->sendFailPayMail($reservation);
            } else {
                $refundData = [];
                $this->stripe_service->manageFullRefund($reservation->id, $reservation->stripe_payment_id, $reservation->accommodation_price, $refundData);
                $chargeId = $authoryRes['data']->id;
                $reservation->update(['stripe_payment_id' => $chargeId]);
            }
        }
    }
}
