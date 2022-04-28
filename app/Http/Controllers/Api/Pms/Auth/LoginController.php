<?php

namespace App\Http\Controllers\Api\Pms\Auth;

use App\Models\ClientApiToken;
use App\Models\Hotel;
use Hash;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    public function login(Request $request)
    {
        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }
        $hotel = Hotel::find($request->get('hotel_id'));
        if (empty($hotel)) {
            return $this->error('ホテル' . trans('auth.throttle'));
        }
        $user = $hotel->client;

        if (empty($user)) {
            return $this->error('クライアント' . trans('auth.throttle'));
        }
        $user->current_hotel_id = $hotel->id;

        $user->pms_user_id = $request->get('pms_user_id');
        $user->main_api_token = $request->get('main_api_token');
        $user->redirect_url = $request->get('redirect_url');

        return $this->sendLoginResponseWithUser($request, $user);
    }

    protected function sendLockoutResponse(Request $request)
    {
        return $this->error(trans('auth.throttle'));
    }

    protected function sendLoginResponseWithUser(Request $request, $user)
    {
        $this->clearLoginAttempts($request);

        return $this->authenticated($request, $user);
    }

    protected function authenticated(Request $request, $user)
    {
        $token = $user->main_api_token;
        if (empty($token)) {
            $token = $user->apiToken($user);
        } else {
            $pmsUserId = $user->pms_user_id;
            if (!empty($pmsUserId)) {
                $clientApiToken = ClientApiToken::where('api_token', $token)->first();
                $clientApiToken->pms_user_id = $pmsUserId;
                $clientApiToken->save();
            }
        }
        $urlParams = [
            'token' => $token,
            'hotelId' => $user->current_hotel_id,
            'redirectUrl' => $user->redirect_url
        ];
        return redirect()->away($this->redirectPath() . '?' . http_build_query($urlParams, null, '&', PHP_QUERY_RFC3986));
    }

    public function redirectPath()
    {
        return config('signature.front_client_url') . 'pms-login';
    }

    protected function error($message = null)
    {
        echo $message;
    }
}
