<?php

namespace App\Http\Middleware;

use Closure;
use App\Http\Models\Setting;

class AuthPOS2
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$paramsSignature)
    {
        $api_key = Setting::where('key', 'api_key')->pluck('value')->first();
        $api_secret = Setting::where('key', 'api_secret')->pluck('value')->first();

        if ($api_key != $request->api_key) {
            return response([
                'status' => 'fail',
                'messages' => ['Invalid api key']
            ], 401);
        }

        $to_sign = '';
        foreach($paramsSignature as $param) {
            $to_sign .= $request->$param;
        }

        $signature = hash_hmac('sha256', $to_sign, $api_secret);

        $debug_messages = [];
        if (!app()->environment('production')) {
            if ($request->bypass_signature == 'ok') {
                $signature = $request->signature;
            }
            if ($signature != $request->signature) {
                $debug_messages[] = '[DEBUG_MSG] Signature should be ' . $signature;
                $debug_messages[] = '[DEBUG_MSG] Formula: hash_hmac(\'sha256\', $' . implode(' . $', $paramsSignature) . ', $api_secret)';
            }
        }


        if ($signature != $request->signature) {
            return response([
                'status' => 'fail',
                'messages' => array_merge(['Signature doesn\'t match'], $debug_messages),
            ], 401);
        }

        return $next($request);
    }
}
