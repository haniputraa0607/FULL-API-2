<?php
namespace App\Lib;

use App\Http\Models\LogActivitiesPos;
use App\Lib\MyHelper;
use Image;
use File;
use DB;
use App\Http\Models\Notification;
use App\Http\Models\Store;
use App\Http\Models\ProductVariant;
use App\Http\Models\SubscriptionUser;

use App\Http\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Exception\ServerErrorResponseException;
use App\Http\Models\Setting;


use FCM;

class Ticketing {

    protected $data;
    public function setData($data) {
        $this->data = $data;
    }

    public function getToken(){
        $url = env('TICKETING_BASE_URL').'api/tokens/get_token';
        $client = new Client();

        $req = [
            'form_params' => [
                'api_key' => env('TICKETING_API_KEY'),
                'api_secret' => env('TICKETING_API_SECRET'),
            ]
        ];

        try {
            $output = $client->request('POST', $url, $req);
            $output = json_decode($output->getBody(), true);
            if(isset($output['token']) && !empty($output['token'])){
                Setting::updateOrCreate(['key' => 'ticketing_token'], ['value_text' => json_encode($output)]);
            }
            return ['status' => 'success', 'response' => $output];
        }catch (\GuzzleHttp\Exception\RequestException $e) {
            try{
                if($e->getResponse()){
                    $response = $e->getResponse()->getBody()->getContents();
                    return ['status' => 'fail', 'response' => json_decode($response, true)];
                }
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
            catch(Exception $e){
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
        }
    }

    public function sendToTicketing() {
        $getOldToken = (array)json_decode(Setting::where('key', 'ticketing_token')->first()['value_text']??null);
        $token = $getOldToken['token']??null;

        if(empty($getOldToken) || date('Y-m-d H:i:s') > $getOldToken['expire_in']){
            $token = $this->getToken()['response']['token']??null;
        }

        if(empty($token)){
            return ['status' => 'fail', 'response' => ['Failed Get Bearer']];
        }

        $baseUrl = env('TICKETING_BASE_URL').'/';
        $urlApi = $baseUrl.$this->data['url'];
        $client = new Client();

        $req = [
            'headers' => [
                'Authorization' => 'Bearer '.$token
            ],
            'form_params' => $this->data['body']
        ];

return         $output = $client->request('POST', $urlApi, $req);
        try {
            $output = json_decode($output->getBody(), true);
            return ['status' => 'success', 'response' => $output['result']??[]];
        }catch (\GuzzleHttp\Exception\RequestException $e) {
            try{
                if($e->getResponse()){
                    $response = $e->getResponse()->getBody()->getContents();
                    return ['status' => 'fail', 'response' => json_decode($response, true)];
                }
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
            catch(Exception $e){
                return ['status' => 'fail', 'response' => ['Check your internet connection.']];
            }
        }
    }
}
?>