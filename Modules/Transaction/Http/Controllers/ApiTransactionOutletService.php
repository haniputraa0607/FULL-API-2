<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Product\Entities\ProductIcount;
use Modules\Product\Entities\ProductProductIcount;
use Modules\Transaction\Entities\ManualRefund;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionOutletService;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceLog;

use App\Http\Models\Deal;
use App\Http\Models\TransactionProductModifier;
use Illuminate\Pagination\Paginator;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionPayment;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPickupWehelpyou;
use App\Http\Models\Province;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\Courier;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use App\Http\Models\Setting;
use App\Http\Models\StockLog;
use App\Http\Models\UserAddress;
use App\Http\Models\ManualPayment;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\ManualPaymentTutorial;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentBalance;
use Modules\Disburse\Entities\MDR;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\Outlet;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\LogTransactionUpdate;
use Modules\ProductVariant\Entities\ProductVariant;
use Modules\ProductVariant\Entities\TransactionProductVariant;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use App\Http\Models\DealsUser;
use App\Http\Models\DealsPaymentMidtran;
use App\Http\Models\DealsPaymentManual;
use Modules\IPay88\Entities\DealsPaymentIpay88;
use Modules\ShopeePay\Entities\DealsPaymentShopeePay;
use App\Http\Models\UserTrxProduct;
use App\Http\Models\OutletSchedule;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;

use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionBundlingProduct;
use Modules\Transaction\Entities\HairstylistNotAvailable;

use Modules\Transaction\Http\Requests\RuleUpdate;

use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;

use Modules\UserRating\Entities\UserRatingLog;

use DB;
use Modules\Franchise\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\TransactionPromo;

class ApiTransactionOutletService extends Controller
{
	function __construct() {
        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->outlet = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->refund = "Modules\Transaction\Http\Controllers\ApiTransactionRefund";
    }

    public function getTimezone($time = null, $time_zone_utc = 7, $format = 'Y-m-d H:i'){
        $data['time_zone_id'] = 'WIB';
        $default_time_zone_utc = 7;
        $time_diff = $time_zone_utc - $default_time_zone_utc;
        if(isset($time)){
        $data['time'] = date($format, strtotime('+'.$time_diff.' hour',strtotime($time)));
        }else{
        $data['time'] = date($format, strtotime('+'.$time_diff.' hour'));
        }
        switch ($time_zone_utc) {
            case 8:
                $data['time_zone_id'] = 'WITA';
            break;
            case 9:
                $data['time_zone_id'] = 'WIT';
            break;
        }
        return $data;
    }

    public function listOutletService(Request $request)
    {	
    	$list = Transaction::where('transaction_from', 'outlet-service')
    			->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
	            ->join('users','transactions.id_user','=','users.id')
	            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
	            ->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')
	            ->leftJoin('transaction_product_services','transactions.id_transaction','=','transaction_product_services.id_transaction')
                ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                ->leftJoin('transaction_payment_xendits', 'transactions.id_transaction', '=', 'transaction_payment_xendits.id_transaction')
	            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
	            ->with('user')
	            ->select(
	            	'transaction_product_services.*',
	            	'transaction_outlet_services.*',
	            	'products.*',
	            	'transaction_products.*',
	            	'outlets.*',
	            	'users.*',
	            	'transactions.*'
	            )
	            ->groupBy('transactions.id_transaction');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->getQuery()->getCountForPagination();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
            	'id_transaction',
                'transaction_date',
                'outlet_code',
                'transaction_receipt_number', 
                'name', 
                'phone',
                'transaction_grandtotal', 
                'transaction_payment_status', 
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $list->orderBy($colname, $column['dir']);
                }
            }
        }
        $list->orderBy('transactions.id_transaction', $column['dir'] ?? 'DESC');
        
        if ($request->page) {
            $list = $list->paginate($request->length ?: 15);
            $list->each(function($item) {
                $item->images = array_map(function($item) {
                    return config('url.storage_url_api').$item;
                }, json_decode($item->images) ?? []);
            });
            $list = $list->toArray();
            if (is_null($countTotal)) {
                $countTotal = $list['total'];
            }
            // needed by datatables
            $list['recordsTotal'] = $countTotal;
            $list['recordsFiltered'] = $list['total'];
            $list['data'] = array_map(function($val){
                $outlet = Outlet::where('id_outlet',$val['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                $date_time = $this->getTimezone($val['transaction_date'], $timeZone);
                $val['transaction_date'] = $date_time['time'].' '.$date_time['time_zone_id'];
                return $val;
            },$list['data']);
        } else {
            $list = $list->get();
            $list = array_map(function($val){
                $outlet = Outlet::where('id_outlet',$val['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                $date_time = $this->getTimezone($val['transaction_date'], $timeZone);
                $val['transaction_date'] = $date_time['time'].' '.$date_time['time_zone_id'];
                return $val;
            },$list->toArray());
        }
        return MyHelper::checkGet($list);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function($model2) use ($model, $where, $new_rule){
            $inner = [
            	'transaction_receipt_number',
            	'outlet_name',
            	'outlet_code',
            	'transaction_grandtotal',
            	'transaction_payment_status'
            ];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['order_id'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('transaction_product_services.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['name', 'phone', 'email'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('users.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['id_outlet'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('transactions.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['payment'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $explode = explode('-', $rul['parameter']);
                        $paymentGateway = $explode[0];
                        $paymentMethod = $explode[1];
                        if($paymentGateway == 'Cash'){
                            $model2->$where('transactions.trasaction_payment_type', 'Cash');
                        }elseif($paymentGateway == 'Midtrans'){
                            $model2->$where('transaction_payment_midtrans.payment_type',  $paymentMethod);
                        }elseif($paymentGateway == 'Xendit'){
                            $model2->$where('transaction_payment_xendits.type',  $paymentMethod);
                        }
                    }
                }
            }
        });

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(transaction_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }

    public function detailTransaction(Request $request)
    {
    	if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $trx = Transaction::where(['transactions.id_transaction' => $id])
        		->leftJoin('transaction_outlet_services','transaction_outlet_services.id_transaction','=','transactions.id_transaction')
        		->first();

        if(!$trx){
            return MyHelper::checkGet($trx);
        }

        $trxPromo = $this->transactionPromo($trx);

        $trxProducts = $this->transactionProduct($trx);
        $trx['product_transaction'] = $trxProducts['product'];
        $productCount = $trxProducts['count'];

        $trxProductServices = $this->transactionProductService($trx);
        $trx['product_service_transaction'] = $trxProductServices['product_service'];
        $productServiceCount = $trxProductServices['count'];

    	$cart = $trx['transaction_subtotal'] + $trx['transaction_shipment'] + $trx['transaction_service'] + $trx['transaction_tax'] - $trx['transaction_discount'];
    	$trx['transaction_carttotal'] = $cart;
        $trx['transaction_item_total'] = $productCount;
        $trx['transaction_item_service_total'] = $productServiceCount;

        $trxPayment = $this->transactionPayment($trx);
        $trx['payment'] = $trxPayment['payment'];
       
        $trx->load('user','outlet');
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $trx['outlet']['id_city'])->first()['time_zone_utc']??null;
        $date_time = $this->getTimezone($trx['transaction_date'], $timeZone);
        $result = [
            'id_transaction'                => $trx['id_transaction'],
            'transaction_receipt_number'    => $trx['transaction_receipt_number'],
            'receipt_qrcode' 				=> 'https://chart.googleapis.com/chart?chl=' . $trx['transaction_receipt_number'] . '&chs=250x250&cht=qr&chld=H%7C0',
            'transaction_date'              => date('d M Y H:i', strtotime($date_time['time'])),
            'transaction_date_timezone'     => $date_time['time_zone_id'],
            'trasaction_type'               => $trx['trasaction_type'],
            'transaction_grandtotal'        => MyHelper::requestNumber($trx['transaction_grandtotal'],'_CURRENCY'),
            'transaction_subtotal'          => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY'),
            'transaction_discount'          => MyHelper::requestNumber($trx['transaction_discount'],'_CURRENCY'),
            'transaction_cashback_earned'   => MyHelper::requestNumber($trx['transaction_cashback_earned'],'_POINT'),
            'transaction_tax'               => $trx['transaction_tax'],
            'mdr'                           => $trx['mdr'],
            'trasaction_payment_type'       => $trx['trasaction_payment_type'],
            'transaction_payment_status'    => $trx['transaction_payment_status'],
            'continue_payment'              => $trxPayment['continue_payment'],
            'payment_gateway'               => $trxPayment['payment_gateway'],
            'payment_type'                  => $trxPayment['payment_type'],
            'payment_redirect_url'          => $trxPayment['payment_redirect_url'],
            'payment_redirect_url_app'      => $trxPayment['payment_redirect_url_app'],
            'payment_token'                 => $trxPayment['payment_token'],
            'total_payment'                 => (int) $trxPayment['total_payment'],
            'timer_shopeepay'               => $trxPayment['timer_shopeepay'],
            'message_timeout_shopeepay'     => $trxPayment['message_timeout_shopeepay'],
            'need_manual_void'              => $trx['need_manual_void'],
            'reject_type'                   => $trx['reject_type'],
            'outlet'                        => [
                'outlet_name'    => $trx['outlet']['outlet_name'],
                'outlet_address' => $trx['outlet']['outlet_address']
            ],
            'user'							=> [
                'phone' => $trx['user']['phone'],
	            'name' 	=> $trx['user']['name'],
	            'email' => $trx['user']['email']
            ],

        ];

        $lastLog = LogInvalidTransaction::where('id_transaction', $trx['id_transaction'])->orderBy('updated_at', 'desc')->first();

        $result['image_invalid_flag'] = NULL;
        if(!empty($trx['image_invalid_flag'])){
            $result['image_invalid_flag'] =  config('url.storage_url_api').$trx['image_invalid_flag'];
        }

        $result['transaction_flag_invalid'] =  $trx['transaction_flag_invalid'];
        $result['flag_reason'] =  $lastLog['reason'] ?? '';

        $formatedTrxProduct = $this->formatTransactionProduct($trx);
        $trx['total_product_qty'] = $formatedTrxProduct['qty'];
        $result['product_transaction'] = $formatedTrxProduct['result'] ?? [];

        $formatedTrxProductService = $this->formatTransactionProductService($trx);
        $trx['total_product_service_qty'] = $formatedTrxProductService['qty'];
        $result['product_service_transaction'] = $formatedTrxProductService['result'] ?? [];

        $result['payment_detail'] = $this->transactionPaymentDetail($trx);
        
        if($result['payment_detail'] && isset($trxPromo)){
            $lastKey = array_key_last($result['payment_detail']);
            for($i = 0; $i < count($trxPromo); $i++){
                $KeyPosition = 1 + $i;
                $result['payment_detail'][$lastKey+$KeyPosition] = $trxPromo[$i];
            }
        }

        if(!isset($trx['payment'])){
            $result['transaction_payment'] = null;
        }else{
            foreach ($trx['payment'] as $key => $value) {
                if ($value['name'] == 'Balance') {
                    $result['transaction_payment'][$key] = [
                        'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                        'is_balance'=> 1,
                        'amount'    => MyHelper::requestNumber($value['amount'],'_POINT')
                    ];
                } else {
                    $result['transaction_payment'][$key] = [
                        'name'      => $value['name'],
                        'amount'    => MyHelper::requestNumber($value['amount'],'_CURRENCY')
                    ];
                }
            }
        }

        return MyHelper::checkGet($result);
    }

    public function transactionProduct(Transaction $trx)
    {	
    	$trx = clone $trx;
    	$trx->load([
    		'productTransaction.product.product_category',
            'productTransaction.product.product_photos',
            'productTransaction.product.product_discounts',
            'productTransaction.modifiers',
            'productTransaction.variants' => function($query){
                $query->select('id_transaction_product','transaction_product_variants.id_product_variant','transaction_product_variants.id_product_variant','product_variants.product_variant_name', 'transaction_product_variant_price')->join('product_variants','product_variants.id_product_variant','=','transaction_product_variants.id_product_variant');
            }
    	]);
    	$trx = $trx->toArray();
    	$productCount = 0;
    	$trxProduct = MyHelper::groupIt($trx['product_transaction'], 'id_brand', null, function($key, &$val) use (&$productCount) {
            $productCount += array_sum(array_column($val,'transaction_product_qty'));
            $brand = Brand::select('name_brand')->find($key);
            if(!$brand){
                return 'No Brand';
            }
            return $brand->name_brand;
        });

        return [
        	'product' => $trxProduct,
        	'count' => $productCount
        ];
    }

    public function transactionProductService(Transaction $trx)
    {
    	$trx = clone $trx;
    	$trx->load(
    		'productServiceTransaction.product.product_category',
            'productServiceTransaction.product.product_photos'
    	);
    	$trx = $trx->toArray();
    	$productServiceCount = 0;
    	$trxProductService = MyHelper::groupIt($trx['product_service_transaction'], 'id_brand', null, function($key, &$val) use (&$productServiceCount) {
            $productServiceCount += array_sum(array_column($val,'transaction_product_qty'));
            $brand = Brand::select('name_brand')->find($key);
            if(!$brand){
                return 'No Brand';
            }
            return $brand->name_brand;
        });

    	return [
        	'product_service' => $trxProductService,
        	'count' => $productServiceCount
        ];
    }

    public function transactionPromo(Transaction $trx){
        $trx = clone $trx;
        $promo_discount = [];
        $promos = TransactionPromo::where('id_transaction', $trx['id_transaction'])->get()->toArray();
        if($promos){
            $promo_discount[0]=[
                "name"  => "Promo / Discount :",
                "is_discount" => 0,
                "amount" => null 
            ];
            foreach($promos as $p => $promo){
                if($promo['promo_type']=='Promo Campaign'){
                    $promo['promo_name'] = PromoCampaign::where('promo_title',$promo['promo_name'])->select('campaign_name')->first()['campaign_name'];
                }
                $promo_discount[$p+1] = [
                    "name"  => $promo['promo_name'],
                    "is_discount" => 1,
                    "amount" => '- '.MyHelper::requestNumber($promo['discount_value'],'_CURRENCY')
                ];
            }
        }
        return $promo_discount;
    }

    public function transactionPayment(Transaction $trx)
    {
    	$trx = clone $trx;
    	$trx = $trx->toArray();
    	$redirectUrlApp = "";
        $redirectUrl = "";
        $tokenPayment = "";
        $continuePayment = false;
        $totalPayment = 0;
        $shopeeTimer = 0;
        $shopeeMessage = "";
        $paymentType = "";
        $paymentGateway = "";
    	switch ($trx['trasaction_payment_type']) {
            case 'Balance':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get()->toArray();
                if ($multiPayment) {
                    foreach ($multiPayment as $keyMP => $mp) {
                        switch ($mp['type']) {
                            case 'Balance':
                                $log = LogBalance::where('id_reference', $mp['id_transaction'])->where('source', 'Online Transaction')->first();
                                if ($log['balance'] < 0) {
                                    $trx['balance'] = $log['balance'];
                                    $trx['check'] = 'tidak topup';
                                } else {
                                    $trx['balance'] = $trx['transaction_grandtotal'] - $log['balance'];
                                    $trx['check'] = 'topup';
                                }
                                $trx['payment'][] = [
                                    'name'      => 'Balance',
                                    'amount'    => $trx['balance']
                                ];
                                break;
                            case 'Manual':
                                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $trx['id_transaction'])->first();
                                $trx['payment'] = $payment;
                                $trx['payment'][] = [
                                    'name'      => 'Cash',
                                    'amount'    => $payment['payment_nominal']
                                ];
                                break;
                            case 'Midtrans':
                                $payMidtrans = TransactionPaymentMidtran::find($mp['id_payment']);
                                $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                                $payment['amount']    = $payMidtrans->gross_amount;
                                $trx['payment'][] = $payment;
                                if($trx['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)) {
                                    $redirectUrl = $payMidtrans->redirect_url;
                                    $tokenPayment = $payMidtrans->token;
                                    $continuePayment =  true;
                                    $totalPayment = $payMidtrans->gross_amount;
                                    $paymentType = strtoupper($payMidtrans->payment_type);
                                    $paymentGateway = 'Midtrans';
                                }
                                break;
                            case 'Ovo':
                                $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                $payment['name']    = 'OVO';
                                $trx['payment'][] = $payment;
                                break;
                            case 'IPay88':
                                $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                $payment['name']    = $PayIpay->payment_method;
                                $payment['amount']    = $PayIpay->amount / 100;
                                $trx['payment'][] = $payment;
                                if($trx['transaction_payment_status'] == 'Pending'){
                                    $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$trx['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                                    $continuePayment =  true;
                                    $totalPayment = $PayIpay->amount / 100;
                                    $paymentType = strtoupper($PayIpay->payment_method);
                                    $paymentGateway = 'IPay88';
                                }
                                break;
                            case 'Shopeepay':
                                $shopeePay = TransactionPaymentShopeePay::find($mp['id_payment']);
                                $payment['name']    = 'ShopeePay';
                                $payment['amount']  = $shopeePay->amount / 100;
                                $payment['reject']  = $shopeePay->err_reason?:'payment expired';
                                $trx['payment'][]  = $payment;
                                if($trx['transaction_payment_status'] == 'Pending'){
                                    $redirectUrl = $shopeePay->redirect_url_http;
                                    $redirectUrlApp = $shopeePay->redirect_url_app;
                                    $continuePayment =  true;
                                    $totalPayment = $shopeePay->amount / 100;
                                    $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                                    $shopeeMessage ='Sorry, your payment has expired';
                                    $paymentGateway = 'Shopeepay';
                                }
                                break;
                            case 'Xendit':
                                $payXendit = TransactionPaymentXendit::find($dataPay['id_payment']);
                                $payment[$dataKey]['name']      = $payXendit->type??'';
                                $payment[$dataKey]['amount']    = $payXendit->amount;
                                $payment[$dataKey]['reject']    = $payXendit->err_reason?:'payment expired';
                                if($trx['transaction_payment_status'] == 'Pending') {
                                    $redirectUrl = $payXendit->redirect_url_http;
                                    $redirectUrlApp = $payXendit->redirect_url_app;
                                    $continuePayment =  true;
                                    $totalPayment = $payXendit->amount;
                                    $paymentGateway = 'Xendit';
                                }
                                break;
                            case 'Offline':
                                $payment = TransactionPaymentOffline::where('id_transaction', $trx['id_transaction'])->get();
                                foreach ($payment as $key => $value) {
                                    $trx['payment'][$key] = [
                                        'name'      => $value['payment_bank'],
                                        'amount'    => $value['payment_amount']
                                    ];
                                }
                                break;
                            default:
                                break;
                        }
                    }
                } else {
                    $log = LogBalance::where('id_reference', $trx['id_transaction'])->first();
                    if ($log['balance'] < 0) {
                        $trx['balance'] = $log['balance'];
                        $trx['check'] = 'tidak topup';
                    } else {
                        $trx['balance'] = $trx['transaction_grandtotal'] - $log['balance'];
                        $trx['check'] = 'topup';
                    }
                    $trx['payment'][] = [
                        'name'      => 'Balance',
                        'amount'    => $trx['balance']
                    ];
                }
                break;
            case 'Manual':
                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $trx['id_transaction'])->first();
                $trx['payment'] = $payment;
                $trx['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['payment_nominal']
                ];
                break;
            case 'Midtrans':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Midtrans'){
                        $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                        $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                        if($trx['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)){
                            $redirectUrl = $payMidtrans->redirect_url;
                            $tokenPayment = $payMidtrans->token;
                            $continuePayment =  true;
                            $totalPayment = $payMidtrans->gross_amount;
                            $paymentType = strtoupper($payMidtrans->payment_type);
                            $paymentGateway = 'Midtrans';
                        }

                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Ovo':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Ovo'){
                        $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = 'OVO';
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Ipay88':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'IPay88'){
                        $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = $PayIpay->payment_method;
                        $payment[$dataKey]['amount']    = $PayIpay->amount / 100;

                        if($trx['transaction_payment_status'] == 'Pending'){
                            $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$trx['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                            $continuePayment =  true;
                            $totalPayment = $PayIpay->amount / 100;
                            $paymentType = strtoupper($PayIpay->payment_method);
                            $paymentGateway = 'Ipay88';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Shopeepay':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Shopeepay'){
                        $payShopee = TransactionPaymentShopeePay::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = 'ShopeePay';
                        $payment[$dataKey]['amount']    = $payShopee->amount / 100;
                        $payment[$dataKey]['reject']    = $payShopee->err_reason?:'payment expired';
                        if($trx['transaction_payment_status'] == 'Pending') {
                            $redirectUrl = $payShopee->redirect_url_http;
                            $redirectUrlApp = $payShopee->redirect_url_app;
                            $continuePayment =  true;
                            $totalPayment = $payShopee->amount / 100;
                            $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                            $shopeeMessage ='Sorry, your payment has expired';
                            $paymentGateway = 'Shopeepay';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey]              = $dataPay;
                        $trx['balance']                = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']      = 'Balance';
                        $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Xendit':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Xendit'){
                        $payXendit = TransactionPaymentXendit::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = $payXendit->type??'';
                        $payment[$dataKey]['amount']    = $payXendit->amount ;
                        $payment[$dataKey]['reject']    = $payXendit->err_reason?:'payment expired';
                        if($trx['transaction_payment_status'] == 'Pending') {
                            $redirectUrl = $payXendit->redirect_url_http;
                            $redirectUrlApp = $payXendit->redirect_url_app;
                            $continuePayment =  true;
                            $totalPayment = $payXendit->amount;
                            $paymentGateway = 'Xendit';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey]              = $dataPay;
                        $trx['balance']                = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']      = 'Balance';
                        $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Offline':
                $payment = TransactionPaymentOffline::where('id_transaction', $trx['id_transaction'])->get();
                foreach ($payment as $key => $value) {
                    $trx['payment'][$key] = [
                        'name'      => $value['payment_bank'],
                        'amount'    => $value['payment_amount']
                    ];
                }
                break;

            case 'Cash':
                $payment = TransactionPaymentCash::where('id_transaction', $trx['id_transaction'])->first();
                $trx['payment'] = [];
                $trx['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['cash_nominal']
                ];
                break;

            default:
                break;
        }

        $res = [
        	'payment' 					=> $trx['payment'] ?? [],
        	'continue_payment'          => $continuePayment,
            'payment_gateway'           => $paymentGateway,
            'payment_type'              => $paymentType,
            'payment_redirect_url'      => $redirectUrl,
            'payment_redirect_url_app'  => $redirectUrlApp,
            'payment_token'             => $tokenPayment,
            'total_payment'             => (int)$totalPayment,
            'timer_shopeepay'           => $shopeeTimer,
            'message_timeout_shopeepay' => $shopeeMessage,
        ];

        return $res;
    }

    public function formatTransactionProduct(Transaction $trx)
    {
    	$discount = 0;
        $quantity = 0;
        $keynya = 0;
        $result = [];
        foreach ($trx['product_transaction'] as $keyTrx => $valueTrx) {
            $result['product_transaction'][$keynya]['brand'] = $keyTrx;
            foreach ($valueTrx as $keyProduct => $valueProduct) {
                $quantity = $quantity + $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_qty']              = $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_product_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_sub_item']         = '@'.MyHelper::requestNumber($valueProduct['transaction_product_subtotal'] / $valueProduct['transaction_product_qty'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_modifier_subtotal']        = MyHelper::requestNumber($valueProduct['transaction_modifier_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_variant_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_variant_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']             = $valueProduct['transaction_product_note'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']         = $valueProduct['transaction_product_discount'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']              = $valueProduct['product']['product_name'];
                $discount = $discount + $valueProduct['transaction_product_discount'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'] = [];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = [];
                $extra_modifiers = [];
                $extra_modifier_price = 0;
                foreach ($valueProduct['modifiers'] as $keyMod => $valueMod) {
                    if (!$valueMod['id_product_modifier_group']) {
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_name']   = $valueMod['text'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_qty']    = $valueMod['qty'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'][$keyMod]['product_modifier_price']  = MyHelper::requestNumber($valueMod['transaction_product_modifier_price']*$valueProduct['transaction_product_qty'],'_CURRENCY');
                    } else {
                        $extra_modifiers[] = $valueMod['id_product_modifier'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['id_product_variant']   = $valueMod['id_product_modifier'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['product_variant_name']   = $valueMod['text'];
                        $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']['m'.$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_modifier_price'];
                        $extra_modifier_price += (int) ($valueMod['qty'] * $valueMod['transaction_product_modifier_price']);
                    }
                }
                $variantsPrice = 0;
                foreach ($valueProduct['variants'] as $keyMod => $valueMod) {
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['id_product_variant']   = $valueMod['id_product_variant'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_name']   = $valueMod['product_variant_name'];
                    $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'][$keyMod]['product_variant_price']  = (int)$valueMod['transaction_product_variant_price'];
                    $variantsPrice = $variantsPrice + $valueMod['transaction_product_variant_price'];
                }
                $variantsPrice += $extra_modifier_price;
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = array_values($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']);
                if ($valueProduct['id_product_variant_group'] ?? false) {
                    $order = array_flip(Product::getVariantParentId($valueProduct['id_product_variant_group'], Product::getVariantTree($valueProduct['id_product'], $list['outlet'])['variants_tree'], $extra_modifiers));
                    usort($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'], function ($a, $b) use ($order) {
                        return ($order[$a['id_product_variant']]??999) <=> ($order[$b['id_product_variant']]??999);
                    });
                }
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product_variant_group_price'] = (int)($valueProduct['transaction_product_price'] + $variantsPrice);

                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers'] = array_values($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_modifiers']);
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants'] = array_values($result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_variants']);
            }
            $keynya++;
        }

        return [
        	'result' => $result['product_transaction'] ?? [],
        	'discount' => $discount,
        	'qty' => $quantity
        ];
    }

    public function formatTransactionProductService(Transaction $trx)
    {
    	$discount = 0;
        $quantity = 0;
        $keynya = 0;
        $result = [];
        foreach ($trx['product_service_transaction'] as $keyTrx => $valueTrx) {
            $result['product_transaction'][$keynya]['brand'] = $keyTrx;
            foreach ($valueTrx as $keyProduct => $valueProduct) {
                $quantity = $quantity + $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_qty']              = $valueProduct['transaction_product_qty'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_product_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_sub_item']         = '@'.MyHelper::requestNumber($valueProduct['transaction_product_subtotal'] / $valueProduct['transaction_product_qty'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_modifier_subtotal']        = MyHelper::requestNumber($valueProduct['transaction_modifier_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_variant_subtotal']         = MyHelper::requestNumber($valueProduct['transaction_variant_subtotal'],'_CURRENCY');
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_note']             = $valueProduct['transaction_product_note'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['transaction_product_discount']         = $valueProduct['transaction_product_discount'];
                $result['product_transaction'][$keynya]['product'][$keyProduct]['product']['product_name']              = $valueProduct['product']['product_name'];
                $discount = $discount + $valueProduct['transaction_product_discount'];
            }
            $keynya++;
        }

        return [
        	'result' => $result['product_transaction'] ?? [],
        	'discount' => $discount,
        	'qty' => $quantity
        ];
    }

    public function transactionPaymentDetail(Transaction $trx)
    {
    	$trx = clone $trx;
    	$trx->load(
    		'transaction_vouchers.deals_voucher.deal',
            'promo_campaign_promo_code.promo_campaign',
            'transaction_payment_subscription.subscription_user_voucher',
            'subscription_user_voucher',
    	);

    	$paymentDetail = [];
    	$totalItem = $trx['transaction_item_total'] + $trx['transaction_item_service_total'];
        $paymentDetail[] = [
            'name'      => 'Subtotal',
            'desc'      => $totalItem . ' items',
            'amount'    => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY')
        ];

    	// if ($trx['transaction_discount']) {
        // 	$discount = abs($trx['transaction_discount']);
        //     $p = 0;
        //     if (!empty($trx['transaction_vouchers'])) {
        //         foreach ($trx['transaction_vouchers'] as $valueVoc) {
        //             $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
        //             $paymentDetail[] = [
        //                 'name'          => 'Diskon',
        //                 'desc'          => 'Promo',
        //                 "is_discount"   => 1,
        //                 'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
        //             ];
        //         }
        //     }

        //     if (!empty($trx['promo_campaign_promo_code'])) {
        //         $result['promo']['code'][$p++]   = $trx['promo_campaign_promo_code']['promo_code'];
        //         $paymentDetail[] = [
        //             'name'          => 'Diskon',
        //             'desc'          => 'Promo',
        //             "is_discount"   => 1,
        //             'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
        //         ];
        //     }

        //     if (!empty($trx['id_subscription_user_voucher']) && !empty($trx['transaction_discount'])) {
        //         $paymentDetail[] = [
        //             'name'          => 'Subscription',
        //             'desc'          => 'Diskon',
        //             "is_discount"   => 1,
        //             'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
        //         ];
        //     }
        // }

        if ($trx['transaction_shipment_go_send'] > 0) {
            $paymentDetail[] = [
                'name'      => 'Delivery',
                'desc'      => $trx['detail']['pickup_by'],
                'amount'    => MyHelper::requestNumber($trx['transaction_shipment_go_send'],'_CURRENCY')
            ];
        }elseif($trx['transaction_shipment'] > 0){
            $getListDelivery = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
            $shipmentCode = strtolower($trx['shipment_method'].'_'.$trx['shipment_courier']);
            if($trx['shipment_method'] == 'GO-SEND'){
                $shipmentCode = 'gosend';
            }

            $search = array_search($shipmentCode, array_column($getListDelivery, 'code'));
            $shipmentName = ($search !== false ? $getListDelivery[$search]['delivery_name']:strtoupper($trx['shipment_courier']));
            $paymentDetail[] = [
                'name'      => 'Delivery',
                'desc'      => $shipmentName,
                'amount'    => MyHelper::requestNumber($trx['transaction_shipment'],'_CURRENCY')
            ];
        }

        if ($trx['transaction_discount_delivery']) {
        	$discount = abs($trx['transaction_discount_delivery']);
            $p = 0;
            if (!empty($trx['transaction_vouchers'])) {
                foreach ($trx['transaction_vouchers'] as $valueVoc) {
                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                    $paymentDetail[] = [
                        'name'          => 'Diskon',
                        'desc'          => 'Delivery',
                        "is_discount"   => 1,
                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                    ];
                }
            }

            if (!empty($trx['promo_campaign_promo_code'])) {
                $result['promo']['code'][$p++]   = $trx['promo_campaign_promo_code']['promo_code'];
                $paymentDetail[] = [
                    'name'          => 'Diskon',
                    'desc'          => 'Delivery',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                ];
            }

            if (!empty($trx['id_subscription_user_voucher']) && !empty($trx['transaction_discount_delivery'])) {
                $paymentDetail[] = [
                    'name'          => 'Subscription',
                    'desc'          => 'Delivery',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                ];
            }
        }

        return $paymentDetail;
    }

    public function manageList(Request $request)
    {	
    	$list = Transaction::where('transaction_from', 'outlet-service')
    			->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
	            ->join('users','transactions.id_user','=','users.id')
	            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
	            ->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')
	            ->leftJoin('transaction_product_services','transactions.id_transaction','=','transaction_product_services.id_transaction')
	            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
                ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
                ->leftJoin('transaction_payment_xendits', 'transactions.id_transaction', '=', 'transaction_payment_xendits.id_transaction')
	            ->with('user')
	            ->where('transaction_payment_status', 'Completed')
	            ->whereNull('transaction_products.transaction_product_completed_at')
	            ->whereNull('transaction_products.reject_at')
	            ->whereNull('transactions.reject_at')
	            ->select(
	            	'transaction_product_services.*',
	            	'transaction_outlet_services.*',
	            	'products.*',
	            	'transaction_products.*',
	            	'outlets.*',
	            	'users.*',
	            	'transactions.*'
	            )
	            ->groupBy('transactions.id_transaction');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->getQuery()->getCountForPagination();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
            	'id_transaction',
                'transaction_date',
                'outlet_code',
                'transaction_receipt_number', 
                'name', 
                'phone',
                'transaction_grandtotal', 
                'transaction_payment_status', 
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $list->orderBy($colname, $column['dir']);
                }
            }
        }
        $list->orderBy('transactions.id_transaction', $column['dir'] ?? 'DESC');

        if ($request->page) {
            $list = $list->paginate($request->length ?: 15);
            $list->each(function($item) {
                $item->images = array_map(function($item) {
                    return config('url.storage_url_api').$item;
                }, json_decode($item->images) ?? []);
            });
            $list = $list->toArray();
            if (is_null($countTotal)) {
                $countTotal = $list['total'];
            }
            // needed by datatables
            $list['recordsTotal'] = $countTotal;
            $list['recordsFiltered'] = $list['total'];
            $list['data'] = array_map(function($val){
                $outlet = Outlet::where('id_outlet',$val['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                $date_time = $this->getTimezone($val['transaction_date'], $timeZone);
                $val['transaction_date'] = $date_time['time'].' '.$date_time['time_zone_id'];
                return $val;
            },$list['data']);
        } else {
            $list = $list->get();
            $list = array_map(function($val){
                $outlet = Outlet::where('id_outlet',$val['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                $date_time = $this->getTimezone($val['transaction_date'], $timeZone);
                $val['transaction_date'] = $date_time['time'].' '.$date_time['time_zone_id'];
                return $val;
            },$list->toArray());
        }
        return MyHelper::checkGet($list);
    }

    public function manageDetail(Request $request, $id_transaction)
    {
    	$detail = Transaction::where('transaction_from', 'outlet-service')
    			->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
    			->where('transactions.id_transaction', $id_transaction)
    			->orderBy('transaction_date', 'desc')
    			->with(
    				'outlet.brands', 
    				'outlet.city.province', 
    				'transaction_outlet_service', 
    				'transaction_products.transaction_product_service.user_hair_stylist',
    				'transaction_products.product.photos',
    				'user_feedbacks'
    			)
    			->select([
    				'transactions.*', 
    				'transaction_outlet_services.*',
    				'transactions.reject_at',
    				'transactions.reject_reason'
    			])
    			->first();

		if (!$detail) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction not found']
			];
		}

		$outlet = [
			'id_outlet' => $detail['outlet']['id_outlet'],
			'outlet_code' => $detail['outlet']['outlet_code'],
			'outlet_name' => $detail['outlet']['outlet_name'],
			'outlet_address' => $detail['outlet']['outlet_address'],
			'outlet_latitude' => $detail['outlet']['outlet_latitude'],
			'outlet_longitude' => $detail['outlet']['outlet_longitude']
		];

		$brand = [
			'id_brand' => $detail['outlet']['brands'][0]['id_brand']??null,
			'brand_code' => $detail['outlet']['brands'][0]['code_brand']??null,
			'brand_name' => $detail['outlet']['brands'][0]['name_brand']??null,
			'brand_logo' => $detail['outlet']['brands'][0]['logo_brand']??null,
            'brand_logo_landscape' => $detail['outlet']['brands'][0]['logo_landscape_brand']??null
		];

		$products = [];
		$services = [];
		$subtotalProduct = 0;
		$subtotalService = 0;
		$timezone = $detail['outlet']['city']['province']['time_zone_utc'] ?? null;

		foreach ($detail['transaction_products'] as $product) {
			$productPhoto = config('url.storage_url_api') . ($product['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
			if ($product['type'] == 'Service') {
                $schedule_time = $this->getTimezone($product['transaction_product_service']['schedule_time'],$timezone,'H:i');
				$services[] = [
					'id_user_hair_stylist' => $product['transaction_product_service']['id_user_hair_stylist'],
					'hairstylist_name' => $product['transaction_product_service']['user_hair_stylist']['nickname'],
					'schedule_date' => MyHelper::dateFormatInd($product['transaction_product_service']['schedule_date'], true, false),
					'schedule_time' => $schedule_time['time'],
					'schedule_time_zone' => $schedule_time['time_zone_id'],
                    'id_product' => $product['product']['id_product'],
					'product_name' => $product['product']['product_name'],
					'subtotal' => 'IDR '.number_format(($product['transaction_product_subtotal']),0,',','.'),
					'order_id' => $product['transaction_product_service']['order_id'],
					'photo' => $productPhoto,
					'detail' => $product
				];

				$subtotalService += abs($product['transaction_product_subtotal']);
			} else {
				$products[] = [
					'product_name' => $product['product']['product_name'],
					'transaction_product_qty' => $product['transaction_product_qty'],
					'transaction_product_price' => 'IDR '.number_format(($product['transaction_product_price']),0,',','.'),
					'transaction_product_subtotal' => $product['transaction_product_subtotal'],
					'subtotal' => 'IDR '.number_format(($product['transaction_product_subtotal']),0,',','.'),
					'photo' => $productPhoto,
					'detail' => $product
				];
				$subtotalProduct += abs($product['transaction_product_subtotal']);
			}
		}

		if ($detail['transaction_payment_status'] == 'Pending') {
			$status = 'unpaid';
		} elseif ($detail['transaction_payment_status'] == 'Cancelled') {
			$status = 'cancelled';
		} elseif (empty($detail['completed_at']) && $detail['transaction_payment_status'] == 'Completed') {
			$status = 'ongoing';
		} else {
			$status = 'completed';
		}

		$paymentDetail = [];
        
        $paymentDetail[] = [
            'name'          => 'Total',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'],'_CURRENCY')
        ];

        if (!empty($detail['transaction_tax'])) {
	        $paymentDetail[] = [
	            'name'          => 'Tax',
	            "is_discount"   => 0,
	            'amount'        => MyHelper::requestNumber($detail['transaction_tax'],'_CURRENCY')
	        ];
        }
    	
        $trx = Transaction::where('id_transaction', $detail['id_transaction'])->first();
		$trxPayment = $this->transactionPayment($trx);
    	$paymentMethod = null;
    	foreach ($trxPayment['payment'] as $p) {
    		$paymentMethod = $p['name'];
    		if (strtolower($p['name']) != 'balance') {
    			break;
    		}
    	}

    	$paymentCashCode = null;
    	if ($detail['transaction_payment_status'] == 'Pending' && $detail['trasaction_payment_type'] == 'Cash') {
    		$paymentCash = TransactionPaymentCash::where('id_transaction', $detail['id_transaction'])->first();
    		$paymentCashCode = $paymentCash->payment_code;
    	}

    	$listHs = UserHairStylist::where('id_outlet', $detail['id_outlet'])
    			->where('user_hair_stylist_status', 'Active')
    			->select('id_user_hair_stylist', 'user_hair_stylist_code', 'nickname', 'fullname', 'phone_number', 'email')
    			->get();

		$res = [
			'id_transaction' => $detail['id_transaction'],
			'transaction_receipt_number' => $detail['transaction_receipt_number'],
			'qrcode' => 'https://chart.googleapis.com/chart?chl=' . $detail['transaction_receipt_number'] . '&chs=250x250&cht=qr&chld=H%7C0',
			'transaction_date' => $detail['transaction_date'],
			'transaction_date_indo' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($detail['transaction_date'])), 'j F Y'),
			'transaction_subtotal' => $detail['transaction_subtotal'],
			'transaction_grandtotal' => $detail['transaction_grandtotal'],
			'transaction_tax' => $detail['transaction_tax'],
			'transaction_product_subtotal' => $subtotalProduct,
			'transaction_service_subtotal' => $subtotalService,
			'need_manual_void' => $detail['need_manual_void'],
			'reject_at' => $detail['reject_at'],
			'reject_reason' => $detail['reject_reason'],
			'customer_name' => $detail['transaction_outlet_service']['customer_name'],
			'color' => $detail['outlet']['brands'][0]['color_brand']??null,
			'status' => $status,
			'transaction_payment_status' => $detail['transaction_payment_status'],
			'payment_method' => $paymentMethod,
			'payment_cash_code' => $paymentCashCode,
			'outlet' => $outlet,
			'brand' => $brand,
			'service' => $services,
			'product' => $products,
			'payment_detail' => $paymentDetail,
			'list_hs' => $listHs
		];
		
		return MyHelper::checkGet($res);
    }

    public function manageDetailUpdate(Request $request)
    {
    	$post = $request->all();

    	if ($request->submit_type == 'reject') {
    		return $this->manageDetailReject($request);
    	}
    	$tps = TransactionProductService::find($request->id_transaction_product_service);

		if (!$tps) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction product service not found']
			];
		}

		$hna = HairstylistNotAvailable::where('id_transaction_product_service', $request->id_transaction_product_service)->first();
		if (!$hna) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction product service not found']
			];
		}

		$trx = Transaction::with([
				'transaction_product_services' => function($q) use ($tps) {
					$q->where('id_transaction_product_service', $tps->id_transaction_product_service);
				} ,
				'hairstylist_not_available' => function($q) use ($hna) {
					$q->where('id_hairstylist_not_available', $hna->id_hairstylist_not_available);
				}
			]);

		$oldTrx = $trx->find($tps->id_transaction);

		$tps->load(
			'user_hair_stylist', 
			'transaction.transaction_outlet_service', 
			'transaction_product.product'
		);

    	$outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
	            ->join('provinces', 'provinces.id_province', 'cities.id_province')
	            ->with('today')
	            ->select('outlets.*', 'provinces.time_zone_utc as province_time_zone_utc')
	            ->where('id_outlet', $tps['transaction']['id_outlet'])
	            ->first();

        if (!$outlet) {
			return [
				'status' => 'fail',
				'messages' => ['Outlet not found']
			];
		}

		$newBookDateTime = date('Y-m-d H:i:s', strtotime($post['schedule_date'] . ' ' . $post['schedule_time']));
		$newBookDateTime = $bookStart = MyHelper::reverseAdjustTimezone($newBookDateTime, $outlet['province_time_zone_utc'], 'Y-m-d H:i:s');
		$checkSchedule = $this->checkOutletServiceSchedule($post['id_user_hair_stylist'], $newBookDateTime, $tps['transaction_product']['id_product']);
		if ($checkSchedule['status'] == 'fail') {
			return $checkSchedule;
		}

		$processingTime = Product::find($tps['transaction_product']['id_product'])['processing_time_service'] ?? null;
    	$bookEnd = date('Y-m-d H:i:s', strtotime("+".(empty($processingTime) ? 30 : $processingTime)." minutes", strtotime($bookStart)));

		DB::beginTransaction();
		$tps->update([
			'schedule_date' => date('Y-m-d', strtotime($bookStart)),
			'schedule_time' => date('H:i:s', strtotime($bookStart)),
			'id_user_hair_stylist' => $post['id_user_hair_stylist'],
			'is_conflict' => 0
		]);

		HairstylistNotAvailable::where('id_transaction_product_service', $request->id_transaction_product_service)->update([
			'booking_start' => $bookStart,
			'booking_end' => $bookEnd,
            'id_user_hair_stylist' => $post['id_user_hair_stylist']
		]);

		$newTrx = $trx->find($tps->id_transaction);

		$logTrx = LogTransactionUpdate::create([
			'id_user' => $request->user()->id,
	    	'id_transaction' => $tps->id_transaction,
	    	'transaction_from' => 'outlet-service',
	        'old_data' => json_encode($oldTrx),
	        'new_data' => json_encode($newTrx),
	    	'note' => $post['note']
		]);

		DB::commit();

		return MyHelper::checkCreate($logTrx);
    }

    public function checkOutletServiceSchedule($id_hs, $bookDateTime, $id_product) {

    	$hs = UserHairStylist::where('user_hair_stylist_status', 'Active')->find($id_hs);

    	if (!$hs) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Hair stylist not found']
    		];
    	}

    	$outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->with('today')->select('outlets.*', 'provinces.time_zone_utc as province_time_zone_utc')
            ->find($hs->id_outlet);

        if (!$outlet) {
    		return [
    			'status' => 'fail',
    			'messages' => ['Outlet not found']
    		];
    	}

        $timeZone = 7; // all datetime use utc+7 timezone
        $currentDate = MyHelper::adjustTimezone(date('Y-m-d H:i'), $timeZone, 'Y-m-d H:i');
        $bookingDate = date('Y-m-d', strtotime($bookDateTime));
        $bookingTime = date('H:i', strtotime($bookDateTime));
        $bookDateIndo = MyHelper::adjustTimezone($bookDateTime, $outlet['province_time_zone_utc'] ?? 7, 'l, d F Y H:i', true);
        $currDateIndo = MyHelper::adjustTimezone($currentDate, $outlet['province_time_zone_utc'] ?? 7, 'l, d F Y H:i', true);

        $idOutletSchedule = $outlet['today']['id_outlet_schedule'] ?? null;

        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];

        $tempStock = [];

        $err = [];
        $outletSchedule = OutletSchedule::where('id_outlet', $outlet['id_outlet'])->where('day', $day[date('D', strtotime($bookingDate))])->first();
        $open = date('H:i:s', strtotime($outletSchedule['open']));
        $close = date('H:i:s', strtotime($outletSchedule['close']));
        $currentHour = date('H:i:s', strtotime($bookingTime));

        if (strtotime($currentHour) < strtotime($open) || strtotime($currentHour) > strtotime($close) || $outletSchedule['is_closed'] == 1) {
        	return [
    			'status' => 'fail',
    			'messages' => ['Outlet closed on ' . $bookDateIndo]
    		];
        }

        $isHoliday = app($this->outlet)->isHoliday($outlet['id_outlet'], $bookDateTime);
        if ($isHoliday['status']) {
        	return [
    			'status' => 'fail',
    			'messages' => ['Outlet closed on ' . $bookDateIndo]
    		];
        }

        $bookTime = date('Y-m-d H:i', strtotime(date('Y-m-d', strtotime($bookingDate)).' '.date('H:i', strtotime($bookingTime))));
        if (strtotime($currentDate) > strtotime($bookTime)) {
        	return [
    			'status' => 'fail',
    			'messages' => ['Booking time must be after '.$currDateIndo]
    		];
        }

        //get hs schedule
        $shift = HairstylistScheduleDate::where('id_user_hair_stylist', $id_hs)
                ->leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')
                ->whereDate('date', date('Y-m-d', strtotime($bookingDate)))
                ->first()['shift'] ?? null;
        if (empty($shift)) {
        	return [
    			'status' => 'fail',
    			'messages' => ["Hair stylist " . $hs->nickname . " - " . $hs->fullname . " not available on ".$bookDateIndo.', shift not found']
    		];
        }

        $getTimeShift = app($this->product)->getTimeShift(strtolower($shift), $outlet['id_outlet'], $outletSchedule->id_outlet_schedule);
        if (empty($getTimeShift['start']) && empty($getTimeShift['end'])) {
        	return [
    			'status' => 'fail',
    			'messages' => ["Hair stylist " . $hs->nickname . " - " . $hs->fullname . " not available on ".$bookDateIndo.', shift hour not found']
    		];
        } else {
            $shiftTimeStart = date('H:i:s', strtotime($getTimeShift['start']));
            $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
            $time = date('H:i', strtotime($bookingTime));
            if ((strtotime($time) >= strtotime($shiftTimeStart) && strtotime($time) < strtotime($shiftTimeEnd)) === false) {
            	return [
	    			'status' => 'fail',
	    			'messages' => ["Hair stylist " . $hs->nickname . " - " . $hs->fullname . " not available on ".$bookDateIndo.', shift hour not available']
	    		];
            }
        }

        $service = Product::find($id_product);
        if (empty($service)) {
            return [
    			'status' => 'fail',
    			'messages' => ["Service not found"]
    		];
        }

        $processingTime = $service['processing_time_service'];
        $bookTimeStart = date("Y-m-d H:i:s", strtotime($bookingDate.' '.$bookingTime));
        $bookTimeEnd = date('Y-m-d H:i:s', strtotime("+".$processingTime." minutes", strtotime($bookTimeStart)));
        $hsNotAvailable = HairstylistNotAvailable::where('id_outlet', $outlet['id_outlet'])
            ->whereRaw('((booking_start >= "'.$bookTimeStart.'" AND booking_start <= "'.$bookTimeEnd.'") 
                        OR (booking_end > "'.$bookTimeStart.'" AND booking_end < "'.$bookTimeEnd.'"))')
            ->where('id_user_hair_stylist', $id_hs)
            ->first();

        if(!empty($hsNotAvailable)){
        	$bookEndIndo = MyHelper::adjustTimezone($hsNotAvailable->booking_end, $outlet['province_time_zone_utc'] ?? 7, 'l, d F Y H:i', true);
            return [
    			'status' => 'fail',
    			'messages' => ["Hair stylist " . $hs->nickname . " - " . $hs->fullname . " not available on ".$bookDateIndo.', booked until '.$bookEndIndo]
    		];
        }

        return ['status' => 'success'];
    }

    public function manageDetailReject(Request $request)
    {
    	$post = $request->all();

    	$trxProduct = TransactionProduct::with('transaction_product_service')->find($request->id_transaction_product);

		if (!$trxProduct) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction product not found']
			];
		}

		if ($trxProduct->reject_at) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction already rejected']
			];
		}

		if ($trxProduct->transaction_product_completed_at) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction product already completed']
			];
		}

		$trx = Transaction::with([
			'transaction_products.transaction_product_service.hairstylist_not_available',
			'transaction_products.transaction_product_service.user_hair_stylist',
			'transaction_outlet_service',
			'outlet',
			'user'
		])
		->find($trxProduct->id_transaction);

		$oldTrx = clone $trx;

		$rejected = 0;
		$completed = 0;
		$unprocessed = 0;
		$totalItem = count($trx['transaction_products']) - 1;

		foreach ($trx['transaction_products'] as $tp) {
			if ($tp['id_transaction_product'] == $trxProduct->id_transaction_product) {
				continue;
			}

			if ($tp['transaction_product_completed_at']) {
				$completed++;
				continue;
			}

			if ($tp['reject_at']) {
				$rejected++;
				continue;
			}

			$unprocessed++;
			break;
		}

		DB::beginTransaction();
		$trxProduct->update([
			'reject_at' => date('Y-m-d H:i:s'),
			'reject_reason' => $request->note		
		]);

		// return stok for product and remove book for service
		if (isset($trxProduct['transaction_product_service']['id_transaction_product_service'])) {
			HairstylistNotAvailable::where('id_transaction_product_service', $trxProduct['transaction_product_service']['id_transaction_product_service'])->delete();

			if (isset($trxProduct['transaction_product_service']['user_hair_stylist']['phone_number'])) {
	    		$phoneHs = $trxProduct['transaction_product_service']['user_hair_stylist']['phone_number'];

	    		app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                    'Mitra HS - Transaction Service Rejected',
                    $phoneHs,
                    [
                    	'date' => $trx['transaction_date'],
                    	'outlet_name' => $trx['outlet']['outlet_name'],
                    	'detail' => $detail ?? null,
                    	'receipt_number' => $trx['transaction_receipt_number'],
                    	'order_id' => $trxProduct['transaction_product_service']['order_id']
                    ], null, false, false, 'hairstylist'
                );
			}
		} else {
			$this->returnProductStock($trxProduct->id_transaction_product);

			$phoneSpv = UserHairStylist::where('id_outlet', $trx['id_outlet'])
						->where('level', 'Supervisor')
						->where('user_hair_stylist_status', 'Active')
						->first()['phone_number'];

			app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                'Mitra SPV - Transaction Product Rejected',
                $phoneSpv,
                [
                    'date' => $trx['transaction_date'],
                	'outlet_name' => $trx['outlet']['outlet_name'],
                	'detail' => $detail ?? null,
                	'receipt_number' => $trx['transaction_receipt_number']
                ], null, false, false, 'hairstylist'
            );
		}

		if (!empty($completed) && ($completed == $totalItem || ($completed + $rejected) == $totalItem)) {
			// completed
			TransactionOutletService::where('id_transaction', $trx->id_transaction)
			->update(['completed_at' => date('Y-m-d H:i:s')]);

			$trx = $trx->load('outlet','user');
    		app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
	        	'Transaction Completed', 
	        	$trx->user->phone, 
	        	[
		            'date' => $trx['transaction_date'],
	            	'outlet_name' => $trx['outlet']['outlet_name'],
	            	'detail' => $detail ?? null,
	            	'receipt_number' => $trx['transaction_receipt_number']
		        ]
		    );
		} elseif (empty($unprocessed) || $rejected == $totalItem) {
			// rejected
			TransactionOutletService::where('id_transaction', $trx->id_transaction)
			->update([
				'reject_at' => date('Y-m-d H:i:s'),
				'reject_reason' => $request->note
			]);

			app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
            	'Transaction Rejected', 
            	$trx->user->phone, 
            	[
		            'date' => $trx['transaction_date'],
                	'outlet_name' => $trx['outlet']['outlet_name'],
                	'detail' => $detail ?? null,
                	'receipt_number' => $trx['transaction_receipt_number']
		        ]
		    );
		}

		$newTrx = Transaction::with([
			'transaction_products.transaction_product_service.hairstylist_not_available',
			'transaction_outlet_service'
		])
		->find($trx->id_transaction);

		$logTrx = LogTransactionUpdate::create([
			'id_user' => $request->user()->id,
	    	'id_transaction' => $trx->id_transaction,
	    	'transaction_from' => 'outlet-service',
	        'old_data' => json_encode($oldTrx),
	        'new_data' => json_encode($newTrx),
	    	'note' => $request->note
		]);

		DB::commit();
        //check if anyone is rejected
        app($this->refund)->refundNotFullPayment($trx->id_transaction);

		return MyHelper::checkCreate($logTrx);
    }

    function returnProductStock($id_transaction_product) {
        $dt = TransactionProduct::where('transaction_products.id_transaction_product', $id_transaction_product)
            ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->select('transaction_products.*', 'transactions.id_outlet')
            ->where('type', 'Product')
            ->first();

        if ($dt) {
            $getProductUse = ProductProductIcount::join('product_detail', 'product_detail.id_product', 'product_product_icounts.id_product')
                ->where('product_product_icounts.id_product', $dt['id_product'])
                ->where('product_detail.id_outlet', $dt['id_outlet'])->get()->toArray();

            foreach ($getProductUse as $productUse){
                $product_icount = new ProductIcount();
                $product_icount->find($productUse['id_product_icount'])->addLogStockProductIcount(($productUse['qty']*$dt['transaction_product_qty']), $productUse['unit'], 'Cancelled Book Product', $dt['id_transaction'], null, $dt['id_outlet']);
            }
        }

        return true;
    }

    public function rejectTransactionOutletService(Request $request)
    {
		$post = $request->json()->all();
    	$tempTrx = Transaction::with([
			'transaction_products.transaction_product_service.hairstylist_not_available',
			'transaction_outlet_service'
		]);

    	$trx = $tempTrx->find($request->id_transaction);
    	if (!$trx) {
    		return ['status' => 'fail', 'messages' => ['Transaction not found']];
    	}

    	if ($trx->reject_at) {
    		return ['status' => 'fail', 'messages' => ['Transaction already rejected']];
    	}

    	$oldTrx = clone $trx;
    	$trx->triggerReject($post);

    	$newTrx = $tempTrx->find($request->id_transaction);


    	$logTrx = LogTransactionUpdate::create([
			'id_user' => $request->user()->id,
	    	'id_transaction' => $request->id_transaction,
	    	'transaction_from' => 'outlet-service',
	        'old_data' => json_encode($oldTrx),
	        'new_data' => json_encode($newTrx),
	    	'note' => $post['reject_reason']
		]);

    	return MyHelper::checkCreate($logTrx);
    }

    public function cancelCashPayment(Request $request){
		$post = $request->json()->all();
        $transaction = Transaction::find($post['id_transaction']);
        if(!$transaction){
            return [
                'status' => 'fail'
            ];
        }
        $cancel = $transaction->triggerPaymentCancelled();
        if(!$cancel){
            return [
                'status' => 'fail'
            ];
        }
        return [
            'status' => 'success'
        ];
    }
}
