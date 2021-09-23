<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;

use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;

use Modules\Transaction\Entities\TransactionOutletService;
use Modules\Transaction\Entities\TransactionProductService;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;

use Modules\Outlet\Entities\OutletBox;

use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiMitraOutletService extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
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
    			->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    			->where('transaction_payment_status' ,'Completed')
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

			$resData[] = [
				'id_transaction_product_service' => $val['id_transaction_product_service'],
				'order_id' => $val['order_id'] ?? null,
				'customer_name' => $val['customer_name'],
				'schedule_date' => $val['schedule_date'],
				'schedule_time' => $val['schedule_time'],
				'service_status' => $val['service_status'],
				'trasaction_payment_type' => $val['trasaction_payment_type'],
				'product_name' => $val['product_name'],
				'timer_text' => $timerText,
				'button_text' => 'Layani Sekarang',
				'disable' => $disable,
				'id_outlet_box' => $schedule->id_outlet_box ?? null
			];
		}

		$res = $queue;
		$res['data'] = $resData;
		return MyHelper::checkGet($res);
    }

    public function customerQueueDetail(Request $request)
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
    			->where('id_user_hair_stylist', $user->id_user_hair_stylist)
    			->where('id_transaction_product_service', $request->id_transaction_product_service)
    			->where('transaction_payment_status' ,'Completed')
				->first()
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

		$res = [
			'id_transaction_product_service' => $queue['id_transaction_product_service'],
			'order_id' => $queue['order_id'] ?? null,
			'customer_name' => $queue['customer_name'],
			'schedule_date' => $queue['schedule_date'],
			'schedule_time' => $queue['schedule_time'],
			'service_status' => $queue['service_status'],
			'trasaction_payment_type' => $queue['trasaction_payment_type'],
			'product_name' => $queue['product_name'],
			'timer_text' => $timerText,
			'button_text' => 'Layani Sekarang',
			'disable' => $disable,
			'id_outlet_box' => $schedule->id_outlet_box ?? null
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
				'messages' => ['Transaction Service not found']
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
				'messages' => ['Schedule not found']
			];
		}

 		if (isset($schedule->id_outlet_box) && $schedule->id_outlet_box != $request->id_outlet_box) {
 			return [
				'status' => 'fail',
				'messages' => ['Please use the same box']
			];	
 		}


		$box = OutletBox::where('id_outlet_box', $request->id_outlet_box)->first();

		if (!$box) {
			return [
				'status' => 'fail',
				'messages' => ['Box not found']
			];
		}

		if ($box->outlet_box_status != 'Active') {
			return [
				'status' => 'fail',
				'messages' => ['Box is not active']
			];
		}

		if ($box->outlet_box_use_status != 0 && $schedule->id_outlet_box != $request->id_outlet_box) {
			return [
				'status' => 'fail',
				'messages' => ['Box is used']
			];
		}

    	DB::beginTransaction();
    	try {
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
				'messages' => ['Failed to start service']
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
				'messages' => ['Transaction Service not found']
			];
		}

		$box = OutletBox::where('id_outlet_box', $service->id_outlet_box)->first();

		if (!$box) {
			return [
				'status' => 'fail',
				'messages' => ['Box not found']
			];
		}

    	DB::beginTransaction();
    	try {
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
				'messages' => ['Failed to stop service']
			];	
    	}

		return ['status' => 'success'];
    }
}
