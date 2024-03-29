<?php
namespace App\Lib;

use Image;
use File;
use DB;
use App\Http\Models\Notification;
use App\Http\Models\Store;
use App\Http\Models\User;
use App\Http\Models\UserDevice;
use App\Http\Models\Transaction;
use App\Http\Models\ProductVariant;
use App\Http\Models\Outlet;

use App\Http\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\ServerErrorResponseException;

use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
// use LaravelFCM\Message\PayloadNotificationBuilder;
use App\Lib\CustomPayloadNotificationBuilder;
use Modules\Recruitment\Entities\UserHairStylist;
use FCM;

class PushNotificationHelper{
    public $saveImage = "img/push";
    public $endPoint;
    public $autocrm;

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');        
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->endPoint  = config('url.storage_url_api');
    }

    public static function saveQueue($id_user, $subject, $message, $inbox=null, $data) {
        $save = [
            'id_user'    => $id_user,
            'subject'    => $subject,
            'message'    => $message,
            'data'       => serialize($data),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        return $save;
    }

    public static function processImage($image) {
        $upload = MyHelper::uploadPhoto($image, $this->saveImage, 500);

        if (isset($upload['status']) && $upload['status'] == "success") {
            $result = $this->endPoint.$upload['path'];
        }
        else {
            $result = "";
        }
        
        return $result;
    }

    // based on field Users Table
    public static function searchDeviceToken($type, $value, $recipient_type = null) {
        $result = [];

        if ($recipient_type && $recipient_type == 'hairstylist') {
            return static::searchHairstylistDeviceToken($type, $value, $recipient_type);
        }elseif ($recipient_type && $recipient_type == 'employee') {
            return static::searchEmployeetDeviceToken($type, $value);
        }
        elseif ($recipient_type && $recipient_type == 'pos_outlet') {
            return static::searcOutletPOSDeviceToken($type, $value);
        }
        $devUser = User::leftjoin('user_devices', 'user_devices.id_user', '=', 'users.id')
            ->select('id_device_user', 'users.id', 'user_devices.device_token', 'user_devices.device_id', 'phone');

        if (is_array($type) && is_array($value)) {
            for ($i=0; $i < count($type) ; $i++) { 
                $devUser->where($type[$i], $value[$i]);
            }
        }
        else {
            if (is_array($value)) {
                $devUser->whereIn('users.'.$type, $value);
            }
            else {
                $devUser->where('users.'.$type, $value);
            }
        }

        $devUser = $devUser->get()->toArray();
        if (!empty($devUser)) {
            // if phone
            if ($type == "phone") {
                if (is_array($value)) {
                    $phone = implode(",", $value);
                }
                else {
                    $phone = $value;
                }

                $result['phone'] = $phone;
            }

            $token             = array_values(array_filter(array_unique(array_pluck($devUser, 'device_token'))));
            $id_user           = array_values(array_filter(array_unique(array_pluck($devUser, 'id_user'))));
            $result['token']   = $token;
            $result['id_user'] = $id_user;
            $result['mphone']  = array_values(array_filter(array_unique(array_pluck($devUser, 'phone'))));
        }

        return $result;
    }

    public static function searchHairstylistDeviceToken($type, $value)
    {
        $hs = UserHairStylist::with('devices');

        if (is_array($type) && is_array($value)) {
            for ($i=0; $i < count($type) ; $i++) { 
                $hs->where($type[$i], $value[$i]);
            }
        }
        else {
            $type = str_replace('phone', 'phone_number', $type);
            if (is_array($value)) {
                $hs->whereIn($type, $value);
            }
            else {
                $hs->where($type, $value);
            }
        }

        $hs = $hs->first();

        return [
            'token' => $hs->devices->pluck('device_token')->unique()->toArray(),
            'id_user' => [$hs->id_user_hair_stylist],
            'mphone' => [$hs->phone]
        ];
    }

    public static function searcOutletPOSDeviceToken($type, $value)
    {
        $outlet = Outlet::with('devices');

        if (is_array($type) && is_array($value)) {
            for ($i=0; $i < count($type) ; $i++) { 
                $outlet->where($type[$i], $value[$i]);
            }
        }
        else {
            if (is_array($value)) {
                $outlet->whereIn($type, $value);
            }
            else {
                $outlet->where($type, $value);
            }
        }

        $outlet = $outlet->first();

        return [
            'token' => $outlet->devices->pluck('device_token')->unique()->toArray(),
            'id_user' => [$outlet->id_outlet],
            'mphone' => [$outlet->outlet_code]
        ];
    }

    public static function searchEmployeetDeviceToken($type, $value)
    {
        $empolyee = User::with('employee_devices');

        if (is_array($type) && is_array($value)) {
            for ($i=0; $i < count($type) ; $i++) { 
                $empolyee->where($type[$i], $value[$i]);
            }
        }
        else {
            if (is_array($value)) {
                $empolyee->whereIn($type, $value);
            }
            else {
                $empolyee->where($type, $value);
            }
        }

        $empolyee = $empolyee->first();

        return [
            'token' => $empolyee->devices->pluck('device_token')->unique()->toArray(),
            'id_user' => [$empolyee->id],
            'mphone' => [$empolyee->phone]
        ];
    }

    public static function getDeviceTokenAll() {
        $device = UserDevice::get()->toArray();

        if (!empty($device)) {
            $device = array_values(array_filter(array_unique(array_pluck($device, 'device_token'))));
        }

        return $device;
    }

    public static function sendPush ($tokens, $subject, $messages, $image=null, $dataOptional=[], $return_error = 0) {

        $optionBuiler = new OptionsBuilder();
        $optionBuiler->setTimeToLive(60*200);
        $optionBuiler->setContentAvailable(true);
        $optionBuiler->setPriority("high");

        // $notificationBuilder = new PayloadNotificationBuilder("");
        $notificationBuilder = new CustomPayloadNotificationBuilder($subject);
        $notificationBuilder->setBody($messages)
                            ->setSound('notif.mp3')
                            ->setClickAction('home');
        if($image){
            $notificationBuilder->setImage($image);
        }
        
        $dataBuilder = new PayloadDataBuilder();

        $dataOptional['title']             = $subject;
        $dataOptional['body']              = $messages;
        $dataOptional['push_notif_local']  = $dataOptional['push_notif_local'] ?? 0;
        $dataBuilder->addData($dataOptional);

        // build semua
        $option       = $optionBuiler->build();
        $notification = $notificationBuilder->build();
        $data         = $dataBuilder->build(); 

        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);
        $success = $downstreamResponse->numberSuccess();
        $fail    = $downstreamResponse->numberFailure();

        if ($fail != 0) {
            $error = $downstreamResponse->tokensWithError();
        }

        $downstreamResponse->tokensToDelete(); 
        $downstreamResponse->tokensToModify(); 
        $downstreamResponse->tokensToRetry();

        $result = [
            'success' => $success,
            'fail'    => $fail
        ];        


        if($return_error ==  1){
            $result['error_token'] = $downstreamResponse->tokensToDelete();
        }
        return $result;
    }

    public static function sendPushOutlet ($tokens, $subject, $messages, $image=null, $dataOptional=[]) {

        $optionBuiler = new OptionsBuilder();
        $optionBuiler->setTimeToLive(60*200);
        $optionBuiler->setContentAvailable(true);
        $optionBuiler->setPriority("high");

        // $notificationBuilder = new PayloadNotificationBuilder("");
        $notificationBuilder = new PayloadNotificationBuilder($subject);
        $notificationBuilder->setBody($messages)
                            ->setSound('default')
                            ->setClickAction($dataOptional['type']);
        
        $dataBuilder = new PayloadDataBuilder();

        $dataOptional['title']             = $subject;
        $dataOptional['body']              = $messages;

        $dataBuilder->addData($dataOptional);

        // build semua
        $option       = $optionBuiler->build();
        $notification = $notificationBuilder->build();
        $data         = $dataBuilder->build(); 
        return $data;
        $downstreamResponse = FCM::sendTo($tokens, $option, $notification, $data);

        $success = $downstreamResponse->numberSuccess();
        $fail    = $downstreamResponse->numberFailure();

        if ($fail != 0) {
            $error = $downstreamResponse->tokensWithError();
        }

        $downstreamResponse->tokensToDelete(); 
        $downstreamResponse->tokensToModify(); 
        $downstreamResponse->tokensToRetry();

        $result = [
            'success' => $success,
            'fail'    => $fail
        ];        

        return $result;
    }
}
?>
