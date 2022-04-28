<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminApiAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $authed = Auth::guard($guard)->check();
        if (!$authed) {
            return $this->unauthenticated($request);
        }

        return $next($request);
    }

    protected function unauthenticated($request)
    {
        return response()->json([
            'code'    => 401,
            'status'  => 'FAIL',
            'message' => 'Authentication Required',
        ], 401);
    }
}
