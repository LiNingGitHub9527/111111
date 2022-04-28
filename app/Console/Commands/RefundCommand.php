<?php

namespace App\Console\Commands;

use App\Http\Controllers\CommonUseCase\Reservation\BookingCoreController;
use App\Models\ReservationRefund;
use App\Services\ScEndPoint\Temairazu\TemairazuService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefundCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shareg:refund-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'refund';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $reservationRefunds = ReservationRefund::where('status', '!=', 1)->get();
        if (empty($reservationRefunds)) {
            return;
        }
        $stripeService = app()->make('StripeService');
        foreach ($reservationRefunds as $reservationRefund) {
            if (empty($reservationRefund['refund_id'])) {
                continue;
            }
            $refundData = [];
            $result = $stripeService->auditAccounts($reservationRefund['refund_id'], $refundData);
            if (!$result) {
                $reservationRefund->handle_date = Carbon::parse(time());
                $reservationRefund->refund_information = $refundData['message'];
                $reservationRefund->status = 0;
                $reservationRefund->save();
                continue;
            }
            $reservationRefund->refund_id = $refundData['refund_id'];
            $reservationRefund->handle_date = Carbon::parse(time());
            $reservationRefund->refund_information = $refundData['status'];
            $reservationRefund->status = 1;
            $reservationRefund->save();

            $reservation = $reservationRefund->reservation;
            $bookingCoreController = new BookingCoreController;
            $bookingCoreController->reserveIncreaseRoomStock($reservation->id, $reservation->hotel);

            $cancelType = $reservationRefund['type'];
            try {
                \DB::transaction(function () use ($reservation, $cancelType) {
                    $reservation->reservation_status = $cancelType;
                    $reservation->cancel_date_time = time();
                    $reservation->save();
                });
            } catch (\Exception $e) {
                Log::info('refund command audit accounts error:' . $e->getMessage());
                continue;
            }

            TemairazuService::instance()->sendReservationNotification($reservation->client_id, $reservation->id);
        }
    }
}
