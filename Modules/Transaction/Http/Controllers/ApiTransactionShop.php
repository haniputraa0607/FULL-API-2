<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\User;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionSetting;

use Modules\Brand\Entities\Brand;

use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;

use App\Lib\MyHelper;
use DB;
use DateTime;

class ApiTransactionShop extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->product      = "Modules\Product\Http\Controllers\ApiProductController";
        $this->online_trx      = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
    }

    public function cart(Request $request){
        $post = $request->json()->all();
        if(empty($post['transaction_from'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Parameter transaction_from can not be empty']
            ]);
        }

        $post['item'] = app($this->online_trx)->mergeProducts($post['item']??[]);

        $grandTotal = app($this->setting_trx)->grandTotal();

        if(!empty($request->user()->id)){
            $user = User::with('memberships')->where('id', $request->user()->id)->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
        }

        $outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->with('today')->select('outlets.*', 'provinces.time_zone_utc as province_time_zone_utc');

        if(empty($post['id_outlet']) && empty($post['outlet_code'])) {
        	$post['id_outlet'] = Setting::where('key', 'default_outlet')->first()['value'] ?? null;
        }

        if(!empty($post['outlet_code'])){
            $outlet = $outlet->where('outlet_code', $post['outlet_code'])->first();
            $post['id_outlet'] = $outlet['id_outlet']??null;
        }else{
            $id_outlet = $post['id_outlet'];
            $outlet = $outlet->where('id_outlet', $id_outlet)->first();
        }
        if (empty($outlet)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
            ]);
        }

        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);

                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid'] || $post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Bundling Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product '.$post['sub']->original['product'].' Not Valid with Bundling '.$post['sub']->original['bundling_name']];
                            }
                        }
                    }
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['subtotal_final'] = array_sum($post['sub']['subtotal_final']);
                $post['subtotal'] = array_sum($post['sub']['subtotal']);
                $post['total_discount_bundling'] = $post['sub']['total_discount_bundling']??0;
                $post['subtotal'] = $post['subtotal'];
            }else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }

        $cashBack = app($this->setting_trx)->countTransaction('cashback', $post);
        $countUserTrx = Transaction::where('id_user', $user['id_user'])->where('transaction_payment_status', 'Completed')->count();
        $countSettingCashback = TransactionSetting::get();

        if ($countUserTrx < count($countSettingCashback)) {
            $cashBack = $cashBack * $countSettingCashback[$countUserTrx]['cashback_percent'] / 100;

            if ($cashBack > $countSettingCashback[$countUserTrx]['cashback_maximum']) {
                $cashBack = $countSettingCashback[$countUserTrx]['cashback_maximum'];
            }
        } else {

            $maxCash = Setting::where('key', 'cashback_maximum')->first();

            if (count($user['memberships']) > 0) {
                $cashBack = $cashBack * ($user['memberships'][0]['benefit_cashback_multiplier']) / 100;

                if($user['memberships'][0]['cashback_maximum']){
                    $maxCash['value'] = $user['memberships'][0]['cashback_maximum'];
                }
            }

            $statusCashMax = 'no';

            if (!empty($maxCash) && !empty($maxCash['value'])) {
                $statusCashMax = 'yes';
                $totalCashMax = $maxCash['value'];
            }

            if ($statusCashMax == 'yes') {
                if ($totalCashMax < $cashBack) {
                    $cashBack = $totalCashMax;
                }
            } else {
                $cashBack = $cashBack;
            }
        }

        $subTotalItem = 0;
        $continueCheckOut = true;
        foreach ($post['item'] as &$item) {
            $err = [];
            $product = $this->getDetailProduct($item['id_product'], $post['id_outlet']);

            if(!empty($product)){
                $product->append('photo');
                $product = $product->toArray();
            }else{
                $item['error_msg'] = 'Stok produk tidak ditemukan';
                $continueCheckOut = false;
                continue;
            }

            if($product['product_variant_status'] && !empty($item['id_product_variant_group'])){
                $product['product_stock_status'] = ProductVariantGroupDetail::where('id_product_variant_group', $item['id_product_variant_group'])
                        ->where('id_outlet', $outlet['id_outlet'])
                        ->first()['product_variant_group_detail_stock_item']??0;
            }

            if($item['qty'] > $product['product_stock_status']){
                $err[] = 'Jumlah pembelian produk melebihi stok yang tersedia';
            }
            unset($product['photos']);
            $product['id_custom'] = $item['id_custom']??null;
            $product['qty'] = $item['qty'];
            $product['id_product_variant_group'] = $item['id_product_variant_group'] ?? null;

            if($product['product_variant_status']){
                if ($product['id_product_variant_group']) {
                    $product['product_price'] = $item['transaction_product_price'];
                    $product['selected_variant'] = Product::getVariantParentId($item['id_product_variant_group'], Product::getVariantTree($item['id_product'], $outlet)['variants_tree'], array_column($product['extra_modifiers']??[], 'id_product_variant'));
                } else {
                    $product['selected_variant'] = [];
                }

                $order = array_flip($product['selected_variant']);
                $variants = array_merge(ProductVariant::select('id_product_variant', 'product_variant_name')->whereIn('id_product_variant', array_keys($item['variants']))->get()->toArray(), $product['extra_modifiers']??[]);
                usort($variants, function ($a, $b) use ($order) {
                    return $order[$a['id_product_variant']] <=> $order[$b['id_product_variant']];
                });
                $product['variants'] = $variants;

                if($product['id_product_variant_group']){
                    $productVariantGroup = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first();
                    if($productVariantGroup['product_variant_group_visibility'] == 'Hidden'){
                        $err[] = 'Product tidak tersedia';
                    }else{
                        if($outlet['outlet_different_price']){
                            $product_variant_group_price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $product['id_product_variant_group'])->where('id_outlet', $outlet['id_outlet'])->first()['product_variant_group_price']??0;
                        }else{
                            $product_variant_group_price = $productVariantGroup['product_variant_group_price']??0;
                        }
                    }
                }else{
                    $product_variant_group_price = (int) $product['product_price'];
                }
            }else{
                $product['id_product_variant_group'] = null;
                $product['selected_variant'] = [];
                $product['variants'] = [];
            }

            $product['product_price_total'] = $item['transaction_product_subtotal']??($product['product_price']*$item['qty']);
            $product['product_price'] = (int)($product_variant_group_price??$product['product_price']);
            unset($product['product_variant_status']);
            $subTotalItem = $subTotalItem + $product['product_price_total'];
            $product['error_msg'] = (empty($err)? null:implode(".", array_unique($err)));
            $product['qty_stock'] = (int)$product['product_stock_status'];
            unset($product['product_stock_status']);


            $item = [
            	'id_custom' => $product['id_custom'],
            	'id_product' => $product['id_product'],
            	'product_name' => $product['product_group']['product_group_name'] . ' ' . $product['variant_name'],
            	'product_code' => $product['product_code'],
            	'variant_name' => $product['variant_name'],
            	'product_description' => $product['product_description'],
            	'id_product_group' => $product['id_product_group'],
            	'product_price' => $product['product_price'],
            	'string_product_price' => 'Rp '.number_format((int)$product['product_price'],0,",","."),
            	'id_product_category' => $product['id_product_category'],
            	'id_brand' => $product['id_brand'],
            	'photo' => $product['photo'],
            	'product_group_name' => $product['product_group']['product_group_name'],
            	'qty' => $product['qty'],
            	'product_price_total' => $product['product_price_total'],
            	'error_msg' => $product['error_msg'],
            	'qty_stock' => $product['qty_stock']
            ];

            if(!empty($err)){
                $continueCheckOut = false;
            }
        }
        $result['items'] = $post['item'];

        $subTotalItemService = 0;

        if ($cashBack??false) {
            $result['point_earned'] = [
                'value' => MyHelper::requestNumber($cashBack,'_CURRENCY'),
                'text' 	=> MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
            ];
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
            ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        $result['currency'] = 'Rp';
        $result['outlet'] = [
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_address' => $outlet['outlet_address'],
            'delivery_order' => $outlet['delivery_order'],
            'today' => $outlet['today'],
            'color' => $brand['color_brand']
        ];

        $result['brand'] = [
            'id_brand' => $brand['id_brand'],
            'brand_code' => $brand['code_brand'],
            'brand_name' => $brand['name_brand'],
            'brand_logo' => $brand['logo_brand'],
            'brand_logo_landscape' => $brand['logo_landscape_brand']
        ];
        $result['subtotal_item'] = $subTotalItem;
        $result['subtotal_item_service'] = $subTotalItemService;

        if (empty($post['item'])) {
            $continueCheckOut = false;
        }
        $result['continue_checkout'] = $continueCheckOut;
        $result['complete_profile'] = (empty($user->complete_profile) ? false : true);

        $finalRes = [
        	'items' => $result['items'],
        	'continue_checkout' => $result['continue_checkout'],
        	'complete_profile' => $result['complete_profile']
        ];
        return MyHelper::checkGet($finalRes);
    }

    public function getDetailProduct($id_product, $id_outlet)
    {
    	$product = Product::select([
	                'products.id_product',
	                'products.product_name',
	                'products.product_code',
	                'products.variant_name',
	                'products.product_description',
	                'products.id_product_group',
	                DB::raw('(CASE
	                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = '.$id_outlet.' ) = 1 
	                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$id_outlet.' )
	                            ELSE product_global_price.product_global_price
	                        END) as product_price'),
	                DB::raw('(select product_detail.product_detail_stock_item from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $id_outlet . ' order by id_product_detail desc limit 1) as product_stock_status'),
	                'brand_product.id_product_category','brand_product.id_brand', 'products.product_variant_status'
	            ])
                ->join('brand_product','brand_product.id_product','=','products.id_product')
                ->leftJoin('product_global_price','product_global_price.id_product','=','products.id_product')
                ->where('brand_outlet.id_outlet','=',$id_outlet)
                ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility IS NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
                    END)')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$id_outlet.' )
                    END)')
                ->where(function ($query) use ($id_outlet){
                    $query->orWhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$id_outlet.' ) is NOT NULL');
                    $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product) is NOT NULL');
                })
                ->with([
                    'photos' => function($query){
                        $query->select('id_product','product_photo');
                    },
                    'product_group'
                ])
                ->having('product_price','>',0)
                ->groupBy('products.id_product')
                ->orderBy('products.position')
                ->find($id_product);

        return $product;
    }
}
