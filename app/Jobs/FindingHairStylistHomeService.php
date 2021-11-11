<?php

namespace App\Jobs;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
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
use Modules\Transaction\Entities\TransactionHomeServiceHairStylistReject;
use Modules\Transaction\Entities\TransactionHomeServiceStatusUpdate;
use Modules\Users\Http\Controllers\ApiUser;

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
        $arrHS = $data['arr_id_hs'];
        $trx = Transaction::where('id_transaction', $data['id_transaction'])->first();

        if($trx['transaction_payment_status'] == 'Pending'){
            FindingHairStylistHomeService::dispatch(['id_transaction' => $data['id_transaction'], 'id_transaction_home_service' => $data['id_transaction_home_service'],'arr_id_hs' => $arrHS])->allOnConnection('findinghairstylistqueue');
        }elseif($trx['transaction_payment_status'] == 'Completed'){
            TransactionHomeServiceStatusUpdate::create(['id_transaction' => $data['id_transaction'],'status' => 'Finding Hair Stylist']);

            $trxProduct = TransactionProduct::where('id_transaction', $data['id_transaction'])->get()->toArray();
            $trxHomeService = TransactionHomeService::where('id_transaction_home_service', $data['id_transaction_home_service'])->first();
            $getHs = null;

            $hsReject = TransactionHomeServiceHairStylistReject::where('id_transaction', $data['id_transaction'])->pluck('id_user_hair_stylist')->toArray();
            foreach ($arrHS as $idHs){
                $err = [];
                foreach ($trxProduct as $key=>$item){
                    $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                        ->select('products.*', 'product_global_price as product_price')
                        ->where('products.id_product', $item['id_product'])
                        ->with('product_service_use')
                        ->first();

                    $hs = UserHairStylist::where('id_user_hair_stylist', $idHs)->where('user_hair_stylist_status', 'Active')->first();
                    $outlet = Outlet::where('id_outlet', $hs['id_outlet'])->first();
                    if(empty($hs)){
                        $err[] = "Outlet hair stylist not found";
                        continue;
                    }

                    if(!empty($service['product_service_use'])){
                        $getProductUse = ProductServiceUse::join('product_detail', 'product_detail.id_product', 'product_service_use.id_product')
                            ->where('product_service_use.id_product_service', $service['id_product'])
                            ->where('product_detail.id_outlet', $outlet['id_outlet'])->get()->toArray();
                        if(count($service['product_service_use']) != count($getProductUse)){
                            $err[] = 'Stok habis';
                            continue;
                        }

                        foreach ($getProductUse as $stock){
                            $use = $stock['quantity_use'] * $item['transaction_product_qty'];
                            if($use > $stock['product_detail_stock_service']){
                                $err[] = 'Stok habis';
                                continue;
                            }
                        }
                    }

                    $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
                    $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                    if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                        $err[] = 'Service tidak tersedia';
                        continue;
                    }

                    if(empty($service['product_price'])){
                        $err[] = 'Service tidak tersedia';
                        continue;
                    }
                }

                if(empty($err)){
                    $check = array_search($idHs,$hsReject);
                    if($check === false){
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
                    app("Modules\Transaction\Http\Controllers\ApiOnlineTransaction")->bookHS($data['id_transaction']);
                    app("Modules\Transaction\Http\Controllers\ApiTransactionHomeService")->bookProductServiceStockHM($data['id_transaction']);
                }
            }else{
                $update = TransactionHomeService::where('id_transaction_home_service', $data['id_transaction_home_service'])->update([
                    'status' => 'Cancelled'
                ]);

                if($update){
                    TransactionHomeServiceStatusUpdate::create([
                        'id_transaction' => $data['id_transaction'],
                        'status' => 'Cancelled'
                    ]);
                    if($trxHomeService['counter_finding_hair_stylist'] > 0){
                        app("Modules\Transaction\Http\Controllers\ApiTransactionHomeService")->cancelBookHS($data['id_transaction']);
                        app("Modules\Transaction\Http\Controllers\ApiTransactionHomeService")->cancelBookProductServiceStockHM($data['id_transaction']);
                    }
                }
            }
        }

        return true;
    }
}
