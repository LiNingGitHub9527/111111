<?php

namespace App\Http\Controllers\Api\Pms;

use App\Http\Controllers\Controller;

class ApiBaseController extends Controller
{
    protected $guard = 'pms_api';
}
