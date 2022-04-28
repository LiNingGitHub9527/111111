<?php

namespace App\Models;

class MailJob extends BaseModel
{
    protected $table = 'mail_jobs';
    
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
    ];

    public function saveMsg($from, $to, $subject, $content, $cc = '', $replyTo = ''): int
    {
        $data = [
            'subject' => $subject,
            'contents' => $content,
            'mail_from' => $from,
            'mail_to' => $to,
            'mail_cc' => $cc,
            'mail_reply_to' => $replyTo,
        ];
        $this->fill($data);
        if ($this->save()) {
            return $this->id;
        }
        return 0;
    }
}
