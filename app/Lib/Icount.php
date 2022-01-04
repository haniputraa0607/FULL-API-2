<?php 
namespace App\Lib;

use DB;

use App\Http\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Models\LogApiIcount;
use App\Http\Models\Setting;
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
            if($method=='get'){
                foreach($response['response']['Data'] as $key => $data){
                    if(isset($data['ItemImage']) && !empty($data['ItemImage'])){
                        $response['response']['Data'][$key]['ItemImage'] = "";
                    }
                }
            }
            $log_response = $response;
            
            $log_api_array = [
                'type'              => $logType,
                'id_reference'      => $orderId,
                'request_url'       => self::getBaseUrl() . $url,
                'request_method'    => strtoupper($method),
                'request_parameter' => json_encode($request),
                'response_body'     => json_encode($log_response),
                'response_header'   => json_encode($fullResponse->getHeaders()),
                'response_code'     => $fullResponse->getStatusCode()
            ];
            LogApiIcount::create($log_api_array);
        } catch (\Exception $e) {                    
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiIcount: ' . $e->getMessage());
        }        

        return $response;
    }

    public static function ApiConfirmationLetter($request, $logType = null, $orderId = null){
        $data = [
            "BranchName" => $request['location']['name'],
            "BranchCode" => $request['location']['code'],
            "Title" => $request['partner']['title'],
            "BusinessPartnerName" => $request['partner']['name'],
            "BusinessPartnerCode" => $request['partner']['code'],
            "GroupBusinessPartner" => $request['partner']['group'],
            "Email" => $request['partner']['email'],
            "PhoneNo" => $request['partner']['phone'],
            "MobileNo" => $request['partner']['mobile'],
            "NPWP" => $request['partner']['npwp'],
            "NPWPName" => $request['partner']['npwp_name'],
            "NPWPAddress" => $request['partner']['npwp_address'],
            "Address" => $request['partner']['address'],
            "IsSuspended" => $request['partner']['is_suspended'] == 1 ? 'true' : 'false',
            "IsTax" => $request['partner']['is_tax'] == 1 ? 'true' : 'false',
            "PriceLevel" => $request['partner']['price_level'],
            "Notes" => $request['partner']['notes'],
            "JoinDate" => date("Y-m-d 00:00:00",strtotime($request['partner']['start_date'])),
            "VoucherNo" => "[AUTO]",
            "DepartmentID" => "011",
            "TermOfPaymentID" => $request['partner']['id_term_payment'],
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "ReferenceNo" => $request['confir']['no_letter'],
            "Detail" => [
                [
                    "Qty" => "100",
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['total_payment']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];
        if(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
            $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
        }
        return self::sendRequest('POST', '/partner_initiation/init_branch_code_order', $data, $logType, $orderId);
    }
    public static function ApiInvoiceConfirmationLetter($request, $logType = null, $orderId = null){
        $data = [
            "SalesOrderID" => $request['partner']['id_sales_order'],
            "VoucherNo" => $request['partner']['voucher_no'],
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "TermOfPaymentID" => $request['partner']['id_term_payment'],
            "ReferenceNo" => $request['confir']['no_letter'],
            "TaxNo" => '',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Qty" => "20",
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['total_payment']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];
        return self::sendRequest('POST', '/partner_initiation/do_invoice_cl', $data, $logType, $orderId);
    }
    public static function ApiInvoiceSPK($request, $logType = null, $orderId = null){
        $data = [
            "SalesOrderID" => $request['partner']['id_sales_order'],
            "VoucherNo" => $request['partner']['voucher_no'],
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "TermOfPaymentID" => $request['partner']['id_term_payment'],
            "ReferenceNo" => $request['confir']['no_letter'],
            "TaxNo" => '',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Qty" => 30,
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['total_payment']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];
        return self::sendRequest('POST', '/partner_initiation/do_invoice_spk', $data, $logType, $orderId);
    }
    public static function ApiInvoiceBAP($request, $logType = null, $orderId = null){
        $data = [
            "SalesOrderID" => $request['partner']['id_sales_order'],
            "VoucherNo" => $request['partner']['voucher_no'],
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "TermOfPaymentID" => $request['partner']['id_term_payment'],
            "ReferenceNo" => $request['confir']['no_letter'],
            "TaxNo" => '',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Qty" => 50,
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['total_payment']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];
        return self::sendRequest('POST', '/partner_initiation/do_invoice_bap', $data, $logType, $orderId);
    }
    public static function ApiPurchaseSPK($request, $logType = null, $orderId = null){
        $data = [
            "VoucherNo" => $request['partner']['voucher_no'],
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "ReferenceNo" => $request['confir']['no_letter'],
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "ItemID" => "015",
                    "BudgetCode" => "Invoice",
                    "Qty" => 10,
                    "Unit" =>"PCS",
                    "Ratio" => 1,
                    "UnitRatio" => 2,
                    "Description" => ""
                ],
                [
                    "ItemID" => "016",
                    "BudgetCode" => "Beban",
                    "Qty" => 3,
                    "Unit" =>"PCS",
                    "Ratio" => 1,
                    "UnitRatio" => 3,
                    "Description" => ""
                ],
            ]
        ];
        return self::sendRequest('POST', '/partner_initiation/purchase_request_spk', $data, $logType, $orderId);
    }

    public static function ApiCreateOrderPOO($request, $logType = null, $orderId = null){
        if(isset($request['transaction']) && !empty($request['transaction'])){
            $penjulana_outlet = Setting::where('key','penjualan_outlet')->first();
            $availablePayment = config('payment_method');
            $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
            foreach($setting as $s => $set){
                $availablePayment[$set['code']]['chart_of_account_id'] = $set['id_chart_of_account'] ?? false;
            }
            // return $availablePayment;
            $data = [
                "BranchID" => $request['id_branch'],
                "BusinessPartnerID" => $request['id_branch'],
                "VoucherNo" => "[AUTO]",
                "TermOfPaymentID" => '11',
                "TransDate" => $request['trans_date'],
                "DueDate" => $request['due_date'],
                "SalesmanID" => '',
                "ReferenceNo" => '',
                "Tax" => $request['ppn'],
                "TaxNo" => '',
                "AddressInvoice" => '',
                "Notes" => '',
            ];
            if(isset($request['id_transaction_payment'])){
                $request['payment_type'] = ucfirst(strtolower($request['payment_type']));
                foreach($availablePayment as $a => $payment){
                    if($payment['payment_method'] == $request['payment_type'] && $payment['payment_gateway'] == 'Midtrans'){
                        $data['ChartOfAccountID'] = $payment['chart_of_account_id'];
                    }
                }
            }else{
                $request['type'] = ucfirst(strtolower($request['type']));
                foreach($availablePayment as $a => $paymentx){
                    if($paymentx['payment_method'] == $request['type'] && $paymentx['payment_gateway'] == 'Xendit'){
                        $data['ChartOfAccountID'] = $paymentx['chart_of_account_id'];
                    }
                }
            }
            
            foreach($request['transaction'] as $key => $transaction){
                $data['Detail'][$key] = [
                    "ItemID" => $penjulana_outlet['value'],
                    "Name" => $transaction['product_name'],
                    "Qty" => $transaction['transaction_product_qty'],
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $transaction['transaction_product_price'],
                    "Disc" => ($transaction['discRp']*100)/($transaction['discRp']+$transaction['transaction_grandtotal']),
                    "DiscRp" => $transaction['discRp'],
                    "Description" => ""
                ];
                
            }
            return self::sendRequest('POST', '/sales/create_order_poo', $data, $logType, $orderId);
        }else{
            $data = [];
            return $data;
        }
    }

    public static function RevenueSharing($request, $logType = null, $orderId = null){
        $management_fee = Setting::where('key','revenue_sharing')->first();
        $data = [
            "VoucherNo" => "[AUTO]",
            "TransDate" => $request['tanggal_akhir'],
            "DueDate" => $request['tanggal_akhir'],
            "TermOfPaymentID" => $request['partner']['id_term_payment'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "ReferenceNo" => '',
            'Tax'=>$request['tax'],
            'TaxNo'=>'',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Name" => "Revenue Sharing",
                    "ItemID" =>$management_fee['value'],
                    "Qty" => 1,
                    "Unit" =>"PCS",
                    "Ratio" => 1,
                    'Price'=>$request['transfer'],
                    'Disc'=>$request['disc'],
                    'DiscRp'=>($request['disc']*100)/($request['disc']+$request['transfer']),
                    "Description" => ""
                ],
            ]
        ];
        return self::sendRequest('POST', '/sales/sharing_management_fee', $data, $logType, $orderId);
    }
    public static function ManagementFee($request, $logType = null, $orderId = null){
        $management_fee = Setting::where('key','management_fee')->first();
        $data = [
            "VoucherNo" => "[AUTO]",
            "TransDate" => $request['tanggal_akhir'],
            "DueDate" => $request['tanggal_akhir'],
            "TermOfPaymentID" => $request['partner']['id_term_payment'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "ReferenceNo" => '',
            'Tax'=>$request['tax'],
            'TaxNo'=>'',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Name" => "Management Fee",
                    "ItemID" =>$management_fee['value'],
                    "Qty" => 1,
                    "Unit" =>"PCS",
                    "Ratio" => 1,
                    'Price'=>$request['transfer'],
                    'Disc'=>$request['disc'],
                    'DiscRp'=>($request['disc']*100)/($request['disc']+$request['transfer']),
                    "Description" => ""
                ],
            ]
        ];
        return self::sendRequest('POST', '/sales/sharing_management_fee', $data, $logType, $orderId);
    }
    public static function get($request, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/branch/list', $request, $logType, $orderId);
    }
    public static function ItemList($request = null, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/item/list?Limit=20', $request, $logType, $orderId);
    }
    public static function ChartOfAccount($request = null, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/chart_of_account/list', $request, $logType, $orderId);
    }
}