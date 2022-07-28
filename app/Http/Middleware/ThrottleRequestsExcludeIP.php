<?php

namespace App\Http\Middleware;

use Closure;

class ThrottleRequestsExcludeIP extends \Illuminate\Routing\Middleware\ThrottleRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '')
    {
        if(in_array($request->ip(), explode(',', config('url.be_ip')))) {
            return $next($request);
        }

        return parent::handle($request, $next, $maxAttempts, $decayMinutes, $prefix);
    }
}
