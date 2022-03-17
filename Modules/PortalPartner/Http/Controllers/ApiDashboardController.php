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

class ApiDashboardController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function index(Request $request) {
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
        $transaction = Product::join('transaction_products','transaction_products.id_product','products.id_product')
                       ->join('transactions','transactions.id_transaction','transaction_products.id_transaction')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where(array('transactions.id_outlet'=>$request->id_outlet,'transactions.transaction_payment_status'=>"Completed"))
                       ->whereBetween('transactions.completed_at',[$request->dari,$request->sampai])
                       ->groupby('products.id_product')
                       ->select('products.id_product','products.product_code','products.product_name',
                                 DB::raw('
                                        count(
                                        transaction_products.id_product
                                        ) as jml
                                    '))
                       ->orderby('jml','DESC')
                       ->get();
       return response()->json(['status' => 'success', 'result' => $transaction]);  
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
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
            $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                       ->whereBetween('transactions.transaction_date',[$request->dari,$request->sampai])
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                       ->select(DB::raw('DATE_FORMAT(transactions.transaction_date, "%d-%m-%Y") as date'),DB::raw('
                                        count(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN 1 ELSE 0
                                       END
                                        ) as jumlah
                                    '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN transaction_gross ELSE 0
                                       END
                                        ) as revenue
                                        '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN transaction_discount ELSE 0
                                       END
                                        ) as diskon
                                        '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN transaction_tax ELSE 0
                                       END
                                        ) as tax
                                        '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN transaction_grandtotal ELSE 0
                                       END
                                        ) as net_sales
                                        '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN mdr ELSE 0
                                       END
                                        ) as mdr
                                        '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN transaction_grandtotal+mdr ELSE 0
                                       END
                                        ) as net_sales_mdr
                                        '),
                               DB::raw('
                                        count(DISTINCT 
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN transaction_product_services.id_user_hair_stylist ELSE 0
                                       END
                                        ) as count_hs
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
    public function growth(Request $request) {
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
               $before = [];
               $now = [];
               $lastyear = [];
               $s = 1;
               $array= array();
               for ($i = 0; $i < $s; $i++) {
                   $dates = date('d',strtotime('+'.$i.'day'.$request->dari));
                   $date_now = date('Y-m-d',strtotime('+'.$i.'day'.$request->dari));
                   $date_before = date('Y-m-d',strtotime('+'.$i.'day - 1 month'.$request->dari));
                   $date_lastyear = date('Y-m-d',strtotime('+'.$i.'day - 1 year'.$request->dari));
                   $n_now = $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                       ->wheredate('transactions.transaction_date',$date_now)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                       ->get();
                   $angka_now = 0;
                   foreach ($n_now as $value) {
                       $angka_now += $value['transaction_gross'];
                   }
                   $n_before = $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                       ->wheredate('transactions.transaction_date',$date_before)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                       ->get();
                   $angka_before = 0;
                   foreach ($n_before as $value) {
                       $angka_before += $value['transaction_gross'];
                   }
                   $n_lastyear = $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                       ->wheredate('transactions.transaction_date',$date_lastyear)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                       ->get();
                   $angka_lastyear = 0;
                   foreach ($n_lastyear as $value) {
                       $angka_lastyear += $value['transaction_gross'];
                   }
                   array_push($array,array(
                       'date'=>$dates,
                       'now'=>$angka_now,
                       'before'=>$angka_before,
                       'lastyear'=>$angka_lastyear,
                   ));
                   if($date_now != $request->sampai){
                       $s++;
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
               $before = [];
               $now = [];
               $lastyear = [];
               $i = 0;
               $array= array();
               for ($s = 4; $s >= $i; $s--) {
                   $bulan = date('M',strtotime('-'.$s.'month'.$request->dari));
                   $awal = date('Y-m-01',strtotime('-'.$s.'month'.$request->dari));
                   $akhir = date('Y-m-t',strtotime('-'.$s.'month'.$request->dari));
                   $n_now = $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                       ->whereBetween('transactions.transaction_date',[$awal,$akhir])
                       ->where('transaction_outlet_services.reject_at', NULL)
                           ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                       ->get();
                   $angka_now = 0;
                   foreach ($n_now as $value) {
                       $angka_now += $value['transaction_gross'];
                   }
                   $n_before = $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                       ->whereBetween('transactions.transaction_date',[$awal,$akhir])
                       ->where('transaction_outlet_services.reject_at', NULL)
                           ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                       ->count();
                   $angka_before = $n_before;
                   if($angka_before == 0 && $angka_now==0){
                       $average = 0;
                   }else{
                       $average = $angka_now/$angka_before;
                   }
                   array_push($array,array(
                       'month'=>$bulan,
                       'revenue'=>$angka_now,
                       'order'=>$angka_before,
                       'average'=>$average,
                   ));
               }
            
       return response()->json(['status' => 'success', 'result' => $array]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
    public function transaction(Request $request) {
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari) && !empty($request->dari) && isset($request->sampai) && !empty($request->sampai) ){
            $transaction = Transaction::where(array('id_outlet'=>$request->id_outlet))
                       ->whereBetween('transactions.transaction_date',[$request->dari,$request->sampai])
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->select(DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y-%m-%d") as date'),DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_payment_status = "Completed" THEN transaction_grandtotal ELSE 0
                                       END
                                        ) as jumlah
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
} 
 