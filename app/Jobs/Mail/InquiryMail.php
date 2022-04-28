<?php

namespace App\Jobs\Mail;

use App\Models\MailJob;
use App\Models\MailJobHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class InquiryMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $mailId;

    /**
     * Create a new job instance.
     *
     * @param $mailId
     */
    public function __construct($mailId)
    {
        $this->mailId = $mailId;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        return ['inquiry-mail', 'mailId:' . $this->mailId];
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(): bool
    {
        $isException = false;
        $mail = MailJob::find($this->mailId);
        if (!empty($mail)) {
            try {
                Mail::raw($mail->contents, function ($message) use ($mail) {
                    if (app()->isProduction()) {
                        $defaultFromName = config('mail.from.name');
                        $message->from($mail->mail_from, $defaultFromName);
                    }
                    $message->subject($mail->subject);
                    $to = $mail->mail_to;
                    if (strpos($to, ',') !== false) {
                        $to = explode(',', $to);
                    }
                    $message->to($to);
                    if (!empty($mail->mail_cc)) {
                        $cc = $mail->mail_cc;
                        if (strpos($cc, ',') !== false) {
                            $cc = explode(',', $cc);
                        }
                        $message->cc($cc);
                    }
                    if (!empty($mail->mail_reply_to)) {
                        $message->replyTo($mail->mail_reply_to);
                    }
                });
            } catch (\Exception $e) {
                $isException = true;
                Log::info($e->getMessage());
            }
            if (!$isException) {
                $mail->mail_status = 1;
                if ($mail->save()) {
                    $mailJobHistory = new MailJobHistory();
                    $mailJobHistoryId = $mailJobHistory->saveMsg($mail);
                    if ($mailJobHistoryId > 0) {
                        $mail->delete();
                    }
                }
            } else {
                $mail->remain = $mail->remain - 1;
                $mail->save();
                if ($mail->remain > 0) {
                    dispatch(new InquiryMail($mail->id))->onQueue('mail-job')->delay(10);
                }
            }
        }
        return true;
    }
}
