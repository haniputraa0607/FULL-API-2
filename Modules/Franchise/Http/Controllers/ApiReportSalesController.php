<?php

namespace Modules\Franchise\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\DailyReportTrx;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Franchise\Entities\UserFranchise;
use App\Lib\MyHelper;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Franchise\Http\Requests\users_create;
use Modules\Report\Entities\DailyReportPayment;
use DB;
use DateTime;

class ApiReportSalesController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function summary(Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_outlet){
        	return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

    	$report = Transaction::where('transactions.id_outlet', $request->id_outlet)
    				->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
    				->where('transactions.transaction_payment_status', 'Completed')
					// ->whereNull('reject_at')
					->select(DB::raw('
						Date(transactions.transaction_date) as transaction_date,
						COUNT(CASE WHEN transactions.id_transaction AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) AS total_transaction, 
						COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_pickup,
						COUNT(CASE WHEN transaction_pickups.pickup_by = "GO-SEND" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_delivery,
						SUM(CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_gross ELSE 0 END) as total_subtotal,
						SUM(
							CASE WHEN transactions.transaction_discount_item IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_item) 
								WHEN transactions.transaction_discount IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount)
								ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
						) as total_discount,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_shipment_go_send + transactions.transaction_shipment ELSE 0 END) as total_delivery,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_grandtotal ELSE 0 END) as total_grandtotal,
						COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) as total_accept,
						COUNT(CASE WHEN transaction_pickups.reject_at IS NOT NULL THEN 1 ELSE NULL END) as total_reject,
						FLOOR(
							(
								COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) 
								/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as acceptance_rate,
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
								 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_response,
						COUNT(
							CASE WHEN transaction_pickups.reject_reason = "auto reject order by system" THEN 1
							ELSE NULL END
						) as total_auto_reject,
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NULL AND transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_manual_reject,
						FLOOR(
							(
								COUNT(
									CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
										 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
									ELSE NULL END
								)/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as response_rate
					'));

        if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $report = $report->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        }elseif (isset($post['filter_type']) && $post['filter_type'] == 'today'){
            $currentDate = date('Y-m-d');
            $report = $report->whereDate('transactions.transaction_date', $currentDate);
        }

        $report = $report->first();

        if (!$report) {
        	return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

        /*$report['acceptance_rate'] = 0;
    	if ($report['total_accept']) {
    		$report['acceptance_rate'] = floor(( $report['total_accept'] / ($report['total_accept'] + $report['total_reject']) ) * 100);
    	}

    	if ($report['total_discount']) {
    		$report['total_discount'] = abs($report['total_discount']);
    	}*/

    	$result = [
    		[
                // 'title' => 'Total Transaction',
                'title' => 'Jumlah Transaksi',
                'amount' => number_format($report['total_transaction']??0,0,",",".")
            ],
            [
                // 'title' => 'Total Transaction Pickup',
                'title' => 'Jumlah Pickup',
                'amount' => number_format($report['total_transaction_pickup']??0,0,",",".")
            ],
            [
                // 'title' => 'Total Transaction Delivery',
                'title' => 'Jumlah Delivery',
                'amount' => number_format($report['total_transaction_delivery']??0,0,",",".")
            ],
            [
                // 'title' => 'Total Subtotal',
                'title' => 'Sub Total',
                'amount' => 'Rp. '.number_format($report['total_subtotal']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Discount',
                'title' => 'Diskon',
                'amount' => 'Rp. '.number_format($report['total_discount']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Delivery',
                'title' => 'Delivery',
                'amount' => 'Rp. '.number_format($report['total_delivery']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Grandtotal',
                'title' => 'Grand Total',
                'amount' => 'Rp. '.number_format($report['total_grandtotal']??0,2,",",".")
            ],
            [
                // 'title' => 'Total Accept',
                'title' => 'Jumlah Accept',
                'amount' => number_format($report['total_accept']??0,0,",",".")
            ],
            [
                // 'title' => 'Acceptance Rate',
                'title' => 'Acceptance Rate Order',
                'amount' => number_format($report['acceptance_rate']??0,0,",",".")."%"
            ],
            [
                // 'title' => 'Acceptance Rate',
                'title' => 'Jumlah Response Order',
                'amount' => number_format($report['total_response']??0,0,",",".")
            ],
            [
                // 'title' => 'Acceptance Rate',
                'title' => 'Response Rate Order',
                'amount' => number_format($report['response_rate']??0,0,",",".")."%"
            ],
            [
                // 'title' => 'Total Reject',
                'title' => 'Jumlah Reject',
                'amount' => number_format($report['total_reject']??0,0,",",".")
            ],
            [
                // 'title' => 'Acceptance Rate',
                'title' => 'Jumlah Auto Reject',
                'amount' => number_format($report['total_auto_reject']??0,0,",",".")
            ],
            [
                // 'title' => 'Acceptance Rate',
                'title' => 'Jumlah Manual Reject',
                'amount' => number_format($report['total_manual_reject']??0,0,",",".")
            ],
    	];

        return MyHelper::checkGet($result);
    }

 	public function listDaily(Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_outlet){
        	return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

    	$list = Transaction::where('transactions.id_outlet', $request->id_outlet)
    				->join('transaction_pickups', 'transaction_pickups.id_transaction', 'transactions.id_transaction')
    				->where('transactions.transaction_payment_status', 'Completed')
					// ->whereNull('reject_at')
					->select(DB::raw('
						Date(transactions.transaction_date) as transaction_date,
						COUNT(CASE WHEN transactions.id_transaction AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) AS total_transaction, 
						COUNT(CASE WHEN transaction_pickups.pickup_by = "Customer" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_pickup,
						COUNT(CASE WHEN transaction_pickups.pickup_by = "GO-SEND" AND transaction_pickups.reject_at IS NULL THEN 1 ELSE NULL END) as total_transaction_delivery,
						SUM(CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN transactions.transaction_gross ELSE 0 END) as total_subtotal,
						SUM(
							CASE WHEN transactions.transaction_discount_item IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_item) 
								WHEN transactions.transaction_discount IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount)
								ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_pickups.reject_at IS NULL THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
						) as total_discount,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_shipment_go_send + transactions.transaction_shipment ELSE 0 END) as total_delivery,
						SUM(CASE WHEN transaction_pickups.reject_at IS NULL THEN transactions.transaction_grandtotal ELSE 0 END) as total_grandtotal,
						COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) as total_accept,
						COUNT(CASE WHEN transaction_pickups.reject_at IS NOT NULL THEN 1 ELSE NULL END) as total_reject,
						FLOOR(
							(
								COUNT(CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1 ELSE NULL END) 
								/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as acceptance_rate,
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
								 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_response,
						COUNT(
							CASE WHEN transaction_pickups.reject_reason = "auto reject order by system" THEN 1
							ELSE NULL END
						) as total_auto_reject,
						COUNT(
							CASE WHEN transaction_pickups.receive_at IS NULL AND transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
							ELSE NULL END
						) as total_manual_reject,
						FLOOR(
							(
								COUNT(
									CASE WHEN transaction_pickups.receive_at IS NOT NULL THEN 1
										 WHEN transaction_pickups.reject_reason NOT LIKE "auto reject order by system%" THEN 1
									ELSE NULL END
								)/ COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END)
							)
							* 100
						) as response_rate
					'))
    				->groupBy(DB::raw('Date(transactions.transaction_date)'));

        if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        }elseif (isset($post['filter_type']) && $post['filter_type'] == 'today'){
            $currentDate = date('Y-m-d');
            $list = $list->whereDate('transactions.transaction_date', $currentDate);
        }

    	$order = $post['order']??'transaction_date';
        $orderType = $post['order_type']??'desc';
        $list = $list->orderBy($order, $orderType);

        $sub = $list;

        $query = DB::table(DB::raw('('.$sub->toSql().') as report_sales'))
		        ->mergeBindings($sub->getQuery());

		$this->filterSalesReport($query, $post);

        if($post['export'] == 1){
            $query = $query->get();
        }else{
            $query = $query->paginate(30);
        }

        if (!$query) {
        	return response()->json(['status' => 'fail', 'messages' => ['Empty']]);
        }

        $result = $query->toArray();

        /*$data = $result['data'] ?? $result;
        foreach ($data as $key => &$value) {
      //   	$value['acceptance_rate'] = 0;
	    	// if ($value['total_accept']) {
	    	// 	$value['acceptance_rate'] = floor(( $value['total_accept'] / ($value['total_accept'] + $value['total_reject']) ) * 100);
	    	// }

	    	// if ($value['total_discount']) {
	    	// 	$value['total_discount'] = abs($value['total_discount']);
	    	// }
        }

        if($post['export'] != 1){
        	$result['data'] = $data;
        	$data = $result;
        }

        return MyHelper::checkGet($data);*/
        return MyHelper::checkGet($result);
    }

    function filterSalesReport($query, $filter)
    {
    	if (isset($filter['rule'])) {
            foreach ($filter['rule'] as $key => $con) {
            	if(is_object($con)){
                    $con = (array)$con;
                }
                if (isset($con['subject'])) {
                    if ($con['subject'] != 'all_transaction') {
                    	$var = $con['subject'];

                        if ($filter['operator'] == 'and') {
                            $query = $query->where($var, $con['operator'], $con['parameter']);
                        } else {
                            $query = $query->orWhere($var, $con['operator'], $con['parameter']);
                        }
                    }
                }
            }
        }

        return $query;
    }
}
