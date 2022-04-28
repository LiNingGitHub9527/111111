<?php

namespace App\Http\Controllers\PmsApi\User;

use Illuminate\Routing\Controller;
use Auth;

class ApiBaseController extends Controller
{
    // protected $guard = 'admin_api';

    // protected $user = null;

    // protected function guard()
    // {
    //     return Auth::guard($this->guard);
    // }

    // protected function authed()
    // {
    //     return $this->guard()->check();
    // }

    // protected function user()
    // {
    //     if (!empty($this->user)) {
    //         return $this->user;
    //     }
    //     if (!empty($guard = $this->guard())) {
    //         $this->user = $guard->user();
    //     }
    //     return $this->user;
    // }

    public function success($data = null, $message = 'SUCCESS', $code = 200)
    {
        return response()->json([
            'code' => $code,
            'status' => 'OK',
            'data' => $data,
            'message' => $message,
        ], 200);
    }

    public function error($message = null, $codeStatus = 400, $codeHeader = 200)
    {
        return response()->json([
            'code' => $codeStatus,
            'status' => 'FAIL',
            'message' => $message,
        ], $codeHeader);
    }
}
