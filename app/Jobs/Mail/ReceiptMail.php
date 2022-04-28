<?php

namespace App\Jobs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class ReceiptMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $contents;
    private $email;
    private $subject;
    private $pdf;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($contents, $email, $subject, $pdf)
    {
        $this->contents = $contents;
        $this->email = $email;
        $this->pdf = $pdf;
        $this->subject = $subject;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::html($this->contents, function ($message) {
                $message->to($this->email)->subject($this->subject);
                $message->attachData($this->pdf->output(), 'receipt.pdf');
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
