<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Auth;

class ApiBaseController extends Controller
{
    protected $guard = 'admin_api';

    protected $user = null;

    protected function guard()
    {
        return Auth::guard($this->guard);
    }

    protected function authed()
    {
        return $this->guard()->check();
    }

    protected function user()
    {
        if (!empty($this->user)) {
            return $this->user;
        }
        if (!empty($guard = $this->guard())) {
            $this->user = $guard->user();
        }
        return $this->user;
    }

}
