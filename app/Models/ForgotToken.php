<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class ForgotToken extends Authenticatable
{

    protected $table = 'forgot_tokens';

    protected $casts = [
        'token_expires_at' => 'datetime'
    ];

    public static function token($email)
    {
        $self = new self();
        ForgotToken::where('email', $email)->delete();

        $token = sha1($email . time() . Str::random(60));
        $self->email = $email;
        $self->token = $token;
        $self->token_expires_at = now()->addDay();
        $self->save();
        return $self->token;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Client', 'email', 'email');
    }
}
