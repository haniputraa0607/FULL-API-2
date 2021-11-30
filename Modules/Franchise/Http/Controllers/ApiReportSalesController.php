<?php

namespace Modules\Franchise\Http\Controllers;

use Modules\Franchise\Entities\Transaction;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use DB;

class ApiReportSalesController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function outletSummary(Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_outlet){
        	return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

    	$report = Transaction::where('transactions.id_outlet', $request->id_outlet)
    				->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
					->select(DB::raw('
						# tanggal transaksi
						Date(transactions.transaction_date) as transaction_date,

						# total transaksi
						COUNT(CASE WHEN transactions.id_transaction IS NOT NULL THEN 1 ELSE NULL END) AS total_transaction, 

						# subtotal
						SUM(CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN transactions.transaction_gross ELSE 0 END) as total_subtotal,

						# diskon
						SUM(
							CASE WHEN transactions.transaction_discount_item IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount_item) 
								WHEN transactions.transaction_discount IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount)
								ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
						) as total_discount,

						# grandtotal
						SUM(CASE WHEN transactions.transaction_grandtotal IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN transactions.transaction_grandtotal ELSE 0 END) as total_grandtotal,

						# payment complete
						COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END) as total_complete_payment,

                        #accept
                        COUNT(CASE WHEN transactions.id_transaction AND transaction_outlet_services.reject_at IS NULL THEN 1 ELSE NULL END) as total_accept
					'));

        if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $report = $report->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        }elseif (isset($post['filter_type']) && $post['filter_type'] == 'today'){
            $currentDate = date('Y-m-d');
            $report = $report->whereDate('transactions.transaction_date', $currentDate);
        }else{
            $report = $report->whereDate('transactions.transaction_date', date('Y-m-d'));
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
            'total_subtotal' => [
                'title' => 'Penjualan Kotor',
                'amount' => 'Rp. '.number_format($report['total_subtotal']??0,0,",","."),
                "tooltip" => 'Total nominal transaksi sebelum dipotong diskon',
                "show" => 1
            ],
            'total_discount' => [
                'title' => 'Total Diskon',
                'amount' => 'Rp. '.number_format($report['total_discount']??0,0,",","."),
                "tooltip" => 'Total diskon transaksi (diskon produk dan diskon bill)',
                "show" => 1
            ],
            'total_grandtotal' => [
                'title' => 'Penjualan Bersih',
                'amount' => 'Rp. '.number_format($report['total_grandtotal']??0,0,",","."),
                "tooltip" => 'Total nominal transaksi setelah dipotong diskon dan ditambah pajak',
                "show" => 1
            ],
            'total_complete_payment' => [
                'title' => 'Pembayaran Sukses',
                'amount' => number_format($report['total_complete_payment']??0,0,",","."),
                "tooltip" => 'jumlah transaksi dengan status pembayaran sukses (mengabaikan status reject order)',
                "show" => 1
            ],
    		'total_transaction' => [
                'title' => 'Total Order',
                'amount' => number_format($report['total_transaction']??0,0,",","."),
                "tooltip" => 'Jumlah semua transaksi',
                "show" => 1
            ],
            'total_accept' => [
                'title' => 'Order Diterima',
                'amount' => number_format($report['total_accept']??0,0,",","."),
                "tooltip" => 'Jumlah transaksi yang diterima oleh outlet',
                "show" => 1
            ]
    	];

        return MyHelper::checkGet($result);
    }

 	public function outletListDaily(Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_outlet){
        	return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

    	$list = Transaction::where('transactions.id_outlet', $request->id_outlet)
    				->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
    				// ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
    				->where('transactions.transaction_payment_status', 'Completed')
                    ->where('transactions.transaction_from', 'outlet-service')
					// ->whereNull('reject_at')
					->select(DB::raw('
						Date(transactions.transaction_date) as transaction_date,
                        COUNT(CASE WHEN transactions.transaction_payment_status = "Completed" THEN 1 ELSE NULL END) as total_complete_payment,
                        COUNT(CASE WHEN transactions.id_transaction AND transaction_outlet_services.reject_at IS NULL THEN 1 ELSE NULL END) AS total_transaction,
                        SUM(CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN transactions.transaction_gross ELSE 0 END) as total_subtotal,
                        SUM(
							CASE WHEN transactions.transaction_discount_item IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount_item) 
								WHEN transactions.transaction_discount IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount)
								ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_delivery IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount_delivery) ELSE 0 END
							+ CASE WHEN transactions.transaction_discount_bill IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN ABS(transactions.transaction_discount_bill) ELSE 0 END
						) as total_discount,
                        SUM(CASE WHEN transactions.transaction_grandtotal IS NOT NULL AND transaction_outlet_services.reject_at IS NULL THEN transactions.transaction_grandtotal ELSE 0 END) as total_grandtotal,
                        COUNT(CASE WHEN Date(transactions.transaction_date) = Date(transaction_outlet_services.pickup_at) AND Date(transaction_outlet_services.pickup_at) = Date(transaction_outlet_services.completed_at) AND transaction_outlet_services.reject_at IS NULL THEN 1 ELSE NULL END) as transaction_in_date,
                        COUNT(CASE WHEN Date(transactions.transaction_date) = Date(transaction_outlet_services.pickup_at) AND Date(transaction_outlet_services.pickup_at) = Date(transaction_outlet_services.completed_at) AND transaction_outlet_services.reject_at IS NULL THEN NULL ELSE 1 END) as transaction_out_date
					'))
    				->groupBy(DB::raw('Date(transactions.transaction_date)'));

        if(isset($post['filter_type']) && $post['filter_type'] == 'range_date'){
            $dateStart = date('Y-m-d', strtotime($post['date_start']));
            $dateEnd = date('Y-m-d', strtotime($post['date_end']));
            $list = $list->whereDate('transactions.transaction_date', '>=', $dateStart)->whereDate('transactions.transaction_date', '<=', $dateEnd);
        }elseif (isset($post['filter_type']) && $post['filter_type'] == 'today'){
            $currentDate = date('Y-m-d');
            $list = $list->whereDate('transactions.transaction_date', $currentDate);
        }else{
            $list = $list->whereDate('transactions.transaction_date', date('Y-m-d'));
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
