<?php

namespace App\Lib;

use GuzzleHttp\Client;
use Modules\Disburse\Entities\LogIRIS;

class classMaskingJson {
	protected $data;
	protected $smsserverip;
	public function setData($data) {
		$this->data = $data;
	}
	public function send() {
		$dt=json_encode($this->data);
		$curlHandle = curl_init(env('SMS_URL')."/sms/api_sms_masking_send_json.php");
		curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $dt);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dt))
		);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 5);
		curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 5);

		$hasil = curl_exec($curlHandle);
		$curl_response = $hasil;
		$curl_errno = curl_errno($curlHandle);
		$curl_error = curl_error($curlHandle);
		$http_code  = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		$curl_info=json_encode(curl_getinfo($curlHandle));
		curl_close($curlHandle);
		if ($curl_errno > 0) {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil=json_encode($senddata);
		} else {
			if ($http_code<>"200") {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil= json_encode($senddata);
			}
		}

        $phone = null;
        if(isset($this->data['datapacket'][0]['number'])){
            if(substr($this->data['datapacket'][0]['number'], 0, 2) == '62'){
                $phone = '0'.substr($this->data['datapacket'][0]['number'],2);
            }else{
                $phone = $this->data['datapacket'][0]['number'];
            }
        }
		$log=[
			'request_body'=>$this->data,
			'request_url'=>env('SMS_URL'),
			'response'=>$curl_response,
			'phone'=>$phone
		];
		MyHelper::logApiSMS($log);

		return $hasil;
	}
	public function balance() {
		$dt=json_encode($this->data);
		$curlHandle = curl_init(env('SMS_URL')."/sms/api_sms_masking_balance_json.php");
		curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $dt);
		curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($dt))
		);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 5);
		curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 5);
		$hasil = curl_exec($curlHandle);
		$curl_errno = curl_errno($curlHandle);
		$curl_error = curl_error($curlHandle);
		$http_code  = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
		curl_close($curlHandle);
		if ($curl_errno > 0) {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil=json_encode($senddata);
		} else {
			if ($http_code<>"200") {
			$senddata = array(
			'sending_respon'=>array(
				'globalstatus' => 90,
				'globalstatustext' => $curl_errno."|".$http_code)
			);
			$hasil= json_encode($senddata);
			}
		}
		return $hasil;
	}

    public function sendSMS() {

	    if(strtoupper($this->data['otp_type']) == 'MISSCALL'){
            ob_start();

            $phone = null;
            if(isset($this->data['datapacket'][0]['number'])){
                if(substr($this->data['datapacket'][0]['number'], 0, 2) == '62'){
                    $phone = '0'.substr($this->data['datapacket'][0]['number'],2);
                }else{
                    $phone = $this->data['datapacket'][0]['number'];
                }
            }

            // setting
            $urlendpoint = 'https://sms114.xyz/sms/api_misscall_otp_send_json.php'; // url endpoint api
            $apikey      = env('MISSCALL_API_KEY'); // api key
            $callbackurl = env('MISSCALL_URL_CALLBACK'); // url callback get status sms
            $number      = $phone; // destinationnumber
            $message     = $this->data['datapacket'][0]['otp']; // misscall number code otp

            // sending
            $senddata = array(
                'apikey' => $apikey,
                'callbackurl' => $callbackurl,
                'number' => $number,
                'message' => $message
            );

            // sending
            $data=json_encode($senddata);
            $curlHandle = curl_init($urlendpoint);
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data))
            );
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
            curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 30);
            $respon = curl_exec($curlHandle);
            curl_close($curlHandle);
            header('Content-Type: application/json');

            $log=[
                'request_body'=>$data,
                'request_url'=>$urlendpoint,
                'response'=>$respon,
                'phone'=>$phone
            ];
            MyHelper::logApiSMS($log);
            return $respon;
        }elseif(strtoupper($this->data['otp_type']) == 'WHATSAPP'){
            ob_start();

            $phone = null;
            if(isset($this->data['datapacket'][0]['number'])){
                if(substr($this->data['datapacket'][0]['number'], 0, 2) == '62'){
                    $phone = '0'.substr($this->data['datapacket'][0]['number'],2);
                }else{
                    $phone = $this->data['datapacket'][0]['number'];
                }
            }

            $phone = substr_replace($phone, '62', 0, 1);

            // setting
            $urlendpoint = 'http://sms114.xyz/sms/api_whatsapp_otp_send_json.php'; // url endpoint api
            $apikey      = env('WHATSAPP_API_KEY'); // api key
            $callbackurl = env('WHATSAPP_URL_CALLBACK'); // url callback get status sms
            $number      = $phone; // destinationnumber
            $message     = $this->data['datapacket'][0]['message']; // misscall number code otp

            // sending
            $senddata = array(
                'apikey' => $apikey,
                'callbackurl' => $callbackurl,
                'datapacket' => [
                    [
                        'number' => $number,
                        'message' => $message
                    ]
                ]
            );

            // sending
            $data=json_encode($senddata);
            $curlHandle = curl_init($urlendpoint);
            curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
            curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data))
            );
            curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
            curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 30);
            $respon = curl_exec($curlHandle);
            curl_close($curlHandle);
            header('Content-Type: application/json');

            $log=[
                'request_body'=>$data,
                'request_url'=>$urlendpoint,
                'response'=>$respon,
                'phone'=>$phone
            ];
            MyHelper::logApiSMS($log);
            return $respon;
        }
    }

    public function sendOTP() {
        $dt=json_encode($this->data);
        $phone = null;
        if(isset($this->data['datapacket'][0]['number'])){
            if(substr($this->data['datapacket'][0]['number'], 0, 2) == '62'){
                $phone = '0'.substr($this->data['datapacket'][0]['number'],2);
            }else{
                $phone = $this->data['datapacket'][0]['number'];
            }
        }

        $apikey      = env('SMS_KEY'); // api key
        $urlendpoint = 'https://sms114.xyz/sms/api_sms_otp_send_json.php'; // url endpoint api
        $callbackurl = ''; // url callback get status sms

        // create header json
        $senddata = array(
            'apikey' => $apikey,
            'callbackurl' => $callbackurl,
            'datapacket'=>array()
        );

        $number=$phone;
        $message=$this->data['datapacket'][0]['message'];
        array_push($senddata['datapacket'],array(
            'number' => trim($number),
            'message' => $message
        ));
        // sending
        $data=json_encode($senddata);
        $curlHandle = curl_init($urlendpoint);
        curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($data))
        );
        curl_setopt($curlHandle, CURLOPT_TIMEOUT, 30);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 30);
        $respon = curl_exec($curlHandle);
        curl_close($curlHandle);
        header('Content-Type: application/json');

        $log=[
            'request_body'=>$senddata,
            'request_url'=>$urlendpoint,
            'response'=>$respon,
            'phone'=>$phone
        ];
        MyHelper::logApiSMS($log);
        return $respon;
    }
}