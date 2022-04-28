<?php

namespace App\Jobs\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Mail;

class HotelSendMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $html;
    private $email;
    private $title;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($html, $email, $title)
    {
        $this->html = $html;
        $this->email = $email;
        $this->title = $title;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            Mail::html($this->html, function ($message) {
                $message->to($this->email)->subject($this->title);
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
