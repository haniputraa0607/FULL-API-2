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
use Modules\Product\Entities\RequestProduct;
use Modules\Product\Entities\RequestProductDetail;
use Modules\Recruitment\Entities\HairstylistIncome;
use Modules\Product\Entities\ProductIcount;

class ApiProfitController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }
    public function index(Request $request) {
        //status
           if(isset($request->id_outlet) && !empty($request->id_outlet) && isset($request->dari_month) && !empty($request->dari_month) && isset($request->sampai_month) && !empty($request->sampai_month) ){
               
               $s = 1;
               $array= array();
               for ($i = 0; $i < $s; $i++) {
                   $dates = date('M Y',strtotime('+'.$i.'month'.$request->dari_month));
                   if($dates != date('M Y',strtotime($request->sampai_month))){
                       $s++;
                   }
                   $month = date('M Y',strtotime('+'.$i.'month'.$request->dari_month));
                   $date_now_awal = date('Y-m-01',strtotime('+'.$i.'month'.$request->dari_month));
                   $date_now_akhir = date('Y-m-t',strtotime('+'.$i.'month'.$request->dari_month));
                   $report = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                       ->whereDate('transactions.transaction_date', '>=', $date_now_awal)->whereDate('transactions.transaction_date', '<=', $date_now_akhir)
                       ->where('transaction_outlet_services.reject_at', NULL)
                       ->where('transactions.reject_at', NULL)
                       ->where('transactions.transaction_payment_status', 'Completed')
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
                                                #revenue
                                                SUM(
                                                CASE WHEN transactions.transaction_gross IS NOT NULL AND transaction_outlet_services.reject_at IS NULL AND transactions.transaction_payment_status = "Completed" AND transactions.reject_at IS NULL THEN transactions.transaction_gross 
                                                        ELSE 0 END
                                                ) as total_revenue
					'))->first();

                            $refund = Transaction::where(array('transactions.id_outlet'=>$request->id_outlet))
                                           ->whereDate('transactions.transaction_date', '>=', $date_now_awal)->whereDate('transactions.transaction_date', '<=', $date_now_akhir)
                                           ->where('transaction_outlet_services.reject_at', NULL)
                                           ->where('transactions.reject_at', NULL)
                                           ->where('transactions.transaction_payment_status', 'Completed')
                                           ->join('transaction_outlet_services', 'transaction_outlet_services.id_transaction', 'transactions.id_transaction')
                                           ->join('transaction_product_services', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                                           ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                                           ->select(DB::raw('
                                                            sum(
                                                           CASE WHEN
                                                           transaction_products.reject_at IS NULL
                                                           THEN transaction_products.transaction_variant_subtotal ELSE 0
                                                           END
                                                            ) as refund_product
                                                            '))
                                           ->first();
                            $total_net_sales = $report['total_revenue'] - ($refund['refund_product']+$report['total_discount']+$report['total_tax']);
                            $income = HairstylistIncome::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_incomes.id_user_hair_stylist')
                                           ->where(array('user_hair_stylist.id_outlet'=>$request->id_outlet))
                                           ->whereBetween('hairstylist_incomes.periode',[$date_now_awal,$date_now_akhir]) 
                                           ->where('hairstylist_incomes.status','Completed')
                                            ->select(DB::raw('
                                                            sum(
                                                           CASE WHEN
                                                           hairstylist_incomes.completed_at IS NOT NULL
                                                           THEN hairstylist_incomes.amount ELSE 0
                                                           END
                                                            ) as incomes
                                                            '))
                                           ->first();
                            $product = RequestProduct::join('request_product_details','request_product_details.id_request_product','request_products.id_request_product')
                                        ->join('product_icounts','product_icounts.id_product_icount','request_product_details.id_product_icount')
                                        ->where(array('request_products.id_outlet'=>$request->id_outlet,'request_product_details.status'=>'Approved','request_product_details.budget_code'=>'Beban'))
                                        ->whereBetween('request_products.requirement_date',[$date_now_awal,$date_now_akhir])
                                        ->get();
                            $total_beban = 0;
                            foreach ($product as $value) {
                                if(!empty($value['total_price'])){
                                    $total_beban += $value['total_price'];
                                }else{
                                    $product_icount = ProductIcount::where('id_product_icount',$value['id_product_icount'])->first();
                                    if($value['unit'] == $product_icount['unit1']){
                                        if($product_icount['unit_price_1']!=0){
                                             $price = $product_icount['unit_price_1'];
                                        }elseif($product_icount['unit_price_2']!=0){
                                             $price = $product_icount['unit_price_2']/$product_icount['ratio2'];
                                        }elseif($product_icount['unit_price_3']!=0){
                                             $price = $product_icount['unit_price_3']/$product_icount['ratio3'];
                                        }
                                    }elseif($value['unit'] == $product_icount['unit2']){
                                        if($product_icount['unit_price_2']!=0){
                                             $price = $product_icount['unit_price_2'];
                                        }elseif($product_icount['unit_price_1']!=0){
                                             $price = $product_icount['unit_price_1']*$product_icount['ratio2'];
                                        }elseif($product_icount['unit_price_3']!=0){
                                             $price = $product_icount['unit_price_3']*$product_icount['ratio2']/$product_icount['ratio3'];
                                        }
                                    }elseif($value['unit'] == $product_icount['unit3']){
                                        if($product_icount['unit_price_3']!=0){
                                             $price = $product_icount['unit_price_3'];
                                        }elseif($product_icount['unit_price_1']!=0){
                                             $price = $product_icount['unit_price_1']*$product_icount['ratio3'];
                                        }elseif($product_icount['unit_price_2']!=0){
                                             $price = $product_icount['unit_price_3']/$product_icount['ratio2']*$product_icount['ratio3'];
                                        }
                                    }
                                    $total_beban +=$price*$value['value'];
                                }
                            }
                            $profit = $total_net_sales-$income['incomes']-$total_beban;
                            if($profit>=0){
                                $text_profit = "Total Profit";
                            }else{
                                $text_profit = "Total Loss";
                            }
                            $data['month'] = $month;
                            $data['data'] = [
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
                                'incomes' => [
                                    'title' => 'Pengeluaran Salary HS',
                                    'amount' => 'Rp. '.number_format($income['incomes']??0,0,",","."),
                                    "tooltip" => 'Total pengeluaran untuk salary hairstylist',
                                    "show" => 1
                                ],
                                'request_product' => [
                                    'title' => 'Pengeluaran Purchase Request',
                                    'amount' => 'Rp. '.number_format($total_beban??0,0,",","."),
                                    "tooltip" => 'Total pengeluaran untuk permintaan product',
                                    "show" => 1
                                ],
                                'profit_loss' => [
                                    'title' =>  $text_profit,
                                    'amount' => 'Rp. '.number_format($profit??0,0,",","."),
                                    "tooltip" => 'Hasil pendapatan outlet',
                                    "show" => 1
                                ],
                            ];
                            $array[] =  $data;
                   
               }
       return response()->json(['status' => 'success', 'result' => $array]);  
       }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
   
} 
 