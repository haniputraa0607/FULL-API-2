<?php 
namespace App\Lib;

use DB;

use App\Http\Requests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Models\LogApiIcount;
use Modules\ChartOfAccount\Entities\ChartOfAccount;
use App\Http\Models\Setting;
use App\Lib\MyHelper;

class Icount
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    private static function getBaseUrl($company = null)
    {
        if($company == 'PT IMS'){
            $baseUrl = env('ICOUNT_URL_IMS', null);    
        }elseif($company == 'PT IMA'){
            $baseUrl = env('ICOUNT_URL_IMA', null);
        }else{
            $baseUrl = env('ICOUNT_URL', null);
        }
        return $baseUrl;
    }

    public static function sendRequest($method = 'GET', $url = null, $request = null, $company = null, $logType = null, $orderId = null){
        $method = strtolower($method);
        $header = [
            "Content-Type" => "application/x-www-form-urlencoded"
        ];
        if($url == '/sales/create_order_poo'){
            $time_out = 2;
        }else{
            $time_out = 1;
        }
        if ($method == 'get') {
            $response = MyHelper::getWithTimeout(self::getBaseUrl($company) . $url, null, $request, $header, 65, $fullResponse);
        }elseif($method == 'put'){
            $response = MyHelper::putWithTimeout(self::getBaseUrl($company) . $url, null, $request, $time_out, $header, 65, $fullResponse);
        }else{
            $response = MyHelper::postWithTimeout(self::getBaseUrl($company) . $url, null, $request, $time_out, $header, 65, $fullResponse);
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
                'type'              => $logType??'',
                'id_reference'      => $orderId??'',
                'request_url'       => self::getBaseUrl($company) . $url,
                'request_method'    => strtoupper($method),
                'request_parameter' => json_encode($request),
                'response_body'     => json_encode($log_response),
                'response_header'   => json_encode($fullResponse->getHeaders()??''),
                'response_code'     => $fullResponse->getStatusCode()??''
            ];
            LogApiIcount::create($log_api_array);
        } catch (\Exception $e) {                    
            \Illuminate\Support\Facades\Log::error('Failed write log to LogApiIcount: ' . $e->getMessage());
        }        

        return $response;
    }

    public static function ApiInitBranch($request, $company = null, $logType = null, $orderId = null){
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
            "TermOfPaymentID" => $request['location']['id_term_of_payment'],
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "ReferenceNo" => $request['confir']['no_letter'],
            "Detail" => [
                [
                    "Qty" => "100",
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['partnership_fee']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];

        if($company=='PT IMS'){
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima']) && isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner']) ){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
        }else{
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            }elseif(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
        }

        return self::sendRequest('POST', '/partner_initiation/init_branch_code_order', $data, $company, $logType, $orderId);
    }
    public static function ApiInvoiceConfirmationLetter($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "SalesOrderID" => $request['partner']['id_sales_order'],
            "VoucherNo" => '[AUTO]',
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "TermOfPaymentID" => $request['location']['id_term_of_payment'],
            "ReferenceNo" => $request['confir']['no_letter'],
            "TaxNo" => '',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Qty" => "20",
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['partnership_fee']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];
        if($company=='PT IMA'){
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            }elseif(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
            if(isset($request['location']['id_branch_ima']) && !empty($request['location']['id_branch_ima'])){
                $data['BranchID'] = $request['location']['id_branch_ima'];
            }elseif(isset($request['location']['id_branch']) && !empty($request['location']['id_branch'])){
                $data['BranchID'] = $request['location']['id_branch'];
            }
        }
        return self::sendRequest('POST', '/partner_initiation/do_invoice_cl', $data, 'PT IMA', $logType, $orderId);
    }
    public static function ApiInvoiceSPK($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "SalesOrderID" => $request['partner']['id_sales_order'],
            "VoucherNo" => '[AUTO]',
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "TermOfPaymentID" => $request['location']['id_term_of_payment'],
            "ReferenceNo" => $request['location']['no_spk'],
            "TaxNo" => '',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Qty" => 30,
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['partnership_fee']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];
        if($company=='PT IMA'){
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            }elseif(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
            if(isset($request['location']['id_branch_ima']) && !empty($request['location']['id_branch_ima'])){
                $data['BranchID'] = $request['location']['id_branch_ima'];
            }elseif(isset($request['location']['id_branch']) && !empty($request['location']['id_branch'])){
                $data['BranchID'] = $request['location']['id_branch'];
            }
        }
        return self::sendRequest('POST', '/partner_initiation/do_invoice_spk', $data, 'PT IMA', $logType, $orderId);
    }
    public static function ApiInvoiceBAP($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "SalesOrderID" => $request['partner']['id_sales_order'],
            "VoucherNo" => "[AUTO]",
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "TermOfPaymentID" => $request['location']['id_term_of_payment'],
            "ReferenceNo" => "",
            "TaxNo" => '',
            "Notes" => $request['partner']['notes'],
            "Detail" => [
                [
                    "Qty" => 50,
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $request['location']['partnership_fee']/100,
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ]
            ]
        ];
        if($company=='PT IMA'){
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            }elseif(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
            if(isset($request['location']['id_branch_ima']) && !empty($request['location']['id_branch_ima'])){
                $data['BranchID'] = $request['location']['id_branch_ima'];
            }elseif(isset($request['location']['id_branch']) && !empty($request['location']['id_branch'])){
                $data['BranchID'] = $request['location']['id_branch'];
            }
        }
        return self::sendRequest('POST', '/partner_initiation/do_invoice_bap', $data, 'PT IMA', $logType, $orderId);
    }
    public static function ApiPurchaseSPK($request, $company= null, $logType = null, $orderId = null){
        $detail = array();
        foreach ($request['location_bundling'] as $value) {
            if($value['unit'] == $value['unit1']){
                $ratio = 1;
                $unitratio = $value['qty'];
            }elseif($value['unit'] == $value['unit2']){
                if($value['ratio2']!=0){
                    $ratio = $value['ratio2'];
                    $unitratio = $value['qty']*$value['ratio2'];
                }else{
                    $ratio = 1;
                    $unitratio = $value['qty'];
                }
            }elseif($value['unit'] == $value['unit3']){
                if($value['ratio3']!=0){
                    $ratio = $value['ratio3'];
                    $unitratio = $value['qty']*$value['ratio3'];
                }else{
                    $ratio = 1;
                    $unitratio = $value['qty'];
                }
            }else{
                $ratio = 1;
                $unitratio = $value['qty'];
            }
            $data_detail = array(
                 "Name" => $value['name'],
                 "ItemID" => $value['id_item'],
                "BudgetCode" => $value['budget_code'],
                "Qty" => $value['qty'],
                "Unit" =>$value['unit'],
                "Ratio" =>$ratio,
                "UnitRatio" => $unitratio,
                "Description" => $value['description']??""
            );
            array_push($detail,$data_detail);
        }
        
        $data = [
            "VoucherNo" => "[AUTO]",
            "TransDate" => $request['location']['trans_date'],
            "DueDate" => $request['location']['due_date'],
            "BusinessPartnerID" => $request['partner']['id_business_partner'],
            "BranchID" => $request['location']['id_branch'],
            "ReferenceNo" => $request['location']['no_spk'],
            "Notes" => $request['partner']['notes'],
            "Detail" => $detail
        ];
        if($company=='PT IMA'){
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            }elseif(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
            if(isset($request['location']['id_branch_ima']) && !empty($request['location']['id_branch_ima'])){
                $data['BranchID'] = $request['location']['id_branch_ima'];
            }elseif(isset($request['location']['id_branch']) && !empty($request['location']['id_branch'])){
                $data['BranchID'] = $request['location']['id_branch'];
            }
        }
        return self::sendRequest('POST', '/partner_initiation/purchase_request_spk', $data, $company, $logType, $orderId);
    }

    public static function ApiCreateOrderPOO($request, $company = null, $logType = null, $orderId = null){
        if(isset($request['transaction']) && !empty($request['transaction'])){
            $trans_date = date('Y-m-d', strtotime($request['transaction_date']));
            $due_date = Setting::where('key','due_date')->first()['value'] ?? 30;
            $due_date = date('Y-m-d', strtotime('+'.$due_date.' days', strtotime($trans_date)));
            $penjulana_outlet = Setting::where('key','penjualan_outlet')->first();
            $availablePayment = config('payment_method');
            $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
            foreach($setting as $s => $set){
                $availablePayment[$set['code']]['chart_of_account_id'] = $set['id_chart_of_account'] ?? false;
            }
            $data = [
                "BranchID" => $request['id_branch'],
                "BusinessPartnerID" => $request['id_business_partner'],
                "VoucherNo" => "[AUTO]",
                "TermOfPaymentID" => '011',
                "TransDate" => $trans_date,
                "DueDate" => $due_date,
                "SalesmanID" => '',
                "ReferenceNo" => '',
                "Tax" => $request['ppn'],
                "TaxNo" => '',
                "AddressInvoice" => '',
                "Notes" => '',
                'MDRFee' => 0
            ];

            if(isset($request['id_transaction_payment'])){
                $request['payment_type'] = ucfirst(strtolower($request['payment_type']));
                foreach($availablePayment as $a => $payment){
                    if(strtolower($payment['payment_method']) == strtolower($request['payment_type']) && $payment['payment_gateway'] == 'Midtrans'){
                        $data['ChartOfAccountID'] = ChartOfAccount::where('id_chart_of_account',$payment['chart_of_account_id'])->first()['ChartOfAccountID'];
                    }
                }
                $data['ReferenceNo'] = strtoupper($request['payment_type']);
            }elseif(isset($request['id_transaction_payment_xendit'])){
                $request['type'] = ucfirst(strtolower($request['type']));
                foreach($availablePayment as $a => $paymentx){
                    if(strtolower($paymentx['payment_method']) == strtolower($request['type']) && $paymentx['payment_gateway'] == 'Xendit'){
                        $data['ChartOfAccountID'] = ChartOfAccount::where('id_chart_of_account',$paymentx['chart_of_account_id'])->first()['ChartOfAccountID'];
                    }
                }
                $data['ReferenceNo'] = strtoupper($request['type']);
            }elseif(isset($request['id_transaction_payment_cash'])){
                $request['type'] = ucfirst(strtolower('Cash'));
                foreach($availablePayment as $a => $paymentc){
                    if(strtolower($paymentc['payment_method']) == strtolower($request['type']) && $paymentc['payment_gateway'] == 'Cash'){
                        $data['ChartOfAccountID'] = ChartOfAccount::where('id_chart_of_account',$paymentc['chart_of_account_id'])->first()['ChartOfAccountID'];
                    }
                }
                $data['ReferenceNo'] = strtoupper($request['type']);
            }
            
            $transactions = [];
            foreach($request['transaction'] as $key => $transaction){
                if(!isset($transactions[$transaction['id_product'].$transaction['total_price']])){
                    $transactions[$transaction['id_product'].$transaction['total_price']] = $transaction;
                }else{
                    $transactions[$transaction['id_product'].$transaction['total_price']]['transaction_product_qty'] += $transaction['transaction_product_qty'];
                }
            }
            $key = 0;

            foreach($transactions as $transaction){
                $data['Detail'][$key] = [
                    "ItemID" => $transaction['id_item_icount'] ?? $penjulana_outlet['value'],
                    "Name" => $transaction['product_name'],
                    "Qty" => $transaction['transaction_product_qty'],
                    "Unit" => "PCS",
                    "Ratio" => "1",
                    "Price" => $transaction['total_price'],
                    "Disc" => "0",
                    "DiscRp" => "0",
                    "Description" => ""
                ];
                $data['MDRFee'] += $transaction['mdr_product'] * $transaction['transaction_product_qty'];
                $key++;
            }
            if($company=='PT IMA'){
                if(isset($request['id_business_partner_ima']) && !empty($request['id_business_partner_ima'])){
                    $data['BusinessPartnerID'] = $request['id_business_partner_ima'];
                }
                if(isset($request['id_branch_ima']) && !empty($request['id_branch_ima'])){
                    $data['BranchID'] = $request['id_branch_ima']; 
                }
            }

            return self::sendRequest('POST', '/sales/create_order_poo', $data, $company, $logType, $orderId);
        }else{
            $data = [];
            return $data;
        }
    }

    public static function RevenueSharing($request, $company = null, $logType = null, $orderId = null){
        $management_fee = Setting::where('key','revenue_sharing')->first();
        if($request['disc']??0 != 0){
            $disc = ($request['disc']*100)/($request['disc']+$request['transfer']);
        }else{
            $disc = 0;
        }
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
            "Notes" => "",
            "Detail" => [
                [
                    "Name" => "Revenue Sharing",
                    "ItemID" =>$management_fee['value'],
                    "Qty" => 1,
                    "Unit" =>"PCS",
                    "Ratio" => 1,
                    'Price'=>$request['transfer'],
                    'Disc'=>$request['disc'],
                    'DiscRp'=>$disc,
                    "Description" => ""
                ],
            ]
        ];
        if($company=='PT IMA'){
            $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            $data['BranchID'] = $request['location']['id_branch_ima'];
        }else{
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            }elseif(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
            if(isset($request['location']['id_branch_ima']) && !empty($request['location']['id_branch_ima'])){
                $data['BranchID'] = $request['location']['id_branch_ima'];
            }elseif(isset($request['location']['id_branch']) && !empty($request['location']['id_branch'])){
                $data['BranchID'] = $request['location']['id_branch'];
            }
        }
        return self::sendRequest('POST', '/sales/sharing_management_fee', $data, $company, $logType, $orderId);
    }
    public static function ManagementFee($request, $company = null, $logType = null, $orderId = null){
        $management_fee = Setting::where('key','management_fee')->first();
        if($request['disc']??0 != 0){
            $disc = ($request['disc']*100)/($request['disc']+$request['transfer']);
        }else{
            $disc = 0;
        }
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
            "Notes" => "",
            "Detail" => [
                [
                    "Name" => "Management Fee",
                    "ItemID" =>$management_fee['value'],
                    "Qty" => 1,
                    "Unit" =>"PCS",
                    "Ratio" => 1,
                    'Price'=>$request['transfer'],
                    'Disc'=>$request['disc'],
                    'DiscRp'=>$disc,
                    "Description" => ""
                ],
            ]
        ];
        if($company=='PT IMA'){
            $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            $data['BranchID'] = $request['location']['id_branch_ima'];
        }else{
            if(isset($request['partner']['id_business_partner_ima']) && !empty($request['partner']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner_ima'];
            }elseif(isset($request['partner']['id_business_partner']) && !empty($request['partner']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['partner']['id_business_partner'];
            }
            if(isset($request['location']['id_branch_ima']) && !empty($request['location']['id_branch_ima'])){
                $data['BranchID'] = $request['location']['id_branch_ima'];
            }elseif(isset($request['location']['id_branch']) && !empty($request['location']['id_branch'])){
                $data['BranchID'] = $request['location']['id_branch'];
            }
        }
        return self::sendRequest('POST', '/sales/sharing_management_fee', $data, $company, $logType, $orderId);
    }
    public static function SalesPayment($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "VoucherNo" => "[AUTO]",
            "TransDate" => date("Y-m-d"),
            "ReferenceNo" => null,
            "SalesInvoiceID" => $request['SalesInvoiceID'],
            "Amount" => $request['amount_return'],
            "Description" => $request['name_category_loan'],
            "Notes" => "Pembayaran peminjaman"
        ];
        if($company=='IMA'){
           $company = "PT IMA";
        }else{
           $company = "PT IMS";
        }
        return self::sendRequest('POST', '/sales/create_sp_hs', $data, $company, $logType, $orderId);
    }
    public static function get($request, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/branch/list', $request, $logType, $orderId);
    }
    public static function ItemList($page = 1, $request = null, $company = null, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/item/list?Limit=20&Page='.$page , $request, $company, $logType, $orderId);
    }
    public static function DepartmentList($page = 1, $request = null, $company = null, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/department/list?Limit=20&Page='.$page , $request, $company, $logType, $orderId);
    }
    public static function ChartOfAccount($page = 1, $request = null, $company = null, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/chart_of_account/list?Limit=20&Page='.$page , $request, $logType, $orderId);
    }
    
    public static function ApiCreateEmployee($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "Name" => $request['employee']['name'],
            "Code" => $request['employee']['code'],
            "TermOfPaymentID" => $request['employee']['id_term_payment'],
            "Email" => $request['employee']['email'],
            "ClusterID" => '013',
            "JoinDate" => $request['employee']['start_date']??date('Y-m-d'),
        ];
        if($company=='PT IMS'){
            if(isset($request['employee']['id_business_partner_ima']) && !empty($request['employee']['id_business_partner_ima']) && isset($request['employee']['id_business_partner']) && !empty($request['employee']['id_business_partner']) ){
                $data['BusinessPartnerID'] = $request['employee']['id_business_partner'];
            }
        }else{
            if(isset($request['employee']['id_business_partner_ima']) && !empty($request['employee']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['employee']['id_business_partner_ima'];
            }elseif(isset($request['employee']['id_business_partner']) && !empty($request['employee']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['employee']['id_business_partner'];
            }
        }

        return self::sendRequest('POST', '/business_partner/employee_create', $data, $company, $logType, $orderId);
    }
    public static function ApiUpdateEmployee($request, $company = null, $logType = null, $orderId = null){
        
        $data = [
            "Code" => $request['employee']['code'],
            "ClusterID" => $request['employee']['id_cluster'],
            "Email" => $request['employee']['email'],
            "Type" => $request['employee']['type'],
            "NPWP" => $request['employee']['npwp'],
            "Name" => $request['employee']['name'],
            "JoinDate" => $request['employee']['start_date']??date('Y-m-d'),
            "TermOfPaymentID" => $request['employee']['id_term_payment'],
            "AddressDelivery" => $request['employee']['address_domicile'],
            "AddressInvoice" => $request['employee']['address_ktp'],
            "Notes" => $request['employee']['notes'],
            "NPWPName" => $request['employee']['npwp_name'],
            "PhoneNo" => $request['employee']['phone_number'],
            "ContactPerson" => $request['employee']['contact_person'],
            "MobileNo" => $request['employee']['phone'],
            "IsTax" => $request['employee']['IsTax']
        ];
        if($company=='PT IMS'){
            if(isset($request['employee']['id_business_partner_ima']) && !empty($request['employee']['id_business_partner_ima']) && isset($request['employee']['id_business_partner']) && !empty($request['employee']['id_business_partner']) ){
                $data['BusinessPartnerID'] = $request['employee']['id_business_partner'];
            }
        }else{
            if(isset($request['employee']['id_business_partner_ima']) && !empty($request['employee']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['employee']['id_business_partner_ima'];
            }elseif(isset($request['employee']['id_business_partner']) && !empty($request['employee']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['employee']['id_business_partner'];
            }
        }
        return self::sendRequest('PUT', '/business_partner/employee_update/'.$data['BusinessPartnerID'], $data, $company, $logType, $orderId);
    }
    public static function EmployeeReimbursement($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "VoucherNo" => "[AUTO]",
            "TransDate" => $request['reimbursement']['date_reimbursement'],
            "DueDate" =>  $request['reimbursement']['due_date'],
            "BranchID" => $request['outlet']['id_branch'],
            "TermOfPaymentID" => $request['employee']['id_term_payment']??'011',
            "ReferenceNo" => '',
            'TaxNo'=>'',
            "Notes" => $request['reimbursement']['notes'],
            "Detail" => [
                [
                    "Name" => $request['item']['name'],
                    "ItemID" =>$request['item']['id_item'],
                    "Unit"=>"PCS",
                    "Qty" => $request['reimbursement']['qty'],
                    'Price'=>$request['reimbursement']['price']*$request['reimbursement']['qty'],
                    "Description" => ""
                ],
            ]
        ];
        if($company=='PT IMA'){
                if(isset($request['location']['id_business_partner_ima']) && !empty($request['location']['id_business_partner_ima'])){
                    $data['BusinessPartnerID'] = $request['location']['id_business_partner_ima'];
                }
                if(isset($request['location']['id_branch_ima']) && !empty($request['location']['id_branch_ima'])){
                    $data['BranchID'] = $request['location']['id_branch_ima']; 
                }
            }
        return self::sendRequest('POST', '/purchase/create_reimbursement', $data, $company, $logType, $orderId);
    }
    public static function EmployeeCashAdvance($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "VoucherNo" => "[AUTO]",
            "TransDate" => $request['cash_advance']['date_cash_advance'],
            "DueDate" =>  $request['cash_advance']['tax_date'],
            "BranchID" => '013',
            "BusinessPartnerID" => $request['employee']['id_business_partner'],
            "amount" => $request['cash_advance']['price_realisasi']??0,
            "ChartOfAccountID" => '',
            "ReferenceNo" => '',
            'Tax'=>0,
            'TaxNo'=>'',
            "Notes" => $request['cash_advance']['notes'],
           
        ];
        if($company=='PT IMA'){
                if(isset($request['employee']['id_business_partner_ima']) && !empty($request['employee']['id_business_partner_ima'])){
                    $data['BusinessPartnerID'] = $request['employee']['id_business_partner_ima'];
                }
            }
        return self::sendRequest('POST', '/purchase/cash_advance_request', $data, $company, $logType, $orderId);
    }

    public static function searchBusinessPartner($id_business_partner, $cluster = '011', $company = null, $logType = null, $orderId = null){
        return self::sendRequest('GET', '/business_partner/list?ID='.$id_business_partner.'&ClusterID='.$cluster, $request, $logType, $orderId);
    }

    public static function ApiCreateHairStylist($request, $company = null, $logType = null, $orderId = null){
        $data = [
            "Name" => $request['hairstylist']['fullname'],
            "Code" => $request['hairstylist']['user_hair_stylist_code'],
            "TermOfPaymentID" => $request['hairstylist']['id_term_payment'],
            "Email" => $request['hairstylist']['email'],
            "ClusterID" => '012',
            "JoinDate" => $request['hairstylist']['join_date']? date('Y-m-d',strtotime($request['hairstylist']['join_date'])) : date('Y-m-d'),
        ];
        if($company=='PT IMS'){
            if(isset($request['hairstylist']['id_business_partner_ima']) && !empty($request['hairstylist']['id_business_partner_ima']) && isset($request['hairstylist']['id_business_partner']) && !empty($request['hairstylist']['id_business_partner']) ){
                $data['BusinessPartnerID'] = $request['hairstylist']['id_business_partner'];
            }
        }else{
            if(isset($request['hairstylist']['id_business_partner_ima']) && !empty($request['hairstylist']['id_business_partner_ima'])){
                $data['BusinessPartnerID'] = $request['hairstylist']['id_business_partner_ima'];
            }elseif(isset($request['hairstylist']['id_business_partner']) && !empty($request['hairstylist']['id_business_partner'])){
                $data['BusinessPartnerID'] = $request['hairstylist']['id_business_partner'];
            }
        }

        return self::sendRequest('POST', '/business_partner/employee_create', $data, $company, $logType, $orderId);
    }
}