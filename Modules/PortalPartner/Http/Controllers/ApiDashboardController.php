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
                       ->limit(10)
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
                       ->get();
       return response()->json(['status' => 'success', 'result' => $transaction]);  
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
                       ->get();
       return response()->json(['status' => 'success', 'result' => $transaction]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
} 
 