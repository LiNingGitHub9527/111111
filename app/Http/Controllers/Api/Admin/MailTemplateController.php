<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\MailTemplate;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Admin\MailTemplateRequest;

class MailTemplateController extends Controller
{

    public function detail($type)
    {
        $mailTemplate = MailTemplate::where('type', $type)->first();

        if (empty($mailTemplate)) {
            return $this->error('データが存在しません', 404);
        }

        $detail = [
            'id' => $mailTemplate->id,
            'subject' => $mailTemplate->subject,
            'body' => $mailTemplate->body,
            'bcc' => $mailTemplate->bcc,
            'type' => $mailTemplate->type
        ];
        $data = [
            'detail' => $detail,
        ];
        return $this->success($data);
    }
    

    public function save(MailTemplateRequest $request)
    {
        MailTemplate::updateOrCreate([
            'type' => $request->type
        ], $request->all());
        return $this->success();
    }
}
