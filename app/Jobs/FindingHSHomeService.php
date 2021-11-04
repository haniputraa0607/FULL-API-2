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
use Modules\Users\Http\Controllers\ApiUser;

use App\Http\Models\Campaign;
use App\Http\Models\CampaignRuleView;

class FindingHSHomeService implements ShouldQueue
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
        $trxProduct = TransactionProduct::where('id_transaction', $data['id_transaction'])->get()->toArray();
        $getHs = null;

        foreach ($arrHS as $idHs){
            $err = [];
            foreach ($trxProduct as $key=>$item){
                $service = Product::where('products.id_product', $item['id_product'])
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
                    }

                    foreach ($getProductUse as $stock){
                        $use = $stock['quantity_use'] * $item['qty'];
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

            if(!empty($err)){
                $getHs = $idHs;
                break;
            }
        }

        if(!empty($getHs)){
            $update = [];
            app("Modules\Transaction\Http\Controllers\ApiOnlineTransaction")->bookHS($data['id_transaction']);
        }
    }
}
