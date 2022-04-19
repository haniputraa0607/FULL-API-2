<?php

namespace App\Http\Middleware;

use App\Http\Models\LogActivitiesEmployeeApp;
use Closure;
use App\Lib\MyHelper;
use Auth;

class LogActivitiesEmployeeAppsMiddleware
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
        $response = $next($request);

        $arrReq = $request->except('_token');
        if(!isset($arrReq['log_save'])){

            if(!isset($arrReq['page']) || (int)$arrReq['page'] <= 1){

                $user = json_encode($request->user());
                $url = $request->url();
                $user = json_decode(json_encode($request->user()), true);
                $st = stristr(json_encode($response),'success');
                $status = 'fail';
                if($st) $status = 'success';
                $reqnya = $request->json()->all();
                if(isset($reqnya['pin'])) $reqnya['pin'] = "******";
                if(isset($reqnya['pin_old'])) $reqnya['pin'] = "******";
                if(isset($reqnya['pin_new'])) $reqnya['pin'] = "******";
                $requestnya = json_encode($reqnya);
                $requeste = json_decode($requestnya, true);
                
                if($requestnya == '[]') $requestnya = null;
                $urlexp = explode('/',$url);

                if(stristr($url, 'employee/phone/check')) $subject = 'Phone Check';
                if(stristr($url, 'employee/pin/forgot')) $subject = 'Pin Forgot';
                if(stristr($url, 'employee/pin/verify')) $subject = 'Pin Verify';
                if(stristr($url, 'employee/pin/change')) $subject = 'Pin Change';
                if(stristr($url, 'employee/announcement')) $subject = 'Announcement';
                if(stristr($url, 'employee/home')) $subject = 'Home';
                if(stristr($url, 'employee/schedule')) $subject = 'Schedule';

                
                if(!empty($request->header('ip-address-view'))){
                    $ip = $request->header('ip-address-view');
                }else{
                    $ip = $request->ip();
                }

                $userAgent = $request->header('user-agent');
                
                $dtUser = null;
                
                if(!empty($user) && $user != ""){
                    $dtUser = json_encode($request->user());
                }

                if(isset($request->user()->phone)){
                    $phone = $request->user()->phone;
                }elseif(isset($reqnya['phone'])){
                    $phone = $reqnya['phone'];
                }
                
                $data = [
                    'url' 		        => $url,
                    'subject' 	        => $subject??'Unknown',
                    'phone' 		    => $phone??null,
                    'user' 		        => $dtUser,
                    'request' 		    => $requestnya,
                    'response_status'   => $status,
                    'response' 		    => json_encode($response),
                    'ip' 		        => $ip,
                    'useragent' 	    => $userAgent
                ];

                $log = LogActivitiesEmployeeApp::create($data);
            }

        }
        return $response;
    }
}
