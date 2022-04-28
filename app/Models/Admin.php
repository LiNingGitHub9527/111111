<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;

class Admin extends Authenticatable
{
    protected $table = 'admins';

    protected $rememberTokenName = '';

    protected $fillable = [
        'name', 'email', 'password',
    ];

    protected $casts = [
        'api_token_expires_at' => 'datetime'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password'
    ];

    public function setApiToken($token)
    {
        $this->api_token = $token;
        $timestamps = $this->timestamps;
        $this->timestamps = false;
        $this->save();
        $this->timestamps = $timestamps;
    }

    public function refreshApiToken($expires = 1)
    {
        $token = sha1(time() . Str::random(60));
        $this->api_token = $token;
        $this->api_token_expires_at = now()->addDays($expires);
    }

    public function bearerToken($refresh = false)
    {
        return $this->apiToken($refresh);
    }

    public function apiToken($refresh = false, $expires = null)
    {
        $updateApiToken = $refresh;
        if (!empty($this->api_token) && !empty($this->api_token_expires_at)) {
            if (now() >= $this->api_token_expires_at) {
                $updateApiToken = true;
            }
        } else {
            $updateApiToken = true;
        }
        if ($updateApiToken) {
            $this->refreshApiToken($expires);
            $this->save();
        }

        return $this->api_token;
    }
}
