<?php

namespace App\Http\Controllers\Api\Client\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Client\ForgotRequest;
use App\Http\Requests\Api\PasswordRequest;
use App\Models\ForgotToken;
use App\Services\MailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForgotController extends Controller
{
    public function send(ForgotRequest $request)
    {
        $email = $request->get('email');
        $token = ForgotToken::token($email);
        $from =  config('mail.from.address');
        $to = $email;
        $subject = 'パスワード再設定';
        $content = config('signature.front_client_url') . 'forgot/change?token=' . $token;
        MailService::instance()->send($from, $to, $subject, $content);
        return $this->success();
    }

    public function verify(Request $request)
    {
        $token = $request->get('token');
        $forgotToken = ForgotToken::where('token', $token)->first();
        if (empty($forgotToken)) {
            return $this->error('無効なURLです');
        }
        if ($forgotToken->token_expires_at < now()) {
            return $this->error('無効なURLです');
        }
        $data = [
            'email' => $forgotToken->email
        ];
        return $this->success($data);
    }

    public function change(PasswordRequest $request)
    {
        $token = $request->get('token');
        $user = ForgotToken::where('token', $token)->first()->client;
        if(empty($user)){
            return $this->error('クライアントが存在しません');
        }
        $user->initial_password = null;
        $user->password = bcrypt($request->get('newpwd'));
        $user->save();
        try {
            ForgotToken::where('email', $user->email)->delete();
        } catch (\Exception $e) {
            Log::info('delete forgot token failed :' . $e);
        }
        return $this->success();
    }


}
