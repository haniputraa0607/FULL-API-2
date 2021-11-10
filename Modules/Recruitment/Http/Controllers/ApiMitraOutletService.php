<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;

use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;

use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionOutletService;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceLog;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Http\Requests\DetailCustomerQueueRequest;

use Modules\Outlet\Entities\OutletBox;

use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\UserRating\Entities\UserRatingLog;

use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiMitraOutletService extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->mitra = "Modules\Recruitment\Http\Controllers\ApiMitra";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
    }

    public function customerQueue(Request $request)
    {
    	$user = $request->user();
    	$queue = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
				->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
				->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
				->join('products', 'transaction_products.id_product', 'products.id_product')
				->where(function($q) {
	    			$q->whereNull('service_status');
	    			$q->orWhere('service_status', '!=', 'Completed');
				})
    			->where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
    			->where(function($q) {
	    			$q->where('trasaction_payment_type', 'Cash')
	    			->orWhere('transaction_payment_status', 'Completed');
				})
    			->where('transaction_payment_status', '!=', 'Cancelled')
    			->orderBy('schedule_date', 'asc')
    			->orderBy('schedule_time', 'asc')
				->paginate(10)
				->toArray();

		$serviceInProgress = TransactionProductService::where('service_status', 'In Progress')
							->where('id_user_hair_stylist', $user->id_user_hair_stylist)
							->first();

		$disable = 0;
		if ($serviceInProgress) {
			$disable = 1;
		}

 		$schedule = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
 		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
 		->whereDate('date', date('Y-m-d'))
 		->first();

		$resData = [];
		$dateNow = new DateTime("now");
		foreach ($queue['data'] ?? [] as $val) {
			$timerText = "";
			$dateSchedule = new DateTime($val['schedule_date'] . ' ' .$val['schedule_time']);
			$interval = $dateNow->diff($dateSchedule);
			$day = $interval->d;
			$hour = $interval->h;
			$minute = $interval->i;
			if ($day) {
				$timerText .= $day.' hari, '. $hour.' jam' ;
			} elseif ($hour) {
				$timerText .= $hour.' jam' ;
			} else {
				$timerText .= $minute.' menit' ;
			}

			$timerText .= (strtotime(date('Y-m-d H:i:s')) < strtotime($val['schedule_date'] . ' ' .$val['schedule_time'])) ? ' lagi' : ' lalu';

			$trx = Transaction::where('id_transaction', $val['id_transaction'])->first();
			$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
	    	$paymentMethod = null;
	    	foreach ($trxPayment['payment'] as $p) {
	    		$paymentMethod = $p['name'];
	    		if (strtolower($p['name']) != 'balance') {
	    			break;
	    		}
	    	}

	    	$buttonText = 'Layani';
	    	$paymentCash = 0;
	    	if ($val['transaction_payment_status'] == 'Pending' && $val['trasaction_payment_type'] == 'Cash') {
	    		$buttonText = 'Pembayaran';
	    		$paymentCash = 1;
	    	}

			$resData[] = [
				'id_transaction_product_service' => $val['id_transaction_product_service'],
				'order_id' => $val['order_id'] ?? null,
				'customer_name' => $val['customer_name'],
				'schedule_date' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($val['schedule_date'])), 'j F Y'),
				'schedule_time' => date('H:i', strtotime($val['schedule_time'])),
				'service_status' => $val['service_status'],
				'payment_method' => $paymentMethod,
				'product_name' => $val['product_name'],
				'price' => $val['transaction_product_net'],
				'timer_text' => $timerText,
				'button_text' => $buttonText,
				'disable' => $disable,
				'id_outlet_box' => $schedule->id_outlet_box ?? null,
				'flag_update_schedule' => $val['flag_update_schedule'],
				'is_conflict' => $val['is_conflict'],
				'payment_cash' => $paymentCash 
			];
		}

		$res = $queue;
		$res['data'] = $resData;
		return MyHelper::checkGet($res);
    }

    public function customerQueueDetail(DetailCustomerQueueRequest $request)
    {
    	$user = $request->user();
    	$checkQr = Transaction::where('transaction_receipt_number',$request->transaction_receipt_number)
    				->with('transaction_product_services')
    				->first();

    	if (!$checkQr) {
    		return [
				'status' => 'fail',
				'title' => 'QR code tidak terdaftar',
				'messages' => ['Tidak dapat memulai layanan menggunakan QR code ini.']
			];
    	}

    	$isNotValidQr = true;
    	foreach ($checkQr['transaction_product_services'] as $val) {
    		if ($val['id_transaction_product_service'] == $request->id_transaction_product_service) {
    			$isNotValidQr = false;
    			break;
    		}
    	}

    	if ($isNotValidQr) {
    		return [
				'status' => 'fail',
				'title' => 'QR code tidak sesuai',
				'messages' => ['Tidak dapat memulai layanan menggunakan QR code ini.']
			];
    	}

    	$queue = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
				->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
				->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
				->join('products', 'transaction_products.id_product', 'products.id_product')
    			->where('transaction_product_services.id_user_hair_stylist', $user->id_user_hair_stylist)
    			->where('id_transaction_product_service', $request->id_transaction_product_service)
    			->where('transaction_payment_status' ,'Completed')
				->first();

		if (!$queue) {
			return [
				'status' => 'fail',
				'messages' => ['Layanan tidak ditemukan']
			];
		}

		$serviceInProgress = TransactionProductService::where('service_status', 'In Progress')
							->where('id_user_hair_stylist', $user->id_user_hair_stylist)
							->first();

		$disable = 0;
		if ($serviceInProgress) {
			$disable = 1;
		}

 		$schedule = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
 		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
 		->whereDate('date', date('Y-m-d'))
 		->first();

 		$dateNow = new DateTime("now");
		$timerText = "";
		$dateSchedule = new DateTime($queue['schedule_date'] . ' ' .$queue['schedule_time']);
		$interval = $dateNow->diff($dateSchedule);
		$day = $interval->d;
		$hour = $interval->h;
		$minute = $interval->i;
		if ($day) {
			$timerText .= $day.' hari, '. $hour.' jam' ;
		} elseif ($hour) {
			$timerText .= $hour.' jam' ;
		} else {
			$timerText .= $minute.' menit' ;
		}

		$timerText .= (strtotime(date('Y-m-d H:i:s')) < strtotime($queue['schedule_date'] . ' ' .$queue['schedule_time'])) ? ' lagi' : ' lalu';

		$trx = Transaction::where('id_transaction', $val['id_transaction'])->first();
		$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
    	$paymentMethod = null;
    	foreach ($trxPayment['payment'] as $p) {
    		$paymentMethod = $p['name'];
    		if (strtolower($p['name']) != 'balance') {
    			break;
    		}
    	}

		$res = [
			'id_transaction_product_service' => $queue['id_transaction_product_service'],
			'order_id' => $queue['order_id'] ?? null,
			'customer_name' => $queue['customer_name'],
			'schedule_date' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($queue['schedule_date'])), 'j F Y'),
			'schedule_time' => date('H:i', strtotime($queue['schedule_time'])),
			'service_status' => $queue['service_status'],
			'payment_method' => $paymentMethod,
			'product_name' => $queue['product_name'],
			'timer_text' => $timerText,
			'button_text' => 'Layani',
			'disable' => $disable,
			'id_outlet_box' => $schedule->id_outlet_box ?? null,
			'flag_update_schedule' => $queue['flag_update_schedule'],
			'is_conflict' => $queue['is_conflict']
		];
		
		return MyHelper::checkGet($res);
    }

    public function availableBox(Request $request)
    {
    	$user = $request->user();
    	$box = OutletBox::where([
			['id_outlet', $user->id_outlet],
			['outlet_box_status', 'Active']
		])->get();

    	return MyHelper::checkGet($box);
    }

    public function startService(Request $request)
    {
    	$user = $request->user();
    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
					->where('id_transaction_product_service', $request->id_transaction_product_service)
					->first();

		if (!$service) {
			return [
				'status' => 'fail',
				'messages' => ['Layanan tidak ditemukan']
			];
		}

		if ($service->service_status == 'In Progress') {
			return [
				'status' => 'fail',
				'messages' => ['Layanan sudah dimulai']
			];
		}

		if ($service->service_status == 'Completed') {
			return [
				'status' => 'fail',
				'messages' => ['Layanan sudah selesai']
			];
		}

		$schedule = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
 		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
 		->whereDate('date', date('Y-m-d'))
 		->first();

 		if (!$schedule) {
			return [
				'status' => 'fail',
				'messages' => ['Jadwal Hairstylist tidak ditemukan']
			];
		}

 		if (isset($schedule->id_outlet_box) && $schedule->id_outlet_box != $request->id_outlet_box) {
 			return [
				'status' => 'fail',
				'messages' => ['Tidak dapat menggunakan box yang berbeda']
			];	
 		}

		$box = OutletBox::where('id_outlet_box', $request->id_outlet_box)->first();

		if (!$box) {
			return [
				'status' => 'fail',
				'messages' => ['Box tidak ditemukan']
			];
		}

		if ($box->outlet_box_status != 'Active') {
			return [
				'status' => 'fail',
				'messages' => ['Box tidak aktif']
			];
		}

		if ($box->outlet_box_use_status != 0) {
			return [
				'status' => 'fail',
				'messages' => ['Box sedang digunakan']
			];
		}

		$shift = app($this->mitra)->timeToShift(date('H:i:s'));
		$usedBox = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
 		->where('id_user_hair_stylist', '!=', $user->id_user_hair_stylist)
 		->whereDate('date', date('Y-m-d'))
 		->where('shift', $shift)
 		->where('id_outlet_box', $request->id_outlet_box)
 		->first();

 		if ($usedBox) {
			return [
				'status' => 'fail',
				'messages' => ['Box sudah dipilih oleh Hairstylist lain']
			];
		}

    	DB::beginTransaction();
    	try {
    		$action = ($service->service_status == 'Stopped') ? 'Resume' : 'Start';
	    	TransactionProductServiceLog::create([
	    		'id_transaction_product_service' => $request->id_transaction_product_service,
	    		'action' => $action
	    	]);

			$service->update([
				'service_status' => 'In Progress',
				'id_outlet_box' => $request->id_outlet_box
			]);

			$box->update(['outlet_box_use_status' => 1]);

			if (empty($schedule->id_outlet_box)) {
				HairstylistScheduleDate::where('id_hairstylist_schedule_date', $schedule->id_hairstylist_schedule_date)
				->update(['id_outlet_box' => $request->id_outlet_box]);
			}


			DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
			DB::rollback();
    		return [
				'status' => 'fail',
				'messages' => ['Gagal memulai layanan']
			];	
    	}


		return ['status' => 'success'];
    }

    public function stopService(Request $request)
    {
    	$user = $request->user();
    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
					->where('id_transaction_product_service', $request->id_transaction_product_service)
					->first();

		if (!$service) {
			return [
				'status' => 'fail',
				'messages' => ['Layanan tidak ditemukan']
			];
		}

		if ($service->service_status == 'Stopped') {
			return [
				'status' => 'fail',
				'messages' => ['Layanan sudah dihentikan']
			];
		}

		if ($service->service_status == 'Completed') {
			return [
				'status' => 'fail',
				'messages' => ['Layanan sudah selesai']
			];
		}

		$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();

		if (!$box) {
			return [
				'status' => 'fail',
				'messages' => ['Box tidak ditemukan']
			];
		}

    	DB::beginTransaction();
    	try {
    		TransactionProductServiceLog::create([
	    		'id_transaction_product_service' => $request->id_transaction_product_service,
	    		'action' => 'Stop'
	    	]);
    		
			$service->update([
				'service_status' => 'Stopped',
				'id_outlet_box' => null
			]);

			$box->update(['outlet_box_use_status' => 0]);

			DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
			DB::rollback();
    		return [
				'status' => 'fail',
				'messages' => ['Gagal menghentikan layanan']
			];	
    	}

		return ['status' => 'success'];
    }

    public function extendService(Request $request)
    {
    	$user = $request->user();
    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
					->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
					->join('products', 'transaction_products.id_product', 'products.id_product')
					->where('id_transaction_product_service', $request->id_transaction_product_service)
					->first();



		if (!$service) {
			return [
				'status' => 'fail',
				'messages' => ['Layanan tidak ditemukan']
			];
		}

		if ($service->flag_update_schedule) {
			return [
				'status' => 'fail',
				'messages' => ['Waktu layanan sudah diperpanjang, tidak dapat memperpanjang waktu lebih dari sekali']
			];
		}

		if ($service->service_status == 'Completed') {
			return [
				'status' => 'fail',
				'messages' => ['Layanan sudah selesai']
			];
		}

		if (empty($service->processing_time_service)) {
			return [
				'status' => 'fail',
				'messages' => ['Waktu pemrosesan tidak ditemukan']
			];
		}

		$processingTime = $service->processing_time_service;

		$extended = new DateTime('now +'. $processingTime .' minutes');
		$extendedTime = $extended->format('H:i:s');

    	DB::beginTransaction();
    	try {

    		TransactionProductServiceLog::create([
	    		'id_transaction_product_service' => $request->id_transaction_product_service,
	    		'action' => 'Extend'
	    	]);

			$service->update(['flag_update_schedule' => 1]);

			$conflictServices = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
					->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
					->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
					->join('products', 'transaction_products.id_product', 'products.id_product')
	    			->whereNull('service_status')
	    			->where('id_user_hair_stylist', $user->id_user_hair_stylist)
	    			->where('transaction_payment_status' ,'Completed')
	    			->whereDate('schedule_date', date('Y-m-d'))
	    			->where('schedule_time', '<', $extendedTime)
	    			->orderBy('schedule_date', 'asc')
	    			->orderBy('schedule_time', 'asc')
					->get();

			foreach ($conflictServices ?? [] as $conflict) {
				$conflict->update(['is_conflict' => 1]);
			}

			DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
			DB::rollback();
    		return [
				'status' => 'fail',
				'messages' => ['Gagal memperpanjang waktu layanan']
			];	
    	}

		return ['status' => 'success'];
    }

    public function completeService(Request $request)
    {
    	$user = $request->user();
    	$service = TransactionProductService::where('id_user_hair_stylist', $user->id_user_hair_stylist)
					->where('id_transaction_product_service', $request->id_transaction_product_service)
					->first();

		if (!$service) {
			return [
				'status' => 'fail',
				'messages' => ['Layanan tidak ditemukan']
			];
		}

		if ($service->service_status == 'Completed') {
			return [
				'status' => 'fail',
				'messages' => ['Layanan sudah selesai']
			];
		}

		$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();

		if (!$box) {
			return [
				'status' => 'fail',
				'messages' => ['Box tidak ditemukan']
			];
		}

    	DB::beginTransaction();
    	try {
    		$trx = Transaction::where('id_transaction', $service->id_transaction)->first();
    		TransactionProductServiceLog::create([
	    		'id_transaction_product_service' => $request->id_transaction_product_service,
	    		'action' => 'Complete'
	    	]);
    		
			$service->update([
				'service_status' => 'Completed',
				'completed_at' => date('Y-m-d H:i:s')
			]);

			TransactionProduct::where('id_transaction_product', $service->id_transaction_product)
			->update([
				'transaction_product_completed_at' => date('Y-m-d H:i:s')
	    	]);

			$box->update(['outlet_box_use_status' => 0]);

			$this->completeTransaction($service->id_transaction);

            //remove hs from table not avilable
            HairstylistNotAvailable::where('id_transaction_product_service', $service['id_transaction_product_service'])->delete();

            //update stock
            app($this->trx)->bookProductServiceStock($trx, $service->id_transaction_product);

            // log rating outlet
            UserRatingLog::updateOrCreate([
                'id_user' => $trx->id_user,
                'id_transaction' => $trx->id_transaction,
                'id_outlet' => $trx->id_outlet
            ],[
                'refuse_count' => 0,
                'last_popup' => date('Y-m-d H:i:s', time() - MyHelper::setting('popup_min_interval', 'value', 900))
            ]);

            // log rating hairstylist
            UserRatingLog::updateOrCreate([
                'id_user' => $trx->id_user,
                'id_transaction' => $trx->id_transaction,
                'id_transaction_product_service' => $request->id_transaction_product_service,
                'id_user_hair_stylist' => $service->id_user_hair_stylist
            ],[
                'refuse_count' => 0,
                'last_popup' => date('Y-m-d H:i:s', time() - MyHelper::setting('popup_min_interval', 'value', 900))
            ]);

            $trx->update(['show_rate_popup' => '1']);

			DB::commit();
    	} catch (\Exception $e) {

    		\Log::error($e->getMessage());
			DB::rollback();
    		return [
				'status' => 'fail',
				'messages' => ['Gagal menyelesaikan layanan']
			];	
    	}

		return ['status' => 'success'];
    }

    public function completeTransaction($id_transaction)
    {
    	$trxProducts = TransactionProduct::where('id_transaction', $id_transaction)
    					->whereNull('transaction_product_completed_at')
    					->first();

    	if (!$trxProducts) {
    		TransactionOutletService::where('id_transaction', $id_transaction)
    		->update(['completed_at' => date('Y-m-d H:i:s')]);
    	}

    	return true;
    }

    public function paymentCashDetail(Request $request){
        $post = $request->json()->all();
        if(empty($post['order_id']) && empty($post['payment_code'])){
            return ['status' => 'fail', 'messages' => ['Order ID and Payment code can not be empty']];
        }

        $trx = Transaction::join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                ->where('transaction_receipt_number', $post['order_id'])->first();
        if(empty($trx)){
            return ['status' => 'fail', 'messages' => ['Transaction not found']];
        }

        $checkCode = TransactionPaymentCash::where('id_transaction', $trx['id_transaction'])
                    ->where('payment_code', $post['payment_code'])->first();
        if(empty($checkCode)){
            return ['status' => 'fail', 'messages' => ['The code you entered is wrong']];
        }

        $trxProduct = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                        ->leftJoin('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                        ->where('transaction_products.id_transaction', $trx['id_transaction'])
                        ->select('products.product_name', 'transaction_products.*', 'transaction_product_services.*')->get()->toArray();

        if(empty($trxProduct)){
            return ['status' => 'fail', 'messages' => ['Products not found']];
        }

        $products = [];
        foreach ($trxProduct as $p){
            if($p['type'] == 'Service'){
                $check = array_search($p['id_product'], array_column($p, 'id_product'));
                if($check !== false){
                    $products[$check]['qty'] = $products[$check]['qty'] + $p['transaction_product_qty'];
                    $products[$check]['product_subtotal'] = $products[$check]['product_subtotal'] + $p['transaction_product_subtotal'];
                    continue;
                }
            }

            $products[] = [
                'id_product' => $p['id_product'],
                'product_name' => $p['product_name'],
                'qty' => $p['transaction_product_qty'],
                'product_subtotal' => $p['transaction_product_subtotal']
            ];
        }

        $result = [
            'order_id' => $trx['transaction_receipt_number'],
            'transaction_subtotal' => $trx['transaction_subtotal'],
            'transaction_tax' => $trx['transaction_tax'],
            'transaction_grandtotal' => $trx['transaction_subtotal'],
            'transaction_date' => MyHelper::dateFormatInd($trx['transaction_date']),
            'customer_name' => $trx['customer_name'],
            'customer_email' => $trx['customer_email'],
            'currency' => 'Rp',
            'products' => $products
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function paymentCashCompleted(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['order_id'])){
            return ['status' => 'fail', 'messages' => ['Order ID can not be empty']];
        }

        $trx = Transaction::where('transaction_receipt_number', $post['order_id'])->first();
        if(empty($trx)){
            return ['status' => 'fail', 'messages' => ['Transaction not found']];
        }

        if($trx['transaction_payment_status'] == 'Completed'){
            return ['status' => 'fail', 'messages' => ['This transaction has been paid']];
        }

        $update = TransactionPaymentCash::where('id_transaction', $trx['id_transaction'])
                ->update(['cash_received_by' => $user->id_user_hair_stylist]);

        if($update){
            $update = Transaction::where('id_transaction', $trx['id_transaction'])->update(['transaction_payment_status' => 'Completed']);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function outletServiceDetail(Request $request)
    {
    	$user = $request->user();
    	$user->load('outlet.brands');

    	$outlet = [
			'id_outlet' => $user['outlet']['id_outlet'],
			'outlet_code' => $user['outlet']['outlet_code'],
			'outlet_name' => $user['outlet']['outlet_name'],
			'outlet_address' => $user['outlet']['outlet_address'],
			'outlet_latitude' => $user['outlet']['outlet_latitude'],
			'outlet_longitude' => $user['outlet']['outlet_longitude']
		];

		$brand = [
			'id_brand' => $user['outlet']['brands'][0]['id_brand'],
			'brand_code' => $user['outlet']['brands'][0]['code_brand'],
			'brand_name' => $user['outlet']['brands'][0]['name_brand'],
			'brand_logo' => $user['outlet']['brands'][0]['logo_brand'],
            'brand_logo_landscape' => $user['outlet']['brands'][0]['logo_landscape_brand']
		];

		$timeNow = date('H:i:s');
		$shift = app($this->mitra)->timeToShift($timeNow);

		$schedule = HairstylistSchedule::join(
			'hairstylist_schedule_dates', 
			'hairstylist_schedules.id_hairstylist_schedule', 
			'hairstylist_schedule_dates.id_hairstylist_schedule'
		)
 		->where('id_user_hair_stylist', $user->id_user_hair_stylist)
 		->whereDate('date', date('Y-m-d'))
 		->where('shift', $shift)
 		->first();

 		$box = [];
 		if ($schedule) {
	 		if ($schedule->id_outlet_box) {
	 			$box = OutletBox::where([
					['id_outlet', $user->id_outlet],
					['id_outlet_box', $schedule->id_outlet_box],
					['outlet_box_status', 'Active']
				])->get();
	 		} else {
				$box = OutletBox::where([
					['id_outlet', $user->id_outlet],
					['outlet_box_status', 'Active']
				])
				->whereDoesntHave('hairstylist_schedule_dates', function($q) use ($shift){
					$q->whereDate('date', date('Y-m-d'))
			 		->where('shift', $shift);
				})->get();
	 		}
 		}

		$res = [
			'outlet' => $outlet,
			'brand' => $brand,
			'box' => $box
		];
    	return MyHelper::checkGet($res);
    }
}
