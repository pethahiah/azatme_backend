<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;


class UrlMiddleware
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
        $allowedOrigins = Config::get('app.allowed_origins', []);

        $origin = $request->header('Origin');
//	$userAgent = $request->header('User-Agent');
//	$referer = $request->header('Referer');

	Log::info('Request Origin: ' . $origin);
//	Log::info('User-Agent: ' . $userAgent);
//	Log::info('Incoming Request:', $request->header());
	
//if ($referer) {
  //          Log::info('Referer: ' . $referer);
    //    }

//	if (strpos($userAgent, 'PostmanRuntime') !== false) {
  //          Log::info('Request from Postman detected.');
    //    }

        if (in_array($origin, $allowedOrigins)) {
            return $next($request)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        }

        return response('Unauthorized', 403);
    }
}
