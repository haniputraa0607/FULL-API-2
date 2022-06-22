<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Configs;
use App\Http\Models\LogBalance;
use App\Http\Models\Outlet;
use App\Http\Models\Province;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionPaymentOvo;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionProduct;
use App\Http\Models\UserAddress;
use App\Http\Models\LogTransactionUpdate;
use App\Jobs\ExportFranchiseJob;
use App\Jobs\FindingHairStylistHomeService;
use App\Lib\Midtrans;
use App\Lib\Ovo;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\Favorite\Entities\FavoriteUserHiarStylist;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;
use Modules\ProductService\Entities\ProductHairstylistCategory;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistLocation;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use App\Http\Models\TransactionPayment;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\StockLog;
use Modules\ProductService\Entities\ProductServiceUse;
use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionHomeServiceStatusUpdate;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\Transaction\Http\Requests\Transaction\NewTransaction;
use Modules\UserFeedback\Entities\UserFeedbackLog;
use Modules\Transaction\Entities\TransactionHomeServiceHairStylistFinding;
use DB;

class ApiManageHomeService extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->product      = "Modules\Product\Http\Controllers\ApiProductController";
        $this->online_trx      = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
        $this->mitra = "Modules\Recruitment\Http\Controllers\ApiMitra";
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

    public function manageList(Request $request)
    {
        $list = Transaction::where('transaction_from', 'home-service')
            ->join('transaction_home_services','transactions.id_transaction', 'transaction_home_services.id_transaction')
            ->join('users','transactions.id_user','=','users.id')
            ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_xendits', 'transactions.id_transaction', '=', 'transaction_payment_xendits.id_transaction')
            ->with('user')
            ->where('transaction_payment_status', 'Completed')
            // ->where(function($q) {
            // 	$q->whereNotIn('transaction_home_services.status', ['Cancelled', 'Completed'])
            // 	->orWhereNull('transaction_home_services.status');
            // })
            // ->whereNull('transactions.reject_at')
            ->select(
                'transaction_home_services.*',	
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
                'transaction_grandtotal',
                'transaction_payment_status',
                'status'
            ];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        if($col_name == 'status' && $rul['parameter'] == 'Waiting Complete Payment'){
                            $model2->whereNull('status');
                        }else{
                            $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                        }
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

    public function manageDetail(Request $request)
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
            ->join('transaction_home_services','transaction_home_services.id_transaction','=','transactions.id_transaction')
            ->leftJoin('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','=','transaction_home_services.id_user_hair_stylist')
            ->leftJoin('outlets', 'outlets.id_outlet', 'user_hair_stylist.id_outlet')
            ->first();

        if(!$trx){
            return MyHelper::checkGet($trx);
        }

        $trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
        $trx['payment'] = $trxPayment['payment'];
        $settingStart = Setting::where('key', 'home_service_time_start')->first()['value']??'07:00:00';
        $settingEnd = Setting::where('key', 'home_service_time_end')->first()['value']??'22:00:00';

        $trx->load('user','outlet');
        if(!$trx['outlet']){
            $transaction_outlet = Transaction::where(['transactions.id_transaction' => $id])->first();
            $outlet_transaction = Outlet::where('id_outlet',$transaction_outlet['id_outlet'])->first();
        }
        $city_transaction = $trx['outlet']['id_city'] ?? $outlet_transaction['id_city'];
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $city_transaction)->first()['time_zone_utc']??null;
        $date_time = $this->getTimezone($trx['schedule_time'], $timeZone, 'H:i');
        $result = [
            'id_transaction'                => $trx['id_transaction'],
            'transaction_receipt_number'    => $trx['transaction_receipt_number'],
            'receipt_qrcode' 				=> 'https://chart.googleapis.com/chart?chl=' . str_replace('#', '', $trx['transaction_receipt_number']) . '&chs=250x250&cht=qr&chld=H%7C0',
            'transaction_date'              => date('d M Y H:i', strtotime($trx['transaction_date'])),
            'transaction_grandtotal'        => MyHelper::requestNumber($trx['transaction_grandtotal'],'_CURRENCY'),
            'transaction_subtotal'          => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY'),
            'transaction_discount'          => MyHelper::requestNumber($trx['transaction_discount'],'_CURRENCY'),
            'transaction_cashback_earned'   => MyHelper::requestNumber($trx['transaction_cashback_earned'],'_POINT'),
            'trasaction_payment_type'       => $trx['trasaction_payment_type'],
            'trasaction_type'               => $trx['trasaction_type'],
            'transaction_payment_status'    => $trx['transaction_payment_status'],
            'booking_date'                  => $trx['schedule_date'],
            'booking_time'                  => $date_time['time'],
            'booking_time_zone'             => $date_time['time_zone_id'],
            'destination_name'              => $trx['destination_name'],
            'destination_phone'             => $trx['destination_phone'],
            'destination_address'           => $trx['destination_address'],
            'destination_short_address'     => $trx['destination_short_address'],
            'destination_address_name'      => $trx['destination_address_name'],
            'destination_note'              => $trx['destination_note'],
            'preference_hair_stylist'       => $trx['preference_hair_stylist'],
            'id_user_hair_stylist'          => $trx['id_user_hair_stylist'],
            'hair_stylist_status'           => $trx['status'],
            'hair_stylist_name'             => $trx['nickname'] ? $trx['nickname'] . ' - ' . $trx['fullname'] : null,
            'hair_stylist_outlet_name'      => $trx['outlet_name'],
            'continue_payment'              => $trxPayment['continue_payment'],
            'payment_gateway'               => $trxPayment['payment_gateway'],
            'payment_type'                  => $trxPayment['payment_type'],
            'payment_redirect_url'          => $trxPayment['payment_redirect_url'],
            'payment_redirect_url_app'      => $trxPayment['payment_redirect_url_app'],
            'payment_token'                 => $trxPayment['payment_token'],
            'total_payment'                 => (int) $trxPayment['total_payment'],
            'timer_shopeepay'               => $trxPayment['timer_shopeepay'],
            'message_timeout_shopeepay'     => $trxPayment['message_timeout_shopeepay'],
            'reject_at'     				=> $trx['reject_at'],
            'setting_time_start'             => $settingStart,
            'setting_time_end'             => $settingEnd,
            'user'							=> [
                'phone' => $trx['user']['phone'],
                'name' 	=> $trx['user']['name'],
                'email' => $trx['user']['email']
            ],

        ];

        $trxServices = TransactionProduct::where('id_transaction', $trx['id_transaction'])
            ->with(['product'])->get()->toArray();
        $totalItem = 0;
        foreach ($trxServices as $ts){
            $totalItem += $ts['transaction_product_qty'];
        }
        if(!$trx['outlet']){
            $transaction_outlet = Transaction::where(['transactions.id_transaction' => $id])->first();
            $outlet_transaction = Outlet::where('id_outlet',$transaction_outlet['id_outlet'])->first();
        }

        $result['product_service'] = $trxServices;
        $trx['transaction_item_service_total'] = $totalItem;

        $lastLog = LogInvalidTransaction::where('id_transaction', $trx['id_transaction'])->orderBy('updated_at', 'desc')->first();

        $result['image_invalid_flag'] = NULL;
        if(!empty($trx['image_invalid_flag'])){
            $result['image_invalid_flag'] =  config('url.storage_url_api').$trx['image_invalid_flag'];
        }

        $result['transaction_flag_invalid'] =  $trx['transaction_flag_invalid'];
        $result['flag_reason'] =  $lastLog['reason'] ?? '';
        $result['payment_detail'] = app($this->trx_outlet_service)->transactionPaymentDetail($trx);

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

    public function findHairstylist(Request $request)
    {
    	$hs = UserHairStylist::where('user_hair_stylist_status', 'Active')->get()->toArray();

    	if (isset($request->id_trx)) {
	    	$trx = TransactionHomeService::join(
	    					'subdistricts',
	    					'transaction_home_services.destination_id_subdistrict',
	    					'subdistricts.id_subdistrict'
	    				)
	    				->where('id_transaction', $request->id_trx)
	    				->first();
    	}

    	if(!empty($trx)){
    	    if(!empty($trx['id_user_hair_stylist'])){
                $idHsCategory = UserHairStylist::where('id_user_hair_stylist', $trx['id_user_hair_stylist'])->first()['id_hairstylist_category']??null;
            }

            $tmpHsCatGroup = [];
            $tmpHsCat = [];
            $arrProccessingTime = [];
    	    $trxProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                            ->where('id_transaction', $trx['id_transaction'])->get()->toArray();
    	    foreach ($trxProduct as $val){
                $hsCat = ProductHairstylistCategory::where('id_product', $val['id_product'])->pluck('id_hairstylist_category')->toArray();
                foreach ($hsCat as $cat){
                    $tmpHsCatGroup[$cat] = ($tmpHsCatGroup[$cat]??0) + 1;
                    $tmpHsCat[$val['id_product']][] = $cat;
                }

                $processingTime = Product::where('id_product', $val['id_product'])->first()['processing_time_service']??0;
                $arrProccessingTime[] = $processingTime * $val['transaction_product_qty'];
            }


            if(empty($idHsCategory) && !empty($tmpHsCatGroup)){
                $idHsCategory = array_search(max($tmpHsCatGroup), $tmpHsCatGroup);
            }

    	    $sumtime = array_sum($arrProccessingTime);
    	    $bookDate = date('Y-m-d', strtotime($trx['schedule_date']));
            $bookTime = date('H:i:s', strtotime($trx['schedule_time']));
            if(!empty($request->schedule_date)){
                $bookDate = date('Y-m-d', strtotime($request->schedule_date));
                $bookTime = date('H:i:s', strtotime($request->schedule_time));
            }
            $startTime = date('Y-m-d H:i:s', strtotime($bookDate.' '.$bookTime));
            $endTime = date('Y-m-d H:i', strtotime("+".$sumtime." minutes", strtotime($startTime)));
            $day = [
                'Mon' => 'Senin',
                'Tue' => 'Selasa',
                'Wed' => 'Rabu',
                'Thu' => 'Kamis',
                'Fri' => 'Jumat',
                'Sat' => 'Sabtu',
                'Sun' => 'Minggu'
            ];
            $bookDay = $day[date('D', strtotime($bookDate))];

            $hsNotAvailable = HairstylistNotAvailable::whereRaw('((booking_start >= "'.$startTime.'" AND booking_end <= "'.$endTime.'")
                            OR (booking_start <= "'.$startTime.'" AND booking_end >= "'.$endTime.'"))')
                ->pluck('id_user_hair_stylist')->toArray();
            $maximumRadius = (int)(Setting::where('key', 'home_service_hs_maximum_radius')->first()['value']??25);
            foreach ($hs as $key=>$val){
                $distance = (float)app($this->outlet)->distance($trx['destination_latitude'], $trx['destination_longitude'], $val['latitude'], $val['longitude'], "K");
                if($distance > $maximumRadius){
                    unset($hs[$key]);
                    continue;
                }

                if(!empty($idHsCategory) && !empty($val['id_hairstylist_category']) && $val['id_hairstylist_category'] != $idHsCategory){
                    unset($hs[$key]);
                    continue;
                }

                if(array_search($val['id_user_hair_stylist'], $hsNotAvailable)!== false){
                    unset($hs[$key]);
                    continue;
                }

                if($val['home_service_status'] == 0){
                    unset($hs[$key]);
                    continue;
                }

                //check schedule hs
                $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                        ->whereNotNull('approve_at')->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                        ->whereDate('date', $bookDate)
                        ->first()['shift']??'';
                if(!empty($shift)){
                    $idOutletSchedule = OutletSchedule::where('id_outlet', $val['id_outlet'])
                            ->where('day', $bookDay)->first()['id_outlet_schedule']??null;
                    $getTimeShift = app($this->product)->getTimeShift(strtolower($shift),$val['id_outlet'], $idOutletSchedule);
                    if(!empty($getTimeShift['start']) && !empty($getTimeShift['end'])){
                        $shiftTimeStart = date('H:i:s', strtotime($getTimeShift['start']));
                        $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
                        if(($bookTime >= $shiftTimeStart) && ($bookTime <= $shiftTimeEnd)){
                            unset($hs[$key]);
                            continue;
                        }
                    }
                }
            }
        }

        $hs = array_values($hs);
    	return MyHelper::checkGet($hs);
    }

    public function manageDetailUpdate(Request $request)
    {
    	$post = $request->all();

    	if ($request->submit_type == 'reject') {
    		return $this->rejectTransactionHomeService($request);
    	}

    	$trxTemp = Transaction::with(
    		'transaction_home_service_hairstylist_finding',
    		'hairstylist_not_available',
    		'transaction_products.product',
    		'transaction_home_service',
            'user'
    	);

    	$trx = $trxTemp->find($request->id_transaction);
    	$oldTrx = $trxTemp->find($request->id_transaction);
    	if (!$trx) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction not found']
			];
		}

		if ($trx->reject_at) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction rejected']
			];
		}

		$trxHome = $trx->transaction_home_service;
		if (!$trxHome) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction not found']
			];
		}
        
    	\DB::beginTransaction();

		HairstylistNotAvailable::where('id_transaction', $request->id_transaction)->delete();

		$newDatetime = date('Y-m-d H:i', strtotime($request->schedule_date . ' ' . $request->schedule_time));
		$oldDatetime = date('Y-m-d H:i', strtotime($trxHome->schedule_date . ' ' . $trxHome->schedule_time));

		$processingTime = 0;
		foreach ($trx->transaction_products as $tp) {
			$processingTime = $processingTime + $tp['product']['processing_time_service'] ?? 0;
		}
		$bookStart = $newDatetime;
		$bookEnd = date('Y-m-d H:i', strtotime("+" . $processingTime . " minutes", strtotime($bookStart)));

		$id_hs = $request->id_user_hair_stylist ?? $trxHomeService->id_user_hair_stylist;

		if ($id_hs) {
			$hsNotAvailable = HairstylistNotAvailable::where('id_user_hair_stylist', $id_hs)
            ->whereRaw('((booking_start >= "'.$bookStart.'" AND booking_start <= "'.$bookEnd.'") 
                        OR (booking_end > "'.$bookStart.'" AND booking_end < "'.$bookEnd.'"))')
            ->first();

            if(!empty($hsNotAvailable)){
	        	$bookEndIndo = MyHelper::adjustTimezone($hsNotAvailable->booking_end, $timezone ?? 7, 'l, d F Y H:i', true);
	        	\DB::rollBack();
	            return [
	    			'status' => 'fail',
	    			'messages' => ["Hair stylist booked until ".$bookEndIndo]
	    		];
	        }
		}

		$trxHome->schedule_date = date('Y-m-d', strtotime($request->schedule_date));
		$trxHome->schedule_time = date('H:i', strtotime($request->schedule_time));

		if ($trxHome->id_user_hair_stylist != $request->id_user_hair_stylist) {

			$outletService = app($this->mitra)->outletServiceScheduleStatus($request->id_user_hair_stylist, $bookStart);
	    	if ($outletService['is_available']) {
				$bookStartIndo = MyHelper::adjustTimezone($bookStart, $timezone ?? 7, 'l, d F Y H:i', true);
				return [
	    			'status' => 'fail',
	    			'messages' => ["Hair stylist has outlet service schedule on " . $bookStartIndo]
	    		];
	    	}

			$trxHome->id_user_hair_stylist = $request->id_user_hair_stylist;
			$trxHome->status = 'Get Hair Stylist';

			TransactionHomeServiceHairStylistFinding::where('id_transaction', $request->id_transaction)->delete();
		}

		$trxHome->save();


		app($this->online_trx)->bookHS($request->id_transaction);

    	$newTrx = $trxTemp->find($request->id_transaction);

        if($trxHome['status'] == 'Finding Hair Stylist'){
            $createUpdateStatus = TransactionHomeServiceStatusUpdate::create(['id_transaction' => $trxHome['id_transaction'],'status' => 'Get Hair Stylist']);
            if($createUpdateStatus){
                TransactionHomeService::where('id_transaction', $trxHome['id_transaction'])->update(['status' => 'Get Hair Stylist']);

                if(!empty($trxTemp['user']['phone'])){
                    app($this->autocrm)->SendAutoCRM(
                        'Home Service Update Status',
                        $trxTemp['user']['phone'],
                        [
                            'id_transaction' => $trxTemp['id_transaction'],
                            'status'=> 'Get Hair Stylist',
                            'receipt_number' => $trxTemp['transaction_receipt_number']
                        ]
                    );
                }
            }
        }

    	$logTrx = LogTransactionUpdate::create([
			'id_user' => $request->user()->id,
	    	'id_transaction' => $request->id_transaction,
	    	'transaction_from' => 'home-service',
	        'old_data' => json_encode($oldTrx),
	        'new_data' => json_encode($newTrx),
	    	'note' => $request->note
		]);

    	if ($logTrx) {
			DB::commit();
    	}

		return MyHelper::checkCreate($logTrx);
    }

    public function rejectTransactionHomeService(Request $request)
    {
		$post = $request->json()->all();
		$post['reject_reason'] = $post['note'] ?? null;

    	$tempTrx = Transaction::with([
			'transaction_home_service_hairstylist_finding',
    		'hairstylist_not_available',
    		'transaction_products.product',
    		'transaction_home_service',
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
	    	'transaction_from' => 'home-service',
	        'old_data' => json_encode($oldTrx),
	        'new_data' => json_encode($newTrx),
	    	'note' => $post['reject_reason']
		]);

    	return MyHelper::checkCreate($logTrx);
    }
}
