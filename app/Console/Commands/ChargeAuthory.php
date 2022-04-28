<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use App\Models\ReservationCaptured;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ChargeAuthory extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shareg:chargeauthory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Charge the authority the day after the last day of the free cancellation period';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->stripe_service = app()->make('StripeService');
        $this->receipt_service = app()->make('ReceiptService');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $before2days = Carbon::now()->subDays(2)->format('Y-m-d');

        // 予約を取得する
        $reservations = Reservation::where('checkin_time', '<', $before2days)
            ->where('payment_method', 1)
            ->where('payment_status', config('prepay.payment_status.authory'))
            ->where('reservation_status', 0)
            ->get();

        // 予約一つずつオーソリをチャージするかチェック
        foreach ($reservations as $reservation) {
            $paymentData = [];
            $reservationCaptured = ReservationCaptured::where('reservation_id', $reservation->id)->first();
            $result = false;

            if (empty($reservationCaptured)) {
                $reservationCaptured = new ReservationCaptured([
                    'reservation_id' => $reservation->id,
                    'payment_status' => 2,
                ]);
                $reservationCaptured->save();
            } else {
                if ($reservationCaptured->payment_status == 1) {
                    continue;
                }
                if ($reservationCaptured->payment_status == 2) {
                    $result = $this->stripe_service->captureAuditAccounts($reservation->stripe_payment_id, $paymentData);
                }
            }

            if (!$result) {
                $result = $this->stripe_service->manageChargeAuthoryById($reservation->id, $reservation->stripe_payment_id, null, $paymentData);
            }

            try {
                \DB::transaction(function () use ($result, $paymentData, $reservationCaptured, $reservation) {
                    $paymentInformation = $paymentData['message'];
                    $capturedStatus = false;
                    $paymentStatus = 0;
                    $now = Carbon::now();
                    if ($result) {
                        $capturedStatus = true;
                        $paymentStatus = 1;
                        $reservationCaptured->update([
                            'payment_amount' => $paymentData['payment_amount'],
                            'amount_captured' => $paymentData['amount_captured'],
                            'stripe_payment_id' => $paymentData['stripe_payment_id']
                        ]);
                        // payment success
                        $reservation->update(['payment_status' => 1]);
                    }
                    $reservationCaptured->update([
                        'captured_status' => $capturedStatus,
                        'payment_status' => $paymentStatus,
                        'handle_date' => $now,
                        'payment_information' => $paymentInformation
                    ]);
                });
                if ($result) {
                    $this->receipt_service->send($paymentData['stripe_payment_id']);
                }
            } catch (\Exception $e) {
                Log::info('authory capture error :' . $e);
                continue;
            }
        }
    }
}
