<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;


class VerifyUserStatusMiddleware
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
	$user = $request->user();

        if ($user && $user->isVerified === 0) {
            return response()->json(['error' => 'User status is not verified'], 403);
        }

        return $next($request);
    }
}
