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
                       ->where(array('transactions.id_outlet'=>$request->id_outlet,'transactions.transaction_payment_status'=>"Completed"))
                       ->whereBetween('transactions.completed_at',[$request->dari,$request->sampai])
                       ->groupby('products.id_product')
                       ->select('products.id_product','products.product_name',
                                 DB::raw('
                                        count(
                                        transaction_products.id_product = 1
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
} 
 