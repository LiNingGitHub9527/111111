<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClientApiToken extends Authenticatable
{

    protected $table = 'client_api_tokens';

    protected $casts = [
        'api_token_expires_at' => 'datetime'
    ];

    public static function apiToken($clientId, $hotelId, $pmsUserId, $expires)
    {
        $token = sha1(time() . Str::random(60)) . '_' . $hotelId;
        $self = new self();
        $self->api_token = $token;
        $self->api_token_expires_at = now()->addDays($expires);
        $self->client_id = $clientId;
        $self->pms_user_id = $pmsUserId;
        $self->save();
        try {
            $self::where('client_id', $clientId)->where('api_token_expires_at', '<', now())->delete();
        } catch (\Exception $e) {
            Log::info('delete failed :' . $e);
        }
        return $self->api_token;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo('\App\Models\Client');
    }
}
