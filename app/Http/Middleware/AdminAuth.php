<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class AdminAuth
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
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
            $request->session()->put('__redirect_url', $request->getRequestUri());
            return redirect()->route('admin.login');
        }
        $user = Auth::guard($guard)->user();
        view()->composer('admin.*', function($view) use ($user) {
            $view->with('__admin', $user);
        });
        return $next($request);
    }
}
