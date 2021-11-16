<?php 
namespace App\Lib;

use DB;

use App\Http\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Models\LogApiIcount;

use App\Lib\MyHelper;

class Icount
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    private static function getBaseUrl()
	{
		$baseUrl = env('ICOUNT_URL', null);
        return $baseUrl;
	}

    public static function sendRequest($method = 'GET', $url = null, $request = null, $logType = null, $orderId = null){
        $method = strtolower($method);
        $header = [
            "Content-Type" => "application/x-www-form-urlencoded"
        ];
        if ($method == 'get') {
            $response = MyHelper::getWithTimeout(self::getBaseUrl() . $url, null, $request, $header, 65, $fullResponse);
        }else{
            
            $response = MyHelper::postWithTimeout(self::getBaseUrl() . $url, null, $request, 1, $header, 65, $fullResponse);
        }   

        try {
            LogApiIcount::create([
                'type'              => $logType,
                'id_reference'      => $orderId,
                'request_url'       => self::getBaseUrl() . $url,
                'request_method'    => strtoupper($method),
                'request_parameter' => json_encode($request),
                'response_body'     => json_encode($response),
                'response_header'   => json_encode($fullResponse->getHeaders()),
                'response_code'     => $fullResponse->getStatusCode()
            ]);
        } catch (\Exception $e) {                          
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiIcount: ' . $e->getMessage());
        }        

        return $response;
    }

    public static function ApiConfirmationLetter($request, $logType = null, $orderId = null){
        return self::sendRequest('POST', '/partner_initiation/init_branch_code_order', $request, $logType, $orderId);
    }
    public static function ApiDeliveryOrderConfirmationLetter($request, $logType = null, $orderId = null){
        return self::sendRequest('POST', '/partner_initiation/delivery_order_cl', $request, $logType, $orderId);
    }
    public static function get($request, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/branch/list', $request, $logType, $orderId);
    }
}




?>