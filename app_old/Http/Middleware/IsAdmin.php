<?php

namespace App\Http\Middleware;

use Closure;
use Auth;


class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->usertype === 'admin') {
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
    }
}
