<?php

namespace App\Services;

use App\Jobs\Mail\HtmlMail;
use App\Jobs\Mail\InquiryMail;
use App\Models\MailJob;

class MailService
{
    private static $instance = null;

    public static function instance(): MailService
    {
        if (self::$instance !== null) {
            return self::$instance;
        }
        $instance = new self();
        self::$instance = $instance;
        return $instance;
    }

    public function send($from, $to, $subject, $content, $cc = '', $replyTo = '')
    {
        $from = $this->parseMailAddress($from);
        $to = $this->parseMailAddress($to);
        $cc = $this->parseMailAddress($cc);
        $replyTo = $this->parseMailAddress($replyTo);
        if (!empty($from) && !empty($to) && !empty($subject) && !empty($content)) {
            $mailJob = new MailJob();
            $mailId = $mailJob->saveMsg($from, $to, $subject, $content, $cc, $replyTo);
            if ($mailId > 0) {
                dispatch(new InquiryMail($mailId))->onQueue('mail-job');
            }
        }
    }

    public function parseMailAddress($name): string
    {
        if (empty($name)) {
            return '';
        }
        if (strpos($name, ',') !== false) {
            $names = explode(',', $name);
            $list = [];
            foreach ($names as $name) {
                $list[] = $this->parseOneMailAddress($name);
            }
            $address = implode(',', $list);
        } else {
            $address = $this->parseOneMailAddress($name);
        }
        return $address;
    }

    private function parseOneMailAddress($name): string
    {
        if (strpos($name, '@') === false) {
            $mailDomain = config('mail.domain');
            $name .= '@'.$mailDomain;
        }
        return $name;
    }
}
