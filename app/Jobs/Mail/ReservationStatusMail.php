<?php

namespace App\Jobs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class ReservationStatusMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $contents;
    private $email;
    private $subject;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($contents, $email, $subject)
    {
        $this->contents = $contents;
        $this->email = $email;
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
