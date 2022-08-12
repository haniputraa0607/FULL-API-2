<?php

namespace App\Http\Middleware;

use App\Http\Models\LogActivitiesMitraApp;
use Closure;
use App\Http\Models\LogActivitiesOutletApps;
use App\Lib\MyHelper;
use Auth;

class LogActivitiesMitraAppsMiddleware
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

                if(stristr($url, 'mitra/phone/check')) $subject = 'Phone Check';
                if(stristr($url, 'mitra/pin/forgot')) $subject = 'Pin Forgot';
                if(stristr($url, 'mitra/pin/verify')) $subject = 'Pin Verify';
                if(stristr($url, 'mitra/pin/change')) $subject = 'Pin Change';
                if(stristr($url, 'mitra/announcement')) $subject = 'Announcement';
                if(stristr($url, 'mitra/home')) $subject = 'Home';
                if(stristr($url, 'mitra/schedule')) $subject = 'Schedule';
                if(stristr($url, 'mitra/outlet-service')) $subject = 'Outlet Service';
                if(stristr($url, 'mitra/shop-service')) $subject = 'Shop Service';
                if(stristr($url, 'mitra/inbox')) $subject = 'Inbox';
                if(stristr($url, 'mitra/rating')) $subject = 'Rating';
                if(stristr($url, 'mitra/home-service')) $subject = 'Home Service';
                if(stristr($url, 'mitra/data-update-request')) $subject = 'Data Update Request';
                if(stristr($url, 'mitra/income')) $subject = 'Income';
                if(stristr($url, 'mitra/income')) $subject = 'attendance';
                if(stristr($url, 'mitra/request')) $subject = 'Request';
                
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

                try {
                    $log = LogActivitiesMitraApp::create($data);
                } catch (\Exception $e) {

                }
            }

        }
        return $response;
    }
}
