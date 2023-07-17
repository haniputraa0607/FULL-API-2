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
    	$report = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $request->dari)->whereDate('transactions.transaction_date', '<=', $request->sampai)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       		->select(DB::raw('
						# tanggal transaksi
						Date(transactions.transaction_date) as transaction_date,

						# total transaksi
						COUNT( CASE WHEN transactions.id_transaction IS NULL THEN 1 ELSE 0 END) AS total_transaction, 
                                                
                                                # tax
						SUM(
							CASE WHEN transactions.transaction_tax IS NOT NULL AND transactions.reject_at IS NULL THEN transactions.transaction_tax
								ELSE 0 END
							) as total_tax,
						# diskon
						 SUM(
                                                    CASE WHEN
                                                     transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN abs(transaction_discount) ELSE 0
                                                     END
                                                  ) as total_discount,
                                                
                                                #mdr 
                                                SUM(
							CASE WHEN transactions.transaction_tax IS NOT NULL AND transactions.reject_at IS NULL  THEN transactions.mdr
								ELSE 0 END
							) as total_mdr,
                                                        
                                                #refund product
                                                sum(
                                                CASE WHEN
                                                transaction_products.reject_at IS NULL
                                                THEN transaction_products.transaction_variant_subtotal ELSE 0
                                                END
                                                 ) as refund_product,
                                                #revenue
                                                SUM(
                                                CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                                        ELSE 0 END
                                                ) as total_revenue
					'));

         $report = $report->first();
        $total_net_sales = $report['total_revenue'] - ($report['refund_product']+$report['total_discount']+$report['total_tax']);
      
    	$result = [
            'total_transaction' => [
                'title' => 'Total Order Completed',
                'amount' => number_format($report['total_transaction']??0,0,",","."),
                "tooltip" => 'Jumlah semua transaksi',
                "show" => 1
            ],
            'total_revenue' => [
                'title' => 'Total Revenue',
                'amount' => 'Rp. '.number_format($report['total_revenue']??0,0,",","."),
                "tooltip" => 'Total pendapatan',
                "show" => 1
            ],
            'total_diskon' => [
                'title' => 'Total Diskon Given',
                'amount' => 'Rp. '.number_format($report['total_discount']??0,0,",","."),
                "tooltip" => 'Total diskon yang dikenakan pada transaksi',
                "show" => 1
            ],
             'total_tax' => [
                'title' => 'Total Tax Charged',
                'amount' =>'Rp. '. number_format($report['total_tax']??0,0,",","."),
                "tooltip" => 'Total pajak yang dikenakan pada transaksi',
                "show" => 1
            ],
            'total_mdr_paid' => [
                'title' => 'Total MDR Paid',
                'amount' => 'Rp. '.number_format($report['total_mdr']??0,0,",","."),
                "tooltip" => 'Total nominal MDR transaksi',
                "show" => 1
            ],
            'total_net_sales' => [
                'title' => 'Total Net Sales',
                'amount' => 'Rp. '.number_format($total_net_sales??0,0,",","."),
                "tooltip" => 'Total pendapatan bersih dari trasaksi',
                "show" => 1
            ],
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
