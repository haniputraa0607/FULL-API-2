<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserLocation;
use App\Http\Models\Level;
use App\Http\Models\Doctor;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use Modules\Balance\Http\Controllers\BalanceController;
use Modules\Users\Entities\OldMember;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone;
use Modules\Users\Http\Requests\users_phone_pin_admin;
use Modules\Users\Http\Requests\usersNewPinEmployee;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use Lcobucci\JWT\Parser;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Auth;
use Modules\Users\Http\Requests\users_phone_pin_new;

class ApiUserV2 extends Controller
{
    function __construct()
    {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');
        $this->home     = "Modules\Users\Http\Controllers\ApiHome";
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->membership  = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->inbox  = "Modules\InboxGlobal\Http\Controllers\ApiInbox";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->deals = "Modules\Deals\Http\Controllers\ApiDeals";
        $this->welcome_subscription = "Modules\Subscription\Http\Controllers\ApiWelcomeSubscription";
    }

    function phoneCheck(users_phone $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $bearerToken = request()->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::select('*',\DB::raw('0 as challenge_key'))->with('city')->where('phone', '=', $phone)->get()->toArray();

        if (isset($data[0]['is_suspended']) && $data[0]['is_suspended'] == '1') {
            $emailSender = Setting::where('key', 'email_sender')->first();
            return response()->json([
                'status' => 'fail',
                'messages' => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di '.$emailSender['value']??'']
            ]);
        }

        // switch (env('OTP_TYPE', 'PHONE')) {
        //     case 'MISSCALL':
        //         $msg_check = str_replace('%phone%', $phoneOld, MyHelper::setting('message_send_otp_miscall', 'value_text', 'Kami akan mengirimkan kode OTP melalui Missed Call ke %phone%.<br/>Anda akan mendapatkan panggilan dari nomor 6 digit.<br/>Nomor panggilan tsb adalah Kode OTP Anda.'));
        //         break;

        //     case 'WHATSAPP':
        //         $msg_check = str_replace('%phone%', $phoneOld, MyHelper::setting('message_send_otp_wa', 'value_text', 'Kami akan mengirimkan kode OTP melalui Whatsapp.<br/>Pastikan nomor %phone% terdaftar di Whatsapp.'));
        //         break;

        //     default:
        //         $msg_check = str_replace('%phone%', $phoneOld, MyHelper::setting('message_send_otp_sms', 'value_text', 'Kami akan mengirimkan kode OTP melalui SMS.<br/>Pastikan nomor %phone% aktif.'));
        //         break;
        // }
        $msg_check = str_replace('%phone%', $phoneOld, MyHelper::setting('message_send_otp_miscall', 'value_text', 'Kami akan mengirimkan kode OTP melalui ke %phone%.<br/>Anda akan mendapatkan kode berupa 6 digit angka.<br/>Silahkan pilih metode pengiriman OTP yang akan digunakan.'));

        if($data){
            if (($data[0]['phone_verified'] == 0 && empty($data[0]['pin_changed'])) && ($scopeUser != 'pos-order' && $data[0]['from_pos'] == 0)) {
                $result['register'] = true;
                $result['forgot'] = false;
                $result['confirmation_message'] = $msg_check;
                return response()->json([
                    'status' => 'success',
                    'result' => $result
                ]);
            }else{
                $result['register'] = false;
                $result['forgot'] = false;
                $result['challenge_key'] = $data[0]['challenge_key'];
                $result['confirmation_message'] = $msg_check;
                return response()->json([
                    'status' => 'success',
                    'result' => $result
                ]);
            }

        }else{
            return response()->json([
                'status' => 'success',
                'result' => [
                    'register' => true,
                    'forgot' => false,
                    'confirmation_message' => $msg_check
                ]
            ]);
        }
    }

    function pinRequest(users_phone $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);
        $bearerToken = request()->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);
        
        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        //get setting rule otp
        $setting = Setting::where('key', 'otp_rule_request')->first();

        $holdTime = 30;//set default hold time if setting not exist. hold time in second
        if($setting && isset($setting['value_text'])){
            $setting = json_decode($setting['value_text']);
            $holdTime = (int)$setting->hold_time;
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();

        if (!$data) {
            $pin = MyHelper::createRandomPIN(6, 'angka');
            // $pin = '777777';

            $provider = MyHelper::cariOperator($phone);
            $is_android     = null;
            $is_ios         = null;
            $device_id = $request->json('device_id');
            $device_token = $request->json('device_token');
            $device_type = $request->json('device_type');
            
            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS')) $useragent = 'IOS';
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) $useragent = 'Android';
            if (stristr($_SERVER['HTTP_USER_AGENT'], 'GuzzleHttp')) $useragent = 'Browser';

            if(empty($device_type)){
                $device_type = $useragent;
            }

            if($device_type == "Android") {
                $is_android = 1;
            } elseif($device_type == "IOS"){
                $is_ios = 1;
            }

            if ($request->json('device_token') != "") {
                $device_token = $request->json('device_token');
            }

            //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
            $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
            if($getSettingTimeExpired){
                $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
            }else{
                $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
            }

            $create = User::create([
                'phone' => $phone,
                'name' => $request->json('name') ?? null,
                'provider'         => $provider,
                'password'        => bcrypt($pin),
                'android_device' => $is_android,
                'from_pos'      => $scopeUser == 'pos-order' ? 1 : 0,
                'ios_device'     => $is_ios,
                'otp_valid_time' => $dateOtpTimeExpired
            ]);

            if ($create) {
                $checkRuleRequest = MyHelper::checkRuleForRequestOTP([$create]);
                if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                    return response()->json($checkRuleRequest);
                }

                if ($request->json('device_id') && $request->json('device_token') && $device_type) {
                    app($this->home)->updateDeviceUser($create, $request->json('device_id'), $request->json('device_token'), $device_type);
                }
            }


            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Pin Create',
                    $phone,
                    []
                );
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Pin Sent',
                    $phone,
                    [
                        'pin' => $pin,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s'),
                        'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                    ],
                    $useragent,
                    false, false, null, null, true, $request->request_type
                );
            }

            app($this->membership)->calculateMembership($phone);

            //create user location when register
            if ($request->json('latitude') && $request->json('longitude')) {
                $userLocation = UserLocation::create([
                    'id_user' => $create['id'],
                    'lat' => $request->json('latitude'),
                    'lng' => $request->json('longitude'),
                    'action' => 'Register'
                ]);
            }

            switch (strtoupper($request->request_type)) {
                case 'MISSCALL':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
                    break;

                case 'WHATSAPP':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
                    break;

                default:
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
                    break;
            }


            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $create->phone,
                        'autocrm'  =>    $autocrm,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $create->getChallengeKeyAttribute(),
                        'forget' => false
                    ]
                ];
            } else {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $create->phone,
                        'autocrm'    =>    $autocrm,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $create->getChallengeKeyAttribute(),
                        'forget' => false
                    ]
                ];
            }
            return response()->json($result);
        } else {
            //First check rule for request otp
            $checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
            if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                return response()->json($checkRuleRequest);
            }

            if($checkRuleRequest == true && !isset($checkRuleRequest['otp_timer'])){
                $pinnya = MyHelper::createRandomPIN(6, 'angka');
                $pin = bcrypt($pinnya);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if($getSettingTimeExpired){
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
                }else{
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                if(!empty($data[0]['pin_changed'])){
                    $update = User::where('phone', '=', $phone)->update(['otp_forgot' => $pin, 'otp_valid_time' => $dateOtpTimeExpired]);
                }else{
                    $update = User::where('phone', '=', $phone)->update(['password' => $pin, 'otp_valid_time' => $dateOtpTimeExpired]);
                }

                $useragent = $_SERVER['HTTP_USER_AGENT'];
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'iOS')) $useragent = 'iOS';
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'okhttp')) $useragent = 'Android';
                if (stristr($_SERVER['HTTP_USER_AGENT'], 'GuzzleHttp')) $useragent = 'Browser';

                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Pin Create',
                        $phone,
                        []
                    );
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Pin Sent',
                        $phone,
                        [
                            'pin' => $pinnya,
                            'useragent' => $useragent,
                            'now' => date('Y-m-d H:i:s'),
                            'date_sent' => date('d-m-y H:i:s'),
                            'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                        ],
                        $useragent,
                        false, false, null, null, true, $request->request_type
                    );
                }
            }elseif(isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false){
                $holdTime = $checkRuleRequest['otp_timer'];
            }

            switch (strtoupper($request->request_type)) {
                case 'MISSCALL':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
                    break;

                case 'WHATSAPP':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
                    break;

                default:
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
                    break;
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $data[0]['phone'],
                        'message'  =>    $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => (empty($data[0]['email']) ? false : true)
                    ]
                ];
            } else {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $data[0]['phone'],
                        'message' => $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => (empty($data[0]['email']) ? false : true)
                    ]
                ];
            }

            return response()->json($result);
        }
    }

    function changePin(users_phone_pin_new_v2 $request)
    {

        $phone = $request->json('phone');

        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();
        if ($data) {
            if(!empty($data[0]['otp_forgot']) && !empty($data[0]['phone_verified']) && !password_verify($request->json('pin_old'), $data[0]['otp_forgot'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Current PIN doesn\'t match']
                ]);
            }elseif(empty($data[0]['otp_forgot']) && !empty($data[0]['pin_changed']) && !empty($data[0]['phone_verified']) && !Auth::attempt(['phone' => $phone, 'password' => $request->json('pin_old')])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Current PIN doesn\'t match']
                ]);
            }

            $pin     = bcrypt($request->json('pin_new'));
            $update = User::where('id', '=', $data[0]['id'])->update(['password' => $pin, 'otp_forgot' => null, 'phone_verified' => '1', 'pin_changed' => '1']);
            if (\Module::collections()->has('Autocrm')) {
                if ($data[0]['first_pin_change'] < 1) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone);
                    $changepincount = $data[0]['first_pin_change'] + 1;
                    $update = User::where('id', '=', $data[0]['id'])->update(['first_pin_change' => $changepincount]);
                } else {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed Forgot Password', $phone);

                    $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->where('oauth_access_tokens.user_id', $data[0]['id'])->where('oauth_access_token_providers.provider', 'users')->delete();
                }
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            $result = [
                'status'    => 'success',
                'result'    => [
                    'phone'    =>    $data[0]['phone'],
                    'challenge_key' => $user->challenge_key
                ]
            ];
        } else {
            $result = [
                'status'    => 'fail',
                'messages'    => ['This phone number isn\'t registered']
            ];
        }
        return response()->json($result);
    }

    function forgotPin(users_forgot $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        //get setting rule otp
        $setting = Setting::where('key', 'otp_rule_request')->first();

        $holdTime = 30;//set default hold time if setting not exist. hold time in second
        if($setting && isset($setting['value_text'])){
            $setting = json_decode($setting['value_text']);
            $holdTime = (int)$setting->hold_time;
        }

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'otp_timer' => $holdTime,
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $user = User::where('phone', '=', $phone)->first();

        if (!$user) {
            $result = [
                'status'    => 'fail',
                'otp_timer' => $holdTime,
                'messages'    => ['User not found.']
            ];
            return response()->json($result);
        }

        $user->sms_increment = 0;
        $user->save();

        $data = User::select('*',\DB::raw('0 as challenge_key'))->where('phone', '=', $phone)
            ->get()
            ->toArray();

        if ($data) {
            //First check rule for request otp
            $checkRuleRequest = MyHelper::checkRuleForRequestOTP($data);
            if(isset($checkRuleRequest['status']) && $checkRuleRequest['status'] == 'fail'){
                return response()->json($checkRuleRequest);
            }

            if(!isset($checkRuleRequest['otp_timer']) && $checkRuleRequest == true){
                $pin = MyHelper::createRandomPIN(6, 'angka');
                $password = bcrypt($pin);

                //get setting to set expired time for otp, if setting not exist expired default is 30 minutes
                $getSettingTimeExpired = Setting::where('key', 'setting_expired_otp')->first();
                if($getSettingTimeExpired){
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+".$getSettingTimeExpired['value']." minutes"));
                }else{
                    $dateOtpTimeExpired = date("Y-m-d H:i:s", strtotime("+30 minutes"));
                }

                $update = User::where('id', '=', $data[0]['id'])->update(['otp_forgot' => $password, 'otp_valid_time' => $dateOtpTimeExpired]);

                if (!empty($request->header('user-agent-view'))) {
                    $useragent = $request->header('user-agent-view');
                } else {
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                if (stristr($useragent, 'iOS')) $useragent = 'iOS';
                if (stristr($useragent, 'okhttp')) $useragent = 'Android';
                if (stristr($useragent, 'GuzzleHttp')) $useragent = 'Browser';

                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Pin Forgot',
                    $phone,
                    [
                        'pin' => $pin,
                        'useragent' => $useragent,
                        'now' => date('Y-m-d H:i:s'),
                        'date_sent' => date('d-m-y H:i:s'),
                        'expired_time' => (string) MyHelper::setting('setting_expired_otp','value', 30),
                    ],
                    $useragent,
                    false, false, null, null, true, $request->request_type
                );
            }elseif(isset($checkRuleRequest['otp_timer']) && $checkRuleRequest['otp_timer'] !== false){
                $holdTime = $checkRuleRequest['otp_timer'];
            }

            switch (strtoupper($request->request_type)) {
                case 'MISSCALL':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_miscall', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Missed Call.'));
                    break;

                case 'WHATSAPP':
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_wa', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui Whatsapp.'));
                    break;

                default:
                    $msg_otp = str_replace('%phone%', $phoneOld, MyHelper::setting('message_sent_otp_sms', 'value_text', 'Kami telah mengirimkan PIN ke nomor %phone% melalui SMS.'));
                    break;
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            if (env('APP_ENV') == 'production') {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $phone,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => true
                    ]
                ];
            } else {
                $result = [
                    'status'    => 'success',
                    'result'    => [
                        'otp_timer' => $holdTime,
                        'phone'    =>    $phone,
                        'message'  =>    $msg_otp,
                        'challenge_key' => $user->challenge_key,
                        'forget' => true
                    ]
                ];
            }
            return response()->json($result);
        } else {
            $result = [
                'status'    => 'fail',
                'messages'  => ['Email yang kamu masukkan kurang tepat']
            ];
            return response()->json($result);
        }
    }

    public function claimPoint(Request $request){
        $id = $request->user()->id;
        $user = User::where('id', $id)->first();

        if(empty($user)){
            return response()->json([[
                'status'    => 'fail',
                'messages'  => ['User tidak ditemukan']
            ]]);
        }

        $checkOldMember = OldMember::where('phone', $user['phone'])->where('claim_status', 0)->get()->toArray();
        $sumPoint = array_sum(array_column($checkOldMember, 'loyalty_point'));
        if(empty($sumPoint)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Point sudah diklaim']
            ]);
        }

        $balanceController = new BalanceController();
        $addLogBalance = $balanceController->addLogBalance($id, (int)$sumPoint, null, "Claim Point", 0);
        if (!$addLogBalance) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Tidak berhasil klaim point']
            ]);
        }

        OldMember::where('phone', $user['phone'])->update(['claim_status' => 1]);
        User::where('id', $id)->update(['claim_point_status' => 1]);
        return response()->json([
            'status' => 'success',
            'result' => [
                'message' => 'Berhasil klaim point sebesar '. number_format((int)$sumPoint)
            ]
        ]);
    }

    function phoneCheckEmployee(Request $request)
    {
        $phone = $request->json('phone');

        $phoneOld = $phone;
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }

        $data = User::select('*',\DB::raw('0 as challenge_key'))->where('phone', '=', $phone)->get()->toArray();

        if($data){
            $result['challenge_key'] = $data[0]['challenge_key'];
            return response()->json([
                'status' => 'success',
                'result' => $result
            ]);
        }else{
            return response()->json([
                'status' => 'fail',
                'messages' => ['Akun tidak ditemukan']]);
        }
    }

    public function changePinEmployee(Request $request){

        $request->validate([
            'phone'			=> 'required|string|max:18',
            'old_password'		=> 'required|string|digits:6',
            'new_password'		=> 'required|string|digits:6',
            'confirm_new_password'	=> 'required|string|digits:6',
            'device_id'		=> 'max:200',
            'device_token'	=> 'max:225'
        ]);
        
        $phone = $request->json('phone');
        $employee = $request->user();
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }
        
        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();
        if ($data && $data[0]['phone'] == $employee['phone']) {
            $this_employee = User::find($employee->id)->makeVisible('password');
            if(!empty($data[0]['otp_forgot']) && !empty($data[0]['phone_verified']) && !password_verify($request->json('old_password'), $data[0]['otp_forgot'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Pin lama tidak sesuai']
                ]);
            }elseif(empty($data[0]['otp_forgot']) && !empty($data[0]['pin_changed']) && !empty($data[0]['phone_verified']) && !password_verify($request->json('old_password'), $this_employee['password'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Pin lama tidak sesuai']
                ]);
            }
            
            if($request->json('new_password') != $request->json('confirm_new_password')){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Pin baru tidak sama']
                ]);
            }

            $pin     = bcrypt($request->json('new_password'));
            $update = User::where('id', '=', $data[0]['id'])->update(['password' => $pin, 'otp_forgot' => null, 'phone_verified' => '1', 'pin_changed' => '1']);
            if (\Module::collections()->has('Autocrm')) {
                if ($data[0]['first_pin_change'] < 1) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone);
                    $changepincount = $data[0]['first_pin_change'] + 1;
                    $update = User::where('id', '=', $data[0]['id'])->update(['first_pin_change' => $changepincount]);
                } else {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed Forgot Password', $phone);

                    $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->where('oauth_access_tokens.user_id', $data[0]['id'])->where('oauth_access_token_providers.provider', 'users')->delete();
                }
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            $result = [
                'status'    => 'success',
                'result'    => [
                    'phone'    =>    $data[0]['phone'],
                    'challenge_key' => $user->challenge_key
                ]
            ];
        } else {
            $result = [
                'status'    => 'fail',
                'messages'    => ['Nomor HP tidak sesuai']
            ];
        }
        return response()->json($result);
    }

    public function changePinEmployeeForgot(Request $request){

        $request->validate([
            'phone'			=> 'required|string|max:18',
            'old_password'		=> 'required|string|digits:6',
            'new_password'		=> 'required|string|digits:6',
            'confirm_new_password'		=> 'required|string|digits:6',
            'device_id'		=> 'max:200',
            'device_token'	=> 'max:225'
        ]);
        
        $phone = $request->json('phone');
        $phone = preg_replace("/[^0-9]/", "", $phone);

        $checkPhoneFormat = MyHelper::phoneCheckFormat($phone);

        if (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'fail') {
            return response()->json([
                'status' => 'fail',
                'messages' => $checkPhoneFormat['messages']
            ]);
        } elseif (isset($checkPhoneFormat['status']) && $checkPhoneFormat['status'] == 'success') {
            $phone = $checkPhoneFormat['phone'];
        }
        
        $data = User::where('phone', '=', $phone)
            ->get()
            ->toArray();
        if ($data && $data[0]['phone']) {
            $this_employee = User::find($data[0]['id'])->makeVisible('password');
            if(!empty($data[0]['otp_forgot']) && !empty($data[0]['phone_verified']) && !password_verify($request->json('old_password'), $data[0]['otp_forgot'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Password lama tidak sesuai']
                ]);
            }elseif(empty($data[0]['otp_forgot']) && !empty($data[0]['pin_changed']) && !empty($data[0]['phone_verified']) && !password_verify($request->json('old_password'), $this_employee['password'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Password lama tidak sesuai']
                ]);
            }
            
            if($request->json('new_password') != $request->json('confirm_new_password')){
                return response()->json([
                    'status'    => 'fail',
                    'messages'    => ['Password baru tidak sama']
                ]);
            }

            $pin     = bcrypt($request->json('new_password'));
            $update = User::where('id', '=', $data[0]['id'])->update(['password' => $pin, 'otp_forgot' => null, 'phone_verified' => '1', 'pin_changed' => '1']);
            if (\Module::collections()->has('Autocrm')) {
                if ($data[0]['first_pin_change'] < 1) {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed', $phone);
                    $changepincount = $data[0]['first_pin_change'] + 1;
                    $update = User::where('id', '=', $data[0]['id'])->update(['first_pin_change' => $changepincount]);
                } else {
                    $autocrm = app($this->autocrm)->SendAutoCRM('Pin Changed Forgot Password', $phone);

                    $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                        ->where('oauth_access_tokens.user_id', $data[0]['id'])->where('oauth_access_token_providers.provider', 'users')->delete();
                }
            }

            $user = User::select('password',\DB::raw('0 as challenge_key'))->where('phone', $phone)->first();

            $result = [
                'status'    => 'success',
                'result'    => [
                    'phone'    =>    $data[0]['phone'],
                    'messages'    => ['Password berhasil diubah'],
                    'challenge_key' => $user->challenge_key
                ]
            ];
        } else {
            $result = [
                'status'    => 'fail',
                'messages'    => ['Nomor HP tidak sesuai']
            ];
        }
        return response()->json($result);
    }
}
