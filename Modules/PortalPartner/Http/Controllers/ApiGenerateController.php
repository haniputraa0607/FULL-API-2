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
use Modules\PortalPartner\Entities\OutletReport;
use Modules\PortalPartner\Entities\OutletPortalReport;
use App\Jobs\OutletJob;
use Modules\PortalPartner\Entities\OutletReportJob;
use DataTables;
use Modules\PortalPartner\Entities\LogOutletPortal;
use Modules\PortalPartner\Entities\OutletReportQueueJob;
use App\Jobs\OutletGenerateJob;

class ApiGenerateController extends Controller
{
    public function create(Request $request) {
        $data = OutletReportQueueJob::create([
            'id_outlet'=>$request->id_outlet,
            'start_date'=>date('Y-m-d', strtotime($request->start_date)),
            'end_date'=>date('Y-m-d', strtotime($request->end_date)),
        ]);
        if($data){
            $data = OutletGenerateJob::dispatch($data)->allOnConnection('database');
        }
        return MyHelper::checkGet($data);
    } 
    public function list(Request $request) {
        $post = $request->json()->all();
        $data = OutletReportQueueJob::where('id_outlet',$request->id_outlet??0)
                ->orderBy('status','ASC')
                ->get();
        return MyHelper::checkGet($data);
    }
    public function daily($datas) {
        $data = array(
                'id_outlet'=> $datas['id_outlet'],
                'dari'=> $datas['start_date'],
                'sampai'=> $datas['end_date'],
            );
        $this->dailyData($data);
        return $data;
    } 
    public function dailyData($request) {
//        DB::beginTransaction();
        try {
           $array = array();
           $transaction = Transaction::where(array('transactions.id_outlet'=>$request['id_outlet']))
                       ->whereDate('transactions.transaction_date', '>=', $request['dari'])->whereDate('transactions.transaction_date', '<=', $request['sampai'])
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->select(DB::raw('DATE_FORMAT(transactions.transaction_date, "%Y-%m-%d") as date'),DB::raw('
                                        count(
                                        CASE WHEN transactions.id_transaction IS NULL THEN 1 ELSE 0 END
                                        ) as jumlah
                                    '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                       END
                                        ) as revenue
                                        '),
                               DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN transaction_grandtotal ELSE 0
                                       END
                                        ) as grand_total
                                        '),
                               DB::raw('
                                      SUM(
                                          CASE WHEN
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN abs(transaction_discount) ELSE 0
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
                                       transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" THEN mdr ELSE 0
                                       END
                                        ) as mdr
                                        ')
                               )
                       ->groupby('date')
                       ->get();
            foreach ($transaction as $value) {
                 $hs= Transaction::where(array('transactions.id_outlet'=>$request['id_outlet']))
                       ->whereDate('transactions.transaction_date', '>=', $value['date'])->whereDate('transactions.transaction_date', '<=', $value['date'])
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->whereNotNull('transaction_product_services.id_user_hair_stylist')
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                        ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                       ->select('transaction_product_services.id_user_hair_stylist')
                       ->distinct()
                       ->get();
                $refund = Transaction::where(array('transactions.id_outlet'=>$request['id_outlet']))
                       ->whereDate('transactions.transaction_date', '>=', $value['date'])->whereDate('transactions.transaction_date', '<=', $value['date'])
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_product_services', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                       ->select(DB::raw('
                                        sum(
                                       CASE WHEN
                                       transaction_products.reject_at IS NULL
                                       THEN transaction_products.transaction_variant_subtotal ELSE 0
                                       END
                                        ) as refund_product
                                        '))
                       ->first();
                $qty= Transaction::where(array('transactions.id_outlet'=>$request['id_outlet']))
                       ->whereDate('transactions.transaction_date', '>=', $value['date'])->whereDate('transactions.transaction_date', '<=', $value['date'])
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
                       ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                       ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                       ->select('transaction_product_qty')
                        ->sum('transaction_product_qty');
                $value['net_sales'] = $value['revenue'] - ($refund['refund_product']+$value['diskon']+$value['tax']);
                $value['net_sales_mdr'] = $value['net_sales'] - $value['mdr'];
                $value['count_hs'] = count($hs);
		$value['refund_product'] = $refund['refund_product'];
                $outletReport = OutletPortalReport::updateOrCreate([
                        'id_outlet' => $request['id_outlet'],
                        'date' => $value['date']
                    ],
                    [
                        'jumlah' => $value['jumlah'],
                        'revenue' => $value['revenue'],
                        'grand_total' => $value['grand_total'],
                        'diskon' => $value['diskon'],
                        'tax' => $value['tax'],
                        'mdr' => $value['mdr'],
                        'net_sales' => $value['net_sales'],
                        'net_sales_mdr' => $value['net_sales_mdr'],
                        'count_hs' => $value['count_hs'],
                        'refund_product' => $value['refund_product'],
                        'qty'=>$qty
                    ]
                    );
            }
//            DB::commit();
        return true;  
        } catch (Exception $exc) {
//            DB::rollBack();
            LogOutletPortal::create([
                'id_outlet'=>$request['id_outlet'],
                'error'=>$exc->getTraceAsString()
            ]);
            return false;  
        }
    }
} 
 