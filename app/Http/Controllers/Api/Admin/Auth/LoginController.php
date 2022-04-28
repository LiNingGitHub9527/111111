<?php

namespace App\Http\Controllers\Api\Admin\Auth;

use App\Http\Requests\Api\Admin\LoginRequest;
use App\Models\Admin;
use Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function login(LoginRequest $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        $user = Admin::where('email', $request->get($this->username()))->first();

        if (empty($user)) {
            return $this->error(trans('auth.error'), 1001);
        }

        if ($this->attemptLoginWithUser($request->password, $user)) {
            return $this->sendLoginResponseWithUser($request, $user);
        }

        $this->incrementLoginAttempts($request);

        return $this->error(trans('auth.failed'), 1002);
    }

    protected function attemptLoginWithUser($password, $user)
    {
        $checked = Hash::check($password, $user->password);

        return $checked;
    }

    protected function sendLockoutResponse(Request $request)
    {
        return $this->error(trans('auth.throttle'), 1003);
    }

    protected function sendLoginResponseWithUser(Request $request, $user)
    {
        $this->clearLoginAttempts($request);

        return $this->authenticated($request, $user);
    }

    protected function authenticated(Request $request, $user)
    {
        $rememberMe = $request->get('rememberMe');
        $expires = null;
        if ($rememberMe) {
            $expires = 1000;
        }
        $data = [
            'token' => $user->apiToken(true, $expires),
            'expires' => $expires
        ];
        return $this->success($data);
    }

    protected function username()
    {
        return 'email';
    }

    protected function success($data = null, $message = 'SUCCESS', $code = 200)
    {
        return response()->json([
            'code' => $code,
            'status' => 'OK',
            'data' => $data,
            'message' => $message,
        ], 200);
    }

    protected function error($message = null, $codeStatus = 400, $codeHeader = 200)
    {
        return response()->json([
            'code' => $codeStatus,
            'status' => 'FAIL',
            'message' => $message,
        ], $codeHeader);
    }
}
