<?php

namespace App\Models;

class MailJobHistory extends BaseModel
{
    protected $table = 'mail_job_histories';
    
    protected $fillable = [
        'mail_to',
        'mail_from',
        'mail_cc',
        'mail_reply_to',
        'subject',
        'contents',
        'charset',
        'remain',
        'mail_status',
        'created_at',
        'updated_at',
    ];

    public function saveMsg($mail): int
    {
        $data = [
            'subject' => $mail->subject,
            'contents' => $mail->contents,
            'mail_from' => $mail->mail_from,
            'mail_to' => $mail->mail_to,
            'mail_cc' => $mail->mail_cc,
            'mail_reply_to' => $mail->mail_reply_to,
            'charset' => $mail->charset,
            'remain' => $mail->remain,
            'mail_status' => $mail->mail_status,
            'created_at' => $mail->created_at,
            'updated_at' => $mail->updated_at,
        ];
        $this->fill($data);
        if ($this->save()) {
            return $this->id;
        }
        return 0;
    }
}
