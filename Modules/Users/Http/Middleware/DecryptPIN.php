<?php

namespace Modules\Users\Http\Middleware;

use App\Http\Models\OauthAccessToken;
use Closure;
use Illuminate\Http\Request;
use App\Lib\MyHelper;
use Lcobucci\JWT\Parser;

class DecryptPIN
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $colname = 'pin', $column = 'phone')
    {
        if(!empty($request->scope)){
            $scopeUser = $request->scope;
        }elseif($request->user()){
            $dataToken = json_decode($request->user()->token());
            $scopeUser = $dataToken->scopes[0];
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

        if ($column == 'request') {
            $column = 'user_phone';
            $request->user_phone = ($scopeUser == 'mitra-apps' ? $request->user()->phone_number : $request->user()->phone);
        }

        if ($request->{$colname.'_encrypt'} && !$request->$colname) {
            $jsonRequest = $request->all();
            $decrypted = MyHelper::decryptPIN($request->{$colname.'_encrypt'}, $request->$column, $scopeUser);
            if (!$decrypted) {
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Invalid PIN']
                ]);
            }
            $jsonRequest[$colname] = $decrypted;
            $request->replace($jsonRequest);
        }
        return $next($request);
    }
}
