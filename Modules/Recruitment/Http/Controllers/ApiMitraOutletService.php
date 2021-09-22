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
				->leftJoin('products', 'transaction_products.id_product', 'products.id_product')
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
				'trasaction_payment_type' => $val['trasaction_payment_type'],
				'product_name' => $val['product_name'],
				'timer_text' => $timerText,
				'button_text' => 'Layani Sekarang',
				'disable' => $disable
			];
		}

		$res = $queue;
		$res['data'] = $resData;
		return MyHelper::checkGet($res);
    }
}
