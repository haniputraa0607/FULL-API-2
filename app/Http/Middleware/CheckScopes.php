<?php

namespace App\Http\Middleware;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\Setting;
use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Passport\Passport;
use Lcobucci\JWT\Parser;
use SMartins\PassportMultiauth\Http\Middleware\AddCustomProvider;

class CheckScopes extends AddCustomProvider
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, $scope = null, $scope2 = null)
    {
        $mtScope = ['apps', 'web-apps', 'mitra-apps', 'client', 'employee-apps'];
        if (in_array($scope,$mtScope) || in_array($scope2,$mtScope)) {
            $getMaintenance = Setting::where('key', 'maintenance_mode')->first();
            if($getMaintenance && $getMaintenance['value'] == 1){
                $dt = (array)json_decode($getMaintenance['value_text']);
                $message = $dt['message'];
                if($dt['image'] != ""){
                    $url_image = config('url.storage_url_api').$dt['image'];
                }else{
                    $url_image = config('url.storage_url_api').'img/maintenance/default.png';
                }
                return response()->json([
                    'status' => 'fail',
                    'messages' => [$message],
                    'maintenance' => config('url.api_url') ."api/maintenance-mode",
                    'data_maintenance' => [
                        'url_image' => $url_image,
                        'text' => $message
                    ]
                ], 200);
            }
        }

        if($request->user()){
            $dataToken = json_decode($request->user()->token());
            $scopeUser = $dataToken->scopes[0];

            if($scope == 'employee-apps' && empty($request->user()->id_role)){
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
        }else{
            try{
                $bearerToken = $request->bearerToken();
                $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
                $getOauth = OauthAccessToken::find($tokenId);
                $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);
            }catch (\Exception $e){
                return response()->json(['error' => 'Unauthenticated.'], 401);
            }
        }

        $arrScope = ['pos', 'be', 'partners', 'apps', 'web-apps', 'landing-page', 'franchise-client', 'franchise-super-admin',
            'franchise-user', 'mitra-apps', 'outlet-display', 'client','employees', 'employee-apps'];
        if((in_array($scope, $arrScope) && $scope == $scopeUser) ||
            (in_array($scope2,$arrScope) && $scope2 == $scopeUser)){
            return $next($request);
        }

        return response()->json(['error' => 'Unauthenticated.'], 401);
    }
}