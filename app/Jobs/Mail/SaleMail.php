<?php

namespace App\Jobs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SaleMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $hotel;
    protected $content;
    protected $subject;
    protected $bcc;
    protected $attachmentName;
    protected $month;
    protected $hotelId;

    public function __construct($hotelId, $month, $subject, $content, $bcc, $attachmentName=null)
    {
        $this->hotelId = $hotelId;
        $this->content = $content;
        $this->subject = $subject;
        $this->month = $month;
        $this->bcc = empty($bcc) ? null : $bcc;
        $this->attachmentName = empty($attachmentName) ? 'sale.pdf' : $attachmentName;
    }

    public function handle()
    {
        list($monthDayStart, $monthDayEnd) = getMonthDayStart($this->month);
        list($start, $end) = getFormatedStartEndDatesForSaleMail($monthDayStart, $monthDayEnd);

        $hotel = app()->make('CommonHotelService')->getHotelsWithReservationWithDateRange($start, $end)->where('id', $this->hotelId)->first();
        if(empty($hotel)) {
           return false; 
        }
        $tempContent = replceKeyWithValueFromText($this->content, [
            '【担当者名】' => $hotel->client->person_in_charge,
            '【施設名】' => $hotel->client->company_name,
            '【ホテル名】' => $hotel->name
        ]);

        $hotelService = app()->make('CommonHotelService');
        $pdf = $hotelService->getSalePdf($hotel->client->company_name, $hotel->name, $hotel->transfer_amount);
        
        try {
            Mail::raw($tempContent, function ($message) use($pdf, $hotel) {
                $message
                    ->to($hotel->client->email)
                    ->subject($this->subject)
                    ->attachData($pdf->output(), $this->attachmentName);
                if ($this->bcc) {
                    $message->bcc($this->bcc, '');
                }
            });
            if (count(Mail::failures()) > 0) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
