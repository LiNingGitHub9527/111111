<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Client extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'clients';

    protected $fillable = [
        'company_name', 'address', 'tel', 'person_in_charge',
        'email', 'password', 'initial_password', 'sync_status'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($client) {
            $client->clientApiTokens()->get()->each->delete();
        });
    }

    public function apiToken($user)
    {
        $clientId = $user->id;
        $hotelId = $user->current_hotel_id ?? 0;
        $pmsUserId = $user->pms_user_id;
        $expires = $user->expires ?? 1;
        return ClientApiToken::apiToken($clientId, $hotelId, $pmsUserId, $expires);
    }

    public function clientApiTokens(): HasMany
    {
        return $this->hasMany('\App\Models\ClientApiToken');
    }

    public function hotels(): HasMany
    {
        return $this->hasMany('\App\Models\Hotel');
    }
}
