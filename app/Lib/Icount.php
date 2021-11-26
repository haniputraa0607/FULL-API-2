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
            "TransDate" => $request['partner']['trans_date'],
            "DueDate" => $request['partner']['due_date'],
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
        return self::sendRequest('POST', '/partner_initiation/init_branch_code_order', $data, $logType, $orderId);
    }
    public static function ApiInvoiceConfirmationLetter($request, $logType = null, $orderId = null){
        $data = [
            "SalesOrderID" => $request['partner']['id_sales_order'],
            "VoucherNo" => $request['partner']['voucher_no'],
            "TransDate" => $request['partner']['trans_date'],
            "DueDate" => $request['partner']['due_date'],
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
            "TransDate" => $request['partner']['trans_date'],
            "DueDate" => $request['partner']['due_date'],
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
            "TransDate" => $request['partner']['trans_date'],
            "DueDate" => $request['partner']['due_date'],
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
            "TransDate" => $request['partner']['trans_date'],
            "DueDate" => $request['partner']['due_date'],
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
    public static function get($request, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/branch/list', $request, $logType, $orderId);
    }
}