<?php

namespace App\Jobs;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Setting;
use App\Http\Models\TransactionProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Modules\Product\Entities\ProductDetail;
use Modules\ProductService\Entities\ProductServiceUse;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionHomeServiceHairStylistFinding;
use Modules\Transaction\Entities\TransactionHomeServiceStatusUpdate;
use Modules\Users\Http\Controllers\ApiUser;
use App\Http\Models\Transaction;

use App\Http\Models\Campaign;
use App\Http\Models\CampaignRuleView;

class FindingHairStylistHomeService implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$camp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        $arrHS = TransactionHomeServiceHairStylistFinding::where('status', 'Pending')
                ->where('id_transaction', $data['id_transaction'])->pluck('id_user_hair_stylist')->toArray();
        $trx = Transaction::where('id_transaction', $data['id_transaction'])->with('user')->first();

        if($trx['transaction_payment_status'] == 'Completed'){
            $trxProduct = TransactionProduct::where('id_transaction', $data['id_transaction'])->get()->toArray();
            $trxHomeService = TransactionHomeService::where('id_transaction_home_service', $data['id_transaction_home_service'])->first();
            $outletHomeService = Setting::where('key', 'default_outlet_home_service')->first()['value']??null;
            $outlet = Outlet::where('id_outlet', $outletHomeService)->first();
            $getHs = null;

            TransactionHomeService::where('id_transaction_home_service', $data['id_transaction_home_service'])->update(['status' => 'Finding Hair Stylist']);
            if($trxHomeService['counter_finding_hair_stylist'] == 0){
                $updateStatus = TransactionHomeServiceStatusUpdate::create(['id_transaction' => $data['id_transaction'],'status' => 'Finding Hair Stylist']);
            }

            if($trxHomeService['preference_hair_stylist'] == 'Favorite'){
                $getHs = $arrHS[0]??null;
            }else{
                foreach ($arrHS as $idHs){
                    $err = [];
                    foreach ($trxProduct as $key=>$item){
                        $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                            ->select('products.*', 'product_global_price as product_price')
                            ->where('products.id_product', $item['id_product'])
                            ->first();

                        $hs = UserHairStylist::where('id_user_hair_stylist', $idHs)->where('user_hair_stylist_status', 'Active')->first();
                        if(empty($hs)){
                            $err[] = "Outlet hair stylist not found";
                            continue;
                        }

                        $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
                        $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                        if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                            $err[] = 'Service tidak tersedia';
                            continue;
                        }

                        if($item['transaction_product_qty'] > $getProductDetail['product_detail_stock_item']){
                            $err[] = 'Service tidak tersedia';
                            continue;
                        }

                        if(empty($service['product_price'])){
                            $err[] = 'Service tidak tersedia';
                            continue;
                        }
                    }

                    if(empty($err)){
                        $getHs = $idHs;
                        break;
                    }
                }
            }

            if(!empty($getHs)){
                $update = TransactionHomeService::where('id_transaction_home_service', $data['id_transaction_home_service'])
                    ->update([
                        'id_user_hair_stylist' => $getHs,
                        'counter_finding_hair_stylist' => $trxHomeService['counter_finding_hair_stylist'] + 1
                    ]);
                if($update){
                    app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                        'Home Service Update Status',
                        $trx['user']['phone'],
                        [
                            'id_transaction' => $trx['id_transaction'],
                            'status'=> $updateStatus['status']??' ',
                            'receipt_number' => $trx['transaction_receipt_number']
                        ]
                    );

                    $dataHS = UserHairStylist::where('id_user_hair_stylist', $getHs)->first();
                    app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                        'Home Service Mitra Get Order',
                        $dataHS['phone_number'],
                        [
                            'id_transaction' => $trx['id_transaction'],
                            'receipt_number' => $trx['transaction_receipt_number']
                        ], null, false, false, 'hairstylist'
                    );

                    app("Modules\Transaction\Http\Controllers\ApiOnlineTransaction")->bookHS($data['id_transaction']);
                    app("Modules\Transaction\Http\Controllers\ApiTransactionHomeService")->bookProductServiceStockHM($data['id_transaction']);
                }
            }else{
                $updateStatus = TransactionHomeServiceStatusUpdate::create([
                    'id_transaction' => $data['id_transaction'],
                    'status' => 'Cancelled'
                ]);

                if($updateStatus){
                    app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                        'Home Service Update Status',
                        $trx['user']['phone'],
                        [
                            'id_transaction' => $trx['id_transaction'],
                            'status'=> $updateStatus['status']??' ',
                            'receipt_number' => $trx['transaction_receipt_number']
                        ]
                    );

                    TransactionHomeService::where('id_transaction_home_service', $trxHomeService['id_transaction_home_service'])->update([
                        'id_user_hair_stylist' => null,
                        'status' => 'Cancelled'
                    ]);

                    TransactionHomeServiceHairStylistFinding::where('id_transaction', $data['id_transaction'])->delete();
                    //refund payment
                    app('Modules\Transaction\Http\Controllers\ApiTransactionHomeService')->rejectOrder($data['id_transaction']);
                }
            }
        }

        return true;
    }
}
