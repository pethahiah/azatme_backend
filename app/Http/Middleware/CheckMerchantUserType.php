<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckMerchantUserType
{
 /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the user is authenticated
        if (auth()->check()) {
            $userType = auth()->user()->usertype;

            // Allow access if user type is 'business' or 'sponsor'
            if (in_array($userType, ['merchant', 'sponsor'])) {
                return $next($request);
            }
        }

        return response()->json(['message' => 'Unauthorized. Only business or sponsor users are allowed.'], 403);
    }
}
