<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class UserAuth
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
            $request->session()->put('__redirect_url', $request->getRequestUri());
            return redirect()->route('user.login');
        }
        $user = Auth::guard($guard)->user();
        view()->composer('user.*', function($view) use ($user) {
            $view->with('__user', $user);
        });
        return $next($request);
    }
}
