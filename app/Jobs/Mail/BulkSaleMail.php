<?php

namespace App\Jobs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BulkSaleMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $content;
    protected $subject;
    protected $bcc;
    protected $month;

    public function __construct($month, $subject, $content, $bcc)
    {
        $this->content = $content;
        $this->subject = $subject;
        $this->month = $month;
        $this->bcc = empty($bcc) ? null : $bcc;
    }

    public function handle()
    {
        list($monthDayStart, $monthDayEnd) = getMonthDayStart($this->month);
        list($start, $end) = getFormatedStartEndDatesForSaleMail($monthDayStart, $monthDayEnd);

        $hotels = app()->make('CommonHotelService')->getHotelsWithReservationWithDateRange($start, $end)->get();

        foreach($hotels as $hotel) {
            $tempContent = replceKeyWithValueFromText($this->content, [
                '【担当者名】' => $hotel->client->person_in_charge,
                '【施設名】' => $hotel->client->company_name,
                '【ホテル名】' => $hotel->name
            ]);

            $hotelService = app()->make('CommonHotelService');
            $pdf = $hotelService->getSalePdf($hotel->client->company_name, $hotel->name, $hotel->transfer_amount);
            $attachmentName = getSaleMailAttachmentName($monthDayStart);

            try {
                Mail::raw($tempContent, function ($message) use($pdf, $hotel, $attachmentName) {
                    $message
                        ->to($hotel->client->email)
                        ->subject($this->subject)
                        ->attachData($pdf->output(), $attachmentName);
                    if ($this->bcc) {
                        $message->bcc($this->bcc, '');
                    }
                });
            } catch (\Exception $e) {
                Log::info('Failed to send email.\n'.$e);
                continue;
            }
        }
    }
}
