<?php

namespace Modules\PortalPartner\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Lib\MyHelper;
use App\Jobs\SendEmailUserFranchiseJob;
use Illuminate\Support\Facades\Auth;
use Modules\BusinessDevelopment\Entities\Location;
use App\Http\Models\Transaction;
use App\Http\Models\Product;
use DB;
use DataTables;
use Modules\PortalPartner\Entities\OutletPortalReport;

class ApiDashboardController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index(Request $request) {
        if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) && isset($request->setfilter) && !empty($request->setfilter)){
            $transaction = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $request->dari)->whereDate('transactions.transaction_date', '<=', $request->sampai)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                       ->join('products','products.id_product','transaction_products.id_product')
                      ->groupby('transaction_products.id_product')
                       ->select('products.product_name as network','products.product_code',
                                 DB::raw('
                                        count(
                                        transaction_products.id_product
                                        ) as MAU
                                    '));
                            $transaction = $transaction->orderby('MAU','DESC');                    
                        if($request->setfilter == 3){
                            $transaction = $transaction->limit(3);
                        }elseif($request->setfilter == 5){
                            $transaction = $transaction->limit(5);
                        }else{
                            $transaction = $transaction->limit(10);
                        }                
                        $transaction = $transaction->get()->toArray();
            $array = array();
            $nama = array();
            foreach ($transaction as $value) {
                $s = 1;
                $n = 8;
                for ($x = 0; $x <= $s; $x++) {
                   if(strlen($value['network'])>$n){
                    $text = substr($value['network'],0,$n);
                    }else{
                        $text = $value['network'];
                    }
                    
                    if(!in_array($text, $nama)){
                        array_push($nama,$text);
                        $array[] = array(
                            'network'=>$text,
                            'MAU'=>$value['MAU'],
                            'label'=>$value['network'],
                        );
                         break;
                    }else{
                        $s++;
                        $n++;
                    }
                }
            }
       return response()->json(['status' => 'success', 'result' => $array]); 
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
    public function status(Request $request) {
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
            $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                        ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                        ->whereBetween('transaction_date',[$request->dari,$request->sampai])
                        ->select(DB::raw('DATE_FORMAT(transaction_date, "%Y-%m-%d") as date'),DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_payment_status = "Completed" AND transaction_outlet_services.reject_at IS NULL THEN 1 ELSE 0
                                       END
                                        ) as completed
                                    '),DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_payment_status = "Paid" THEN 1 ELSE 0
                                       END
                                        ) as paid
                                    '),DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_payment_status = "Cancelled" THEN 1 ELSE 0
                                       END
                                        ) as cancelled
                                    '),DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_payment_status = "Pending" THEN 1 ELSE 0
                                       END
                                        ) as pending
                                    '),DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_payment_status = "Completed" AND transaction_outlet_services.reject_at IS NOT NULL THEN 1 ELSE 0
                                       END
                                        ) as rejected
                                    ')
                               )
                       ->groupby('date')
                       ->orderby('transactions.transaction_date','asc')
                       ->get();
       return response()->json(['status' => 'success', 'result' => $transaction]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
    public function daily(Request $request) {
       if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
            $data = OutletPortalReport::where('id_outlet',$request->id_outlet)
                    ->whereDate('date', '>=', $request->dari)
                    ->whereDate('date', '<=', $request->sampai)
                    ->orderby('date','DESC')
                    ->get();      
            
             return response()->json(['status' => 'success', 'result' => $data]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
    public function growth(Request $request) {
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
               $before = [];
               $now = [];
               $lastyear = [];
               $s = 1;
               $array= array();
               for ($i = 0; $i < $s; $i++) {
                   $dates = date('M Y',strtotime('+'.$i.'month'.$request->dari));
                   if($dates != date('M Y',strtotime($request->sampai))){
                       $s++;
                   }
                   $date_now_awal = date('Y-m-01',strtotime('+'.$i.'month'.$request->dari));
                   $date_now_akhir = date('Y-m-t',strtotime('+'.$i.'month'.$request->dari));
                   $date_before_awal = date('Y-m-01',strtotime('+'.$i.'month'.'- 1 month'.$request->dari));
                   $date_before_akhir = date('Y-m-t',strtotime('+'.$i.'month'.'- 1 month'.$request->dari));
                   $date_lastyear_awal = date('Y-m-01',strtotime('+'.$i.'month'.'- 1 year'.$request->dari));
		   $date_lastyear_akhir = date('Y-m-t',strtotime('+'.$i.'month'.'- 1 year'.$request->dari));
                   
//                   if($i==0){
//                   $date_now_awal = date('Y-m-d',strtotime('+'.$i.'month'.$request->dari));
//                   $date_before_awal = date('Y-m-d',strtotime('+'.$i.'month'.'- 1 month'.$request->dari));
//                   $date_lastyear_awal = date('Y-m-d',strtotime('+'.$i.'month'.'- 1 year'.$request->dari));
//		   }
//                   if($dates == date('M Y',strtotime($request->sampai))){
//                       $date_now_akhir = date('Y-m-d',strtotime($request->sampai));
//                       $date_before_akhir = date('Y-m-d',strtotime('- 1 month'.$request->sampai));
//                       $date_lastyear_akhir = date('Y-m-d',strtotime('- 1 year'.$request->sampai));
//                   }
                   $n_now = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $date_now_awal)->whereDate('transactions.transaction_date', '<=', $date_now_akhir)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->where('transactions.reject_at', NULL)
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->select(
                        DB::raw('
                                 SUM(
                                 CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                         ELSE 0 END
                                 ) as revenue
                                 ')
                       )
                       ->first();
                   $n_before = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $date_before_awal)->whereDate('transactions.transaction_date', '<=', $date_before_akhir)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                        ->select(
                        DB::raw('
                                 SUM(
                                 CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                         ELSE 0 END
                                 ) as revenue
                                 ')
                       )
                       ->first();
                   $n_lastyear = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $date_lastyear_awal)->whereDate('transactions.transaction_date', '<=', $date_lastyear_akhir)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->select(
                        DB::raw('
                                 SUM(
                                 CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                         ELSE 0 END
                                 ) as revenue
                                 ')
                       )
                       ->first();
                    if($n_now['revenue']??0 != 0 || $n_before['revenue']??0 != 0 ||$n_lastyear['revenue']??0 != 0){
                    array_push($array,array(
                       'date'=>$dates,
                       'now'=>floor($n_now['revenue']??0),
                       'before'=>floor($n_before['revenue']??0),
                       'lastyear'=>floor($n_lastyear['revenue']??0),
                   ));
                   }

                   
               }
            
       return response()->json(['status' => 'success', 'result' => $array]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
    public function monthly(Request $request) {
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
              $transaction = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $request->dari)->whereDate('transactions.transaction_date', '<=', $request->sampai)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->select(DB::raw('DATE_FORMAT(transactions.transaction_date, "%M %y") as date'),DB::raw('
                                        count(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL THEN 1 ELSE 0
                                       END
                                        ) as total_order
                                    '),
                                 DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                        ELSE 0 END
                                        ) as revenue
                                        '),
                               )
                       ->groupby('date')
                       ->orderby('transactions.transaction_date','asc')
                       ->get();
               $revenue = array();
               foreach ($transaction as $value) {
                   $value['revenue'] = floor($value['revenue']);
                   array_push($revenue,$value);
               }
               $average = array();
               foreach ($transaction as $value) {
                   $value['average'] = $value['revenue']/$value['total_order']??0;
                   $value['average'] = floor($value['average']);
                   array_push($average,$value);
               }
               $total_order = array();
               foreach ($transaction as $value) {
                  $vas = array(
                      'date'=>$value['date'],
                      'total_order'=>$value['total_order'],
                  );
                   array_push($total_order,$vas);
               }
               $array = array(
                   'revenue'=>$revenue,
                   'average'=>$average,
                   'total_order'=>$total_order,
               );
       return response()->json(['status' => 'success', 'result' => $array]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
    public function transaction(Request $request) {
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
            $transaction = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $request->dari)->whereDate('transactions.transaction_date', '<=', $request->sampai)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                       ->select(DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y-%m-%d") as date'),DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_payment_status = "Completed" THEN transaction_grandtotal ELSE 0
                                       END
                                        ) as jumlah
                                    '),
                               )
                       ->groupby('date')
                       ->orderby('transactions.transaction_date','asc')
                       ->get();
       return response()->json(['status' => 'success', 'result' => $transaction]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
} 
 