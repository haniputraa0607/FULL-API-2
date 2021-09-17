<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\User;
use App\Http\Models\UserFeature;
use App\Http\Models\UserDevice;
use App\Http\Models\Level;
use App\Http\Models\LogActivitiesApps;
use App\Http\Models\UserInbox;
use App\Http\Models\Setting;
use App\Http\Models\Greeting;
use App\Http\Models\HomeBackground;
use App\Http\Models\UsersMembership;
use App\Http\Models\Transaction;
use App\Http\Models\Banner;
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\FeaturedDeal;
use Modules\Subscription\Entities\FeaturedSubscription;
use Modules\PromoCampaign\Entities\FeaturedPromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignReport;

use DB;
use App\Lib\MyHelper;

use Modules\Users\Http\Requests\Home;
use Modules\Queue\Http\Controllers\ApiQueue;

class ApiHome extends Controller
{
    public $getMyVoucher;
    public $endPoint;

    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
		$this->balance  = "Modules\Balance\Http\Controllers\BalanceController";
		$this->point  = "Modules\Deals\Http\Controllers\ApiDealsClaim";
		$this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
		$this->endPoint  = config('url.storage_url_api');
        $this->deals = "Modules\Deals\Http\Controllers\ApiDeals";
    }

	public function homeNotLoggedIn(Request $request) {

		if ($request->json('device_id') && $request->json('device_token') && $request->json('device_type')) {
           $this->updateDeviceUserGuest($request->json('device_id'), $request->json('device_token'), $request->json('device_type'));
		}
		$key = array_pluck(Setting::where('key', 'LIKE', '%default_home%')->get()->toArray(), 'key');
		$value = array_pluck(Setting::where('key', 'LIKE', '%default_home%')->get()->toArray(), 'value');
		$defaultHome = array_combine($key, $value);

		if(isset($defaultHome['default_home_image'])){
			$defaultHome['default_home_image_url'] = $this->endPoint.$defaultHome['default_home_image'];
		}

		if(isset($defaultHome['default_home_splash_screen'])){
			$defaultHome['splash_screen_url'] = $this->endPoint.$defaultHome['default_home_splash_screen']."?";
		}

        // banner
        $banners = $this->getBanner();
        $defaultHome['banners'] = $banners;

       return response()->json(MyHelper::checkGet($defaultHome));
	}

    public function getBanner()
    {
        // banner
        $banners = Banner::orderBy('position')
            ->where('banner_start', '<=', date('Y-m-d H:i:s'))
            ->where('banner_end', '>=', date('Y-m-d H:i:s'))
            ->where(function($query) {
                $query->where('time_start', "<=", date("H:i:s"))
                    ->where('time_end', ">=", date("H:i:s"))
                    ->orWhereNull('time_start')
                    ->orWhereNull('time_end');
            })->get();

        $gofood = 0;
        $setting = Setting::where('key', 'banner-gofood')->first();
        if (!empty($setting)) {
            $gofood = $setting->value;
        }

        if (empty($banners)) {
            return $banners;
        }
        $array = [];

        foreach ($banners as $key => $value) {

            $item['image_url']  = config('url.storage_url_api').$value->image;
            $item['type']       = 'none';
            $item['id_news']    = $value->id_reference;
            $item['id_reference']    = $value->id_reference;
            $item['news_title'] = "";
            $item['url']        = $value->url;

            if($item['url'] != null){
                $item['type']       = 'link';
            }

            if ($value->type == 'gofood') {
                $item['type']       = 'news';
                $item['news_title'] = $value->news->news_title;
                // if news, generate webview news detail url
                $item['url']        = config('url.api_url') .'news/webview/'. $value->id_news;
            } elseif ($value->type == 'gofood') {
                $item['type']       = 'gofood';
                $item['id_news'] = 99999999;
                $item['news_title'] = "GO-FOOD";
                $item['url']     = config('url.app_url').'outlet/webview/gofood/list';
            } elseif ($value->type == 'referral') {
                $item['type']       = 'referral';
                $item['id_news'] = 999999999;
                $item['news_title'] = "Referral";
                $item['url']     = config('url.api_url') . 'api/referral/webview';
            } elseif ($value->type == 'order') {
                $item['type']       = 'order';
                $item['id_news'] = null;
                $item['news_title'] = null;
                $item['url']     = null;
            } elseif (in_array($value->type, ['deals_list', 'subscription_list', 'my_voucher', 'edit_profile'])) {
                $item['type']         = $value->type;
                unset($item['id_news']);
                unset($item['news_title']);
                unset($item['url']);
                unset($item['id_reference']);
            } elseif (in_array($value->type, ['deals_detail', 'subscription_detail'])) {
                $item['type']         = $value->type;
                unset($item['id_news']);
                unset($item['news_title']);
                unset($item['url']);
            } elseif ($value->id_reference && isset($value->news->news_title)) {
                $item['type']       = 'news';
                $item['news_title'] = $value->news->news_title;
                // if news, generate webview news detail url
                $item['url']        = config('url.api_url') .'news/webview/'. $value->id_reference;
            } elseif ($value->id_reference) {
                $item['type']         = $value->type;
                unset($item['id_news']);
                unset($item['news_title']);
                unset($item['url']);
            }
            array_push($array, $item);
        }

        return $array;
    }

    public function refreshPointBalance(Request $request) {
		$user = $request->user();
		if($user){
			// $point      = app($this->point)->getPoint($user->id);
			// $balance      = app($this->balance)->balanceNow($user->id);
			$balance      = $user->balance;

			 /* QR CODE */
            $expired = Setting::where('key', 'qrcode_expired')->first();
            if(!$expired || ($expired && $expired->value == null)){
                $expired = '10';
            }else{
                $expired = $expired->value;
            }

            $timestamp = strtotime('+'.$expired.' minutes');

            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if(stristr($useragent,'iOS')) $useragent = 'iOS';
            if(stristr($useragent,'okhttp')) $useragent = 'Android';
            else $useragent = null;

            $qr = MyHelper::createQR($timestamp, $user->phone, $useragent);

            // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
            $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

			$result = [
					'status' => 'success',
					'result' => [
						'total_point' => (int) $balance,
						'qr_code'        => $qrCode,
                        'expired_qr'    => $expired
					]
				];
		}else {
			$result = [
                'status' => 'fail'
            ];
		}
		return response()->json($result);
	}

    public function home(Home $request) {
        try {
            $user = $request->user();

            /**
             * update device token
             */

            if ($request->json('device_id') && $request->json('device_token') && $request->json('device_type')) {
                $this->updateDeviceUser($user, $request->json('device_id'), $request->json('device_token'), $request->json('device_type'));
            }

            if($request->user()->email == null || $request->user()->name == null){
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['User email or user name is empty.', 'Please complete name and email first']
                ]);
            }

            if($request->user()->is_suspended == '1'){
                //delete token
                $del = OauthAccessToken::join('oauth_access_token_providers', 'oauth_access_tokens.id', 'oauth_access_token_providers.oauth_access_token_id')
                ->where('oauth_access_tokens.user_id', $request->user()->id)->where('oauth_access_token_providers.provider', 'users')->delete();

                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            if ($request->json('time')) {
                $time = $request->json('time');
            }
            else {
                $time = date('H:i:s');
            }

            $time = strtotime($time);

            // ambil dari DB
            $timeDB = Setting::select('key', 'value')->whereIn('key', ['greetings_morning', 'greetings_afternoon', 'greetings_evening', 'greetings_latenight'])->get()->toArray();

            if (empty($timeDB)) {
                $greetings = "Hello";
                $background = "";
            }
            else {
                $dbTime = [];

                /**
                 * replace key supaya gamapang dibaca
                 */
                foreach ($timeDB as $key => $value) {
                    $dbTime[str_replace("greetings_", "", $value['key'])] = $value['value'];
                }

                /**
                 * search greetings from DB
                 */
                if($time >= strtotime($dbTime['afternoon']) && $time < strtotime($dbTime['evening'])){
                    // salamnya dari DB
                    $greetings  = Greeting::where('when', '=', 'afternoon')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'afternoon')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['evening']) && $time <= strtotime($dbTime['latenight'])){
                    $greetings  = Greeting::where('when', '=', 'evening')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'evening')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['latenight'])){
                    $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'latenight')->get()->toArray();
                }
                elseif($time <= strtotime("04:00:00")){
                    $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'latenight')->get()->toArray();
                }
                else{
                    $greetings  = Greeting::where('when', '=', 'morning')->get()->toArray();
                    $background  = HomeBackground::where('when', '=', 'morning')->get()->toArray();
                }

                /**
                 * kesimpulannya
                 */
                if (empty($greetings)) {
                    $greetingss = "Hello";
                    $greetingss2 = "Nice to meet You";
                    $background = "";
                }
                else {
                    $greetingKey   = array_rand($greetings, 1);
					// return $greetings[$greetingKey]['greeting2'];
                    $greetingss     = app($this->autocrm)->TextReplace($greetings[$greetingKey]['greeting'], $user['phone']);
                    $greetingss2     = app($this->autocrm)->TextReplace($greetings[$greetingKey]['greeting2'], $user['phone']);
                    if (!empty($background)) {
						$backgroundKey = array_rand($background, 1);
						$background    = config('url.storage_url_api').$background[$backgroundKey]['picture'];
					}
                }
            }

            $expired = Setting::where('key', 'qrcode_expired')->first();
            if(!$expired || ($expired && $expired->value == null)){
                $expired = '10';
            }else{
                $expired = $expired->value;
            }

            $timestamp = strtotime('+'.$expired.' minutes');

            $useragent = $_SERVER['HTTP_USER_AGENT'];
            if(stristr($useragent,'iOS')) $useragent = 'iOS';
            if(stristr($useragent,'okhttp')) $useragent = 'Android';
            else $useragent = null;

            $qr = MyHelper::createQR($timestamp, $user->phone, $useragent);

            // $qrCode = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data='.$qr;
            $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
            $qrCode = html_entity_decode($qrCode);

			// $point      = app($this->point)->getPoint($user->id);
			// $balance      = app($this->balance)->balanceNow($user->id);

			$membership = UsersMembership::select('memberships.*')
										->Join('memberships','memberships.id_membership','=','users_memberships.id_membership')
										->where('id_user','=',$user->id)
										->orderBy('id_log_membership','desc')
										->first();

			if(isset($membership) && $membership != ""){
                $dataEncode = [
                    'id_user' => $user->id,
                ];

                $encode = json_encode($dataEncode);
                $base = base64_encode($encode);

                $membership['webview_detail_membership'] = config('url.api_url').'api/membership/web/view?data='.$base;
				if(isset($membership['membership_image']))
					$membership['membership_image'] = config('url.storage_url_api').$membership['membership_image'];
			} else {
				$membership = null;
			}

			$splash = Setting::where('key', '=', 'default_home_splash_screen')->first();

			if(!empty($splash)){
				$splash = $this->endPoint.$splash['value'];
			} else {
				$splash = null;
            }

            $countUnread = UserInbox::where('id_user','=',$user['id'])->where('read', '0')->count();
            $transactionPending = Transaction::where('id_user','=',$user['id'])->where('transaction_payment_status', 'Pending')->count();

            // banner
            $banners = $this->getBanner();

            // webview: user profile form
            $webview_url = "";
            $popup_text = "";
            $webview_link = config('url.app_url') . 'webview/complete-profile';

            // check user profile completeness (if there is null data)
            if ($user->id_city=="" || $user->gender=="" || $user->birthday=="") {
                // get setting user profile value
                $complete_profile_interval = 0;
                $complete_profile_count = 0;
                $setting_profile_point = Setting::where('key', 'complete_profile_interval')->first();
                $setting_profile_cashback = Setting::where('key', 'complete_profile_count')->first();
                if (isset($setting_profile_point->value)) {
                    $complete_profile_interval = $setting_profile_point->value;
                }
                if (isset($setting_profile_cashback->value)) {
                    $complete_profile_count = $setting_profile_cashback->value;
                }

                // check interval and counter
                // if $webview_url == "", app won't pop up the form
                if ($user->last_complete_profile != null) {
                    $now = date('Y-m-d H:i:s');
                    // count date difference (in minutes)
                    $date_start = strtotime($user->last_complete_profile);
                    $date_end   = strtotime($now);
                    $date_diff  = $date_end - $date_start;
                    $minutes_diff = $date_diff / 60;

                    if ($user->count_complete_profile < $complete_profile_count && $complete_profile_interval < $minutes_diff ) {
                        $webview_url = $webview_link;

                        $setting_profile_popup = Setting::where('key', 'complete_profile_popup')->first();
                        if (isset($setting_profile_popup->value)) {
                            $popup_text = $setting_profile_popup->value;
                        }else{
                            $popup_text = "Lengkapi data dan dapatkan Points";
                        }
                    }
                }
                else {  // never pop up before
                    $webview_url = $webview_link;

                    $setting_profile_popup = Setting::where('key', 'complete_profile_popup')->first();
                    if (isset($setting_profile_popup->value)) {
                        $popup_text = $setting_profile_popup->value;
                    }else{
                        $popup_text = "Lengkapi data dan dapatkan Points";
                    }
                }
            }

            $updateUserLogin = User::where('phone', $user->phone)->update(['new_login' => '0']);

            $birthday = "";
            if ($user->birthday != "") {
                $birthday = date("d F Y", strtotime($user->birthday));
            }

            $result = [
                'status' => 'success',
                'result' => [
                    // 'greetings'     => $greetingss,
                    // 'greetings2'    => $greetingss2,
                    // 'background'    => $background,
                    'banners'       => $banners,
                    'splash_screen_url' => $splash."?update=".time(),
                    'total_point' => (int) $user->balance,
                    // 'notification'  =>[
                    //     'total' => $countUnread + $transactionPending,
                    //     'count_unread_inbox' => $countUnread,
                    //     'count_transaction_pending' => $transactionPending,
                    // ],
                    'user_info'     => [
                        'name'  => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'birthday' => $birthday,
                        'gender' => $user->gender,
                        'relationship'  => $user->relationship,
                        'city'  => $user->city,
                        'membership'  => $membership,
                    ],
                    'qr_code'       => $qrCode,
                    'uid'           => $qr,
                    'webview_complete_profile_url'   => $webview_url,
                    'popup_complete_profile'   => $popup_text,
                ]
            ];

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(MyHelper::throwError($e));
        }
    }

    public function background(Request $request) {
        try {

            if ($request->json('time')) {
                $time = $request->json('time');
            }
            else {
                $time = date('H:i:s');
            }

            $time = strtotime($time);

            // ambil dari DB
            $timeDB = Setting::select('key', 'value')->whereIn('key', ['greetings_morning', 'greetings_afternoon', 'greetings_evening', 'greetings_late_night'])->get()->toArray();

            // print_r($timeDB); exit();

            if (empty($timeDB)) {
                $background = "";
            }
            else {
                $dbTime = [];

                /**
                 * replace key supaya gamapang dibaca
                 */
                foreach ($timeDB as $key => $value) {
                    $dbTime[str_replace("greetings_", "", $value['key'])] = $value['value'];
                }

                /**
                 * search greetings from DB
                 */
                if($time >= strtotime($dbTime['afternoon']) && $time < strtotime($dbTime['evening'])){
                    // salamnya dari DB
                    $background  = HomeBackground::where('when', '=', 'afternoon')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['evening']) && $time <= strtotime($dbTime['late_night'])){
                    $background  = HomeBackground::where('when', '=', 'evening')->get()->toArray();
                }
                elseif($time >= strtotime($dbTime['late_night'])){
                    $background  = HomeBackground::where('when', '=', 'late_night')->get()->toArray();
                }
                elseif($time <= strtotime("04:00:00")){
                    $background  = HomeBackground::where('when', '=', 'late_night')->get()->toArray();
                }
                else{
                    $background  = HomeBackground::where('when', '=', 'morning')->get()->toArray();
                }

                /**
                 * kesimpulannya
                 */
                if (empty($background)) {
                    $background = "";
                }
                else {
                    $backgroundKey = array_rand($background, 1);
                    $background    = config('url.storage_url_api').$background[$backgroundKey]['picture'];
                }
            }

            $result = [
                'status' => 'success',
                'result' => [
                    'background'     => $background,
                ]
            ];

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(MyHelper::throwError($e));
        }
    }

	public function updateDeviceUserGuest($device_id, $device_token, $device_type) {
        $dataUpdate = [
            'device_id'    => $device_id,
            'device_token' => $device_token,
            'device_type' => $device_type
        ];

        $checkDevice = UserDevice::where('device_id', $device_id)
								->where('device_token', $device_token)
								->where('device_type', $device_type)
								->count();
        if ($checkDevice == 0) {
            $update                = UserDevice::updateOrCreate(['device_id' => $device_id], [
                'device_token'		=> $device_token,
                'device_type'		=> $device_type
            ]);
            $result = [
                'status' => 'updated'
            ];
        }
        else {
            $result = [
                'status' => 'success'
            ];
        }

        return $result;
    }

    public function updateDeviceUser($user, $device_id, $device_token, $device_type) {
        $dataUpdate = [
            'device_id'    => $device_id,
            'device_token' => $device_token,
            'device_type' => $device_type
        ];

        $checkDevice = UserDevice::where('id_user', $user->id)
								->where('device_id', $device_id)
								->where('device_type', $device_type)
								->count();

        $update                = UserDevice::updateOrCreate(['device_id' => $device_id], [
            'id_user'           => $user->id,
            'device_token'		=> $device_token,
            'device_type'		=> $device_type
        ]);

        if ($update) {
			if($device_type == 'Android')
            $query                 = User::where('id','=',$user->id)->update(['android_device' => $device_id]);

			if($device_type == 'IOS')
            $query                 = User::where('id','=',$user->id)->update(['ios_device' => $device_id]);

            $result = [
                'status' => 'updated'
            ];
        }
        else {
            $result = [
                'status' => 'fail'
            ];
        }

        return $result;
    }

    public function membership(Request $request){
        $user = $request->user();
        if ($request->json('device_id') && $request->json('device_token') && $request->json('device_type')) {
            $this->updateDeviceUser($user, $request->json('device_id'), $request->json('device_token'), $request->json('device_type'));
        }
        if($user->first_login===0){
            $user->first_login=1;
            $user->save();
            $send = app($this->autocrm)->SendAutoCRM('Login First Time', $user['phone']);
            if (!$send) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Send notification failed']]);
            }
        }
        $user->load(['city','city.province']);

        if ($request->json('time')) {
            $time = $request->json('time');
        }
        else {
            $time = date('H:i:s');
        }

        $time = strtotime($time);

        // ambil dari DB
        $timeDB = Setting::select('key', 'value')->whereIn('key', ['greetings_morning', 'greetings_afternoon', 'greetings_evening', 'greetings_latenight'])->get()->toArray();

        if (empty($timeDB)) {
            $greetings = "Hello";
        }
        else {
            $dbTime = [];

            /**
             * replace key supaya gamapang dibaca
             */
            foreach ($timeDB as $key => $value) {
                $dbTime[str_replace("greetings_", "", $value['key'])] = $value['value'];
            }

            /**
             * search greetings from DB
             */
            if($time >= strtotime($dbTime['afternoon']) && $time < strtotime($dbTime['evening'])){
                // salamnya dari DB
                $greetings  = Greeting::where('when', '=', 'afternoon')->get()->toArray();
            }
            elseif($time >= strtotime($dbTime['evening']) && $time <= strtotime($dbTime['latenight'])){
                $greetings  = Greeting::where('when', '=', 'evening')->get()->toArray();
            }
            elseif($time >= strtotime($dbTime['latenight'])){
                $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
            }
            elseif($time <= strtotime("04:00:00")){
                $greetings  = Greeting::where('when', '=', 'latenight')->get()->toArray();
            }
            else{
                $greetings  = Greeting::where('when', '=', 'morning')->get()->toArray();
            }

            /**
             * kesimpulannya
             */
            if (empty($greetings)) {
                $greetingss = "Hello";
            }
            else {
                $greetingKey   = array_rand($greetings, 1);
                // return $greetings[$greetingKey]['greeting2'];
                $greetingss     = app($this->autocrm)->TextReplace($greetings[$greetingKey]['greeting'], $user['phone']);
            }
        }

        $expired = Setting::where('key', 'qrcode_expired')->first();
        if(!$expired || ($expired && $expired->value == null)){
            $expired = '10';
        }else{
            $expired = $expired->value;
        }

        $timestamp = strtotime('+'.$expired.' minutes');

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(stristr($useragent,'iOS')) $useragent = 'iOS';
        if(stristr($useragent,'okhttp')) $useragent = 'Android';
        else $useragent = null;

        $qr = MyHelper::createQR($timestamp, $user->phone, $useragent);

        $qrCode = 'https://chart.googleapis.com/chart?chl='.$qr.'&chs=250x250&cht=qr&chld=H%7C0';
        $qrCode = html_entity_decode($qrCode);

        $membership = UsersMembership::select('memberships.membership_name','memberships.membership_image')
                                    ->Join('memberships','memberships.id_membership','=','users_memberships.id_membership')
                                    ->where('id_user','=',$user->id)
                                    ->orderBy('id_log_membership','desc')
                                    ->first();

        if(isset($membership) && $membership != ""){
            $dataEncode = [
                'id_user' => $user->id,
            ];

            $encode = json_encode($dataEncode);
            $base = base64_encode($encode);

            $membership['webview_detail_membership'] = config('url.api_url').'api/membership/web/view?data='.$base;
            $membership['membership_image'] = config('url.storage_url_api').$membership['membership_image'];
        } else {
            $membership = null;
            $membership['membership_image'] = "";
        }

        $retUser=$user->toArray();

        if($retUser['birthday']){
            $retUser['birthday']=date("d F Y", strtotime($retUser['birthday']));
        }else{
            $retUser['birthday'] = "";
        }

        $retUser['job'] = ($retUser['job'] === NULL ? '' : $retUser['job']);
        $retUser['gender'] = ($retUser['gender'] === NULL ? '' : $retUser['gender']);
        $retUser['id_city'] = ($retUser['id_city'] === NULL ? '' : $retUser['id_city']);

        if($retUser['id_card_image']??false){
            $retUser['id_card_image'] = config('url.storage_url_api').$retUser['id_card_image'];
        }
        array_walk_recursive($retUser, function(&$it,$ix){
            if($it==null&&!in_array($ix, ['city','membership'])){
                $it="";
            }
        });
        $hidden=['password_k','created_at','updated_at','provider','phone_verified','email_unsubscribed','level','points','rank','android_device','ios_device','is_suspended','balance','complete_profile','subtotal_transaction','count_transaction','id_membership','relationship'];
        foreach ($hidden as $hide) {
            unset($retUser[$hide]);
        }

        // chek vote transaksi
        $trx = Transaction::where([
            ['id_user',$user->id],
            ['show_rate_popup',1]
        ])->orderBy('transaction_date')->first();
        $rate_popup = $trx?$trx->transaction_receipt_number.','.$trx->id_transaction:null;
        $retUser['membership']=$membership;
        $result = [
            'status' => 'success',
            'result' => [
                'total_point' => (int) $user->balance??0,
                'user_info'     => $retUser,
                'qr_code'       => $qrCode??'',
                'greeting'      => $greetingss??'',
                'expired_qr'    => $expired??'',
                'rate_popup'    => $rate_popup
            ]
        ];

        return response()->json($result);
    }

    public function splash(Request $request){
        $splash = Setting::where('key', '=', 'default_home_splash_screen')->first();
        $duration = Setting::where('key', '=', 'default_home_splash_duration')->pluck('value')->first();

        if(!empty($splash)){
            $splash = $this->endPoint.$splash['value'];
        } else {
            $splash = null;
        }
        $ext=explode('.', $splash);
        $result = [
            'status' => 'success',
            'result' => [
                'splash_screen_url' => $splash."?update=".time(),
                'splash_screen_duration' => $duration??5,
                'splash_screen_ext' => '.'.end($ext)
            ]
        ];
        return $result;
    }

    public function banner(Request $request){
        $banners = $this->getBanner();
        $result = [
            'status' => 'success',
            'result' => $banners,
        ];
        return $result;
    }

    public function featuredDeals(Request $request){
        $now=date('Y-m-d H-i-s');
        $home_text = Setting::where('key','=','home_deals_title')->orWhere('key','=','home_deals_sub_title')->orderBy('id_setting')->get();
        $text['title'] = $home_text[0]['value']??'Penawaran Spesial.';
        $text['sub_title'] = $home_text[1]['value']??'Potongan menarik untuk setiap pembelian.';

        $deals=FeaturedDeal::select('id_featured_deals','id_deals')->with(['deals'=>function($query){
            $query->select('deals_title','deals_image','deals_total_voucher','deals_total_claimed','deals_publish_end','deals_start','deals_end','id_deals','deals_voucher_price_point','deals_voucher_price_cash','deals_voucher_type');
        }])
            ->whereHas('deals',function($query){
                $query->where('deals_publish_end','>=',DB::raw('CURRENT_TIMESTAMP()'));
                $query->where('deals_publish_start','<=',DB::raw('CURRENT_TIMESTAMP()'));
                $query->whereHas('brands',function($query){
                    $query->where('brand_active',1);
                });
            })
            ->orderBy('order')
            ->where('start_date','<=',$now)
            ->where('end_date','>=',$now)
            ->get();
        if($deals){
            $deals=array_map(function($value){
                if ($value['deals']['deals_voucher_type'] == "Unlimited") {
                    $calc = '*';
                }else{
                    $calc = $value['deals']['deals_total_voucher'] - $value['deals']['deals_total_claimed'];
                }
                $value['deals']['available_voucher'] = (string) $calc;
                if($calc&&is_numeric($calc)){
                    $value['deals']['percent_voucher'] = $calc*100/$value['deals']['deals_total_voucher'];
                }else{
                    $value['deals']['percent_voucher'] = 100;
                }
                $value['deals']['show'] = 1;
                $value['deals']['time_to_end']=strtotime($value['deals']['deals_end'])-time();
                return $value;
            },$deals->toArray());
            foreach ($deals as $key => $value) {
                if ($value['deals']['available_voucher'] == "0" && $value['deals']['deals_status'] != 'soon') {
                    unset($deals[$key]);
                }
            }

            $data_home['text'] = $text;
            $data_home['featured_list'] = $deals;
            return [
                'status'=>'success',
                'result'=>$data_home
            ];
        }else{
            return [
                'status' => 'fail',
                'messages' => ['Something went wrong']
            ];
        }
    }

    public function featuredSubscription(Request $request){

        $now=date('Y-m-d H-i-s');
        $home_text = Setting::where('key','=','home_subscription_title')->orWhere('key','=','home_subscription_sub_title')->orderBy('id_setting')->get();
        $text['title'] = $home_text[0]['value']??'Subscription';
        $text['sub_title'] = $home_text[1]['value']??'Banyak untungnya kalo berlangganan';

        $subs=featuredSubscription::select('id_featured_subscription','id_subscription')->with(['subscription'=>function($query){
            $query->select('subscription_title','subscription_sub_title','subscription_image','subscription_total', 'subscription_voucher_total','subscription_bought','subscription_publish_start','subscription_publish_end','subscription_start','subscription_end','id_subscription','subscription_price_point','subscription_price_cash');
        }])
            ->whereHas('subscription',function($query){
                $query->where('subscription_publish_end','>=',DB::raw('CURRENT_TIMESTAMP()'));
                $query->where('subscription_publish_start','<=',DB::raw('CURRENT_TIMESTAMP()'));
            })
            ->orderBy('order')
            ->where('date_start','<=',$now)
            ->where('date_end','>=',$now)
            ->get();

        if($subs){
            $subs=array_map(function($value){
                if ( (empty($value['subscription']['subscription_price_point']) && empty($value['subscription']['subscription_price_cash'])) || empty($value['subscription']['subscription_total']) ) {
                    $calc = '*';
                }else{
                    $calc = $value['subscription']['subscription_total'] - $value['subscription']['subscription_bought'];
                }
                $value['subscription']['available_subscription'] = (string) $calc;
                if($calc&&is_numeric($calc)){
                    $value['subscription']['percent_subscription'] = $calc*100/$value['subscription']['subscription_total'];
                }else{
                    $value['subscription']['percent_subscription'] = 100;
                }
                $value['subscription']['time_to_end']=strtotime($value['subscription']['subscription_end'])-time();
                return $value;
            },$subs->toArray());

            $featuredList = [];
            $tempList = [];
            $i = 0;

            foreach ($subs as $key => $value) {
                if ($value['subscription']['available_subscription'] == "0" && isset($value['subscription']['total'])) {
                    unset($subs[$key]);
                }else{

                    $featuredList[$i]['id_featured_subscription'] = $value['id_featured_subscription'];
                    $featuredList[$i]['id_subscription'] = $value['id_subscription'];
                    $featuredList[$i]['subscription_title'] = $value['subscription']['subscription_title'];
                    $featuredList[$i]['subscription_sub_title'] = $value['subscription']['subscription_sub_title'];
                    $featuredList[$i]['url_subscription_image'] = $value['subscription']['url_subscription_image'];
                    $featuredList[$i]['time_to_end'] = $value['subscription']['time_to_end'];
                    $featuredList[$i]['subscription_end'] = $value['subscription']['subscription_end'];
                    $featuredList[$i]['subscription_publish_end'] = $value['subscription']['subscription_publish_end'];
                    $featuredList[$i]['time_server'] = date('Y-m-d H:i:s');
                    $i++;
                }

            }
            $data_home['text'] = $text;
            $data_home['featured_list'] = $featuredList;
            return [
                'status'=>'success',
                'result'=> $data_home
            ];
        }else{
            return [
                'status' => 'fail',
                'messages' => ['Something went wrong']
            ];
        }
    }

    public function socialMedia(Request $request)
    {
    	$getSetting = Setting::whereIn('key', ['facebook_url', 'instagram_url'])->get()->keyBy('key');
    	$result = [
    		'facebook' => $getSetting['facebook_url']['value_text'] ?? null,
    		'instagram' => $getSetting['instagram_url']['value_text'] ?? null
    	];
    	return MyHelper::checkGet($result);
    }

    public function featuredPromoCampaign(Request $request){
        $now = date('Y-m-d H-i-s');
        $home_text = Setting::whereIn('key',['home_promo_campaign_sub_title','home_promo_campaign_title'])->get()->keyBy('key');
        $text['title'] = $home_text['home_promo_campaign_title']['value'] ?? 'Penawaran Spesial.';
        $text['sub_title'] = $home_text['home_promo_campaign_sub_title']['value'] ?? 'Potongan menarik untuk setiap pembelian.';

        $featuredPromo = FeaturedPromoCampaign::select('id_featured_promo_campaign','id_promo_campaign')
        		->with(['promo_campaign' => function($query) {
            		$query->select('id_promo_campaign', 'promo_title', 'promo_image', 'total_coupon', 'date_start', 'date_end', 'is_all_outlet', 'used_code', 'limitation_usage', 'min_basket_size', 'is_all_shipment', 'is_all_payment', 'promo_description', 'user_limit', 'code_limit', 'device_limit');
        		}])
	            ->whereHas('promo_campaign',function($query){
	                $query->where('date_end','>=',DB::raw('CURRENT_TIMESTAMP()'));
	                $query->where('date_start','<=',DB::raw('CURRENT_TIMESTAMP()'));
	                $query->whereHas('brands',function($query){
	                    $query->where('brand_active',1);
	                });
	            })
	            ->orderBy('order')
	            ->where('date_start','<=',$now)
	            ->where('date_end','>=',$now)
	            ->get();

        if (!$featuredPromo) {
        	return MyHelper::checkGet($featuredPromo);
        }

        $featuredPromo = array_map(function($value) {
        	$used_code = PromoCampaignReport::where('id_promo_campaign',$value['id_promo_campaign'])->count();
            if ($value['promo_campaign']['total_coupon'] == "0") {
                $calc = '*';
            } else {
                $calc = $value['promo_campaign']['total_coupon'] - $used_code;
            }
            $value['promo_campaign']['available_promo_code'] = (string) $calc;
            if ($calc && is_numeric($calc)) {
                $value['promo_campaign']['percent_promo_code'] = $calc*100/$value['promo_campaign']['total_coupon'];
            } else {
                $value['promo_campaign']['percent_promo_code'] = 100;
            }
            $value['promo_campaign']['show'] = 1;
            $value['promo_campaign']['time_to_end'] = strtotime($value['promo_campaign']['date_end'])-time();
            return $value;
        },$featuredPromo->toArray());
        foreach ($featuredPromo as $key => $value) {
            if ($value['promo_campaign']['available_promo_code'] == "0") {
                unset($featuredPromo[$key]);
            }
        }

        $data_home['text'] = $text;
        $data_home['featured_list'] = $featuredPromo;
        return [
            'status' => 'success',
            'result' => $data_home
        ];
    }
}
