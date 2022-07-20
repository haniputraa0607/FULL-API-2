<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\DailyTransactions;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\User;
use App\Http\Models\Outlet;
use App\Http\Models\Province;
use App\Http\Models\Product;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionSetting;
use App\Http\Models\UserAddress;
use App\Http\Models\Configs;

use Modules\Brand\Entities\Brand;

use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Modules\Transaction\Entities\TransactionShop;

use Modules\Transaction\Http\Requests\Transaction\NewTransaction;

use App\Lib\MyHelper;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use DB;
use DateTime;
use App\Lib\WeHelpYou;
use Modules\Franchise\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\TransactionPromo;

class ApiTransactionShop extends Controller
{
    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->product       = "Modules\Product\Http\Controllers\ApiProductController";
        $this->online_trx    = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->outlet        = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->promo_trx 	 = "Modules\Transaction\Http\Controllers\ApiPromoTransaction";
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
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
            $product['qty_stock'] = (int)$product['product_stock_status'];
            unset($product['product_stock_status']);

            $product['product_detail'] = ProductDetail::where(['id_product' => $item['id_product'], 'id_outlet' => $outlet['id_outlet']])->first();
            $max_order = null;

	        if(isset($product['product_detail']['max_order'])){
	            $max_order = $product['product_detail']['max_order'];
	        }
	        if($max_order==null){
	            $max_order = Outlet::select('max_order')->where('id_outlet',$outlet['id_outlet'])->pluck('max_order')->first();
	            if($max_order == null){
	                $max_order = Setting::select('value')->where('key','max_order')->pluck('value')->first();
	                if($max_order == null){
	                    $max_order = 100;
	                }
	            }
	        }

	        $product_name = $product['product_group']['product_group_name'] . ' ' . $product['variant_name'];
	        $max_order = (int) $max_order;
        	$max_order_alert = MyHelper::simpleReplace(Setting::select('value_text')
				->where('key','transaction_exceeds_limit_text')
				->pluck('value_text')
				->first()?:'Transaksi anda melebihi batas! Maksimal transaksi untuk %product_name% : %max_order%',
                    [
                        'product_name' => $product_name,
                        'max_order' => $max_order
                    ]
                );

        	if($item['qty'] > $max_order){
                $err[] = $max_order_alert;
            }
            $product['error_msg'] = (empty($err)? null:implode(".", array_unique($err)));
            $stock = 'Available';
            if (empty($product['qty_stock']) || $product['qty_stock'] <= 0) {
                $stock = 'Sold Out';
            }

            $item = [
            	'id_custom' => $product['id_custom'],
            	'id_product' => $product['id_product'],
            	'product_name' => $product_name,
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
            	'qty_stock' => $product['qty_stock'],
            	'product_stock_status' => $stock,
            	'max_order' => $max_order,
            	'max_order_alert' => $max_order_alert
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
        	'point_earned' => $result['point_earned'] ?? null,
        	'continue_checkout' => $result['continue_checkout'],
        	'complete_profile' => $result['complete_profile']
        ];
        return MyHelper::checkGet($finalRes);
    }

    public function check(Request $request) {
        $post = $request->json()->all();

        if (empty($post['transaction_from'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Parameter transaction_from can not be empty']
            ]);
        }

        if (empty($post['item'])) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item can not be empty']
            ]);
        }

        if (empty($post['outlet_code']) && empty($post['id_outlet'])) {
        	$post['id_outlet'] = Setting::where('key', 'default_outlet')->first()['value'] ?? null;
        }

        $grandTotal = app($this->setting_trx)->grandTotal();
        $user = $request->user();
        if ($user->complete_profile == 0) {
            return response()->json([
                'status'    => 'success',
                'result'  => [
                    'complete_profile' => false
                ]
            ]);
        }

        if (!empty($post['outlet_code'])) {
            $outlet = Outlet::where('outlet_code', $post['outlet_code'])->with('today')->where('outlet_status', 'Active')
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')
                ->first();
            $post['id_outlet'] = $outlet['id_outlet'] ?? null;
        } else {
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->where('outlet_status', 'Active')
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')
                ->first();
        }

        $id_outlet = $post['id_outlet'];
        if (empty($outlet)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
            ]);
        }

        $issetDate = false;
        if (isset($post['transaction_date'])) {
            $issetDate = true;
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if (!isset($post['shipping'])) {
            $post['shipping'] = 0;
        }

        $error_msg = [];

        if (!isset($post['subtotal'])) {
            $post['subtotal'] = 0;
        }

        if (!isset($post['discount'])) {
            $post['discount'] = 0;
        }

        if (!isset($post['service'])) {
            $post['service'] = 0;
        }

        if (!isset($post['tax'])) {
            $post['tax'] = 0;
        }

        $totalItem = 0;
        $totalDisProduct = 0;
        $post['discount'] = -$post['discount'];
        $subtotal = 0;
        $items = [];
        $post['item'] = app($this->online_trx)->mergeProducts($post['item']);

        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
                // $post['sub'] = $this->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }
                    }

                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                // $post['subtotal'] = array_sum($post['sub']);
                $post['subtotal'] = array_sum($post['sub']['subtotal']);
                $post['subtotal'] = $post['subtotal'] - $totalDisProduct??0;
            }elseif ($valueTotal == 'discount') {
                // $post['dis'] = $this->countTransaction($valueTotal, $post);
                $post['dis'] = app($this->setting_trx)->countTransaction($valueTotal, $post, $discount_promo);
                $mes = ['Data Not Valid'];

                if (isset($post['dis']->original['messages'])) {
                    $mes = $post['dis']->original['messages'];

                    if ($post['dis']->original['messages'] == ['Price Product Not Found']) {
                        if (isset($post['dis']->original['product'])) {
                            $mes = ['Price Product Not Found with product '.$post['dis']->original['product'].' at outlet '.$outlet['outlet_name']];
                        }
                    }

                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                // $post['discount'] = $post['dis'] + $totalDisProduct;
                $post['discount'] = $totalDisProduct??0;
            }else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }

        $subtotalProduct = 0;
        foreach ($discount_promo['item']??$post['item'] as &$item) {

            $product = $this->getDetailProduct($item['id_product'], $post['id_outlet']);
            $product->load([
            			'product_promo_categories' => function($query) {
                            $query->select('product_promo_categories.id_product_promo_category','product_promo_category_name as product_category_name','product_promo_category_order as product_category_order');
                        }
                    ])->append('photo');

	        $product = $product->toArray();

            if($product['product_variant_status'] && !empty($item['id_product_variant_group'])){
                $product['product_stock_status'] = ProductVariantGroupDetail::where('id_product_variant_group', $item['id_product_variant_group'])
                        ->where('id_outlet', $outlet['id_outlet'])
                        ->first()['product_variant_group_detail_stock_item']??0;
            }

            if($item['qty'] > $product['product_stock_status']){
                $error_msg[] = MyHelper::simpleReplace(
                    'Produk %product_name% tidak tersedia',
                    [
                        'product_name' => $product['product_name']
                    ]
                );
                continue;
            }
            unset($product['photos']);
            $product['id_custom'] = $item['id_custom']??null;
            $product['qty'] = $item['qty'];

            $subtotalProduct = $subtotalProduct + $item['transaction_product_subtotal'];

            //calculate total item
            $totalItem += $product['qty'];

            $tempItem =   [
            	'id_custom' => $product['id_custom'],
            	'id_product' => $product['id_product'],
            	'product_name' => $product['product_group']['product_group_name'] . ' ' . $product['variant_name'],
            	'product_code' => $product['product_code'],
            	'variant_name' => $product['variant_name'],
            	'product_description' => $product['product_description'],
            	'id_product_group' => $product['id_product_group'],
            	'id_product_category' => $product['id_product_category'],
            	'id_brand' => $product['id_brand'],
            	'photo' => $product['photo'],
            	'product_group_name' => $product['product_group']['product_group_name'],
            	'qty' => $product['qty'],
            	'product_stock_status' => $product['product_stock_status'],
	            'product_price' => (int) $product['product_price'],
	            'product_price_raw' => (int) $product['product_price'],
	            'product_price_raw_total' => (int) $item['transaction_product_subtotal'],
	            'product_price_total_pretty' => MyHelper::requestNumber((int) $item['transaction_product_subtotal'],'_CURRENCY'),
	            'qty_stock' => (int)$product['product_stock_status'],
            ];

            if(!empty($tempItem['product_stock_status'])){
                $tempItem['product_stock_status'] = 'Available';
            }else{
                $tempItem['product_stock_status'] = 'Sold Out';
            }

            $items[] = $tempItem;
        }

        $post['tax'] = ($outlet['is_tax']/100) * $post['subtotal'];

        if ($post['id_user_address'] ?? null) {
            $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
        } else {
            $address = UserAddress::where('id_user', $user->id)->where('favorite', 1)->first();
        }

        if(empty($post['customer']) || empty($post['customer']['name'])){
            $id = $request->user()->id;

            $user = User::leftJoin('cities', 'cities.id_city', 'users.id_city')->where('id', $id)
                    ->select('users.*', 'cities.city_name')->first();
            if (empty($user)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }

            $result['customer'] = [
                "name" => $user['name'],
                "email" => $user['email'],
                "phone" => $user['phone'],
                "domicile" => $user['city_name'],
                "birthdate" => date('Y-m-d', strtotime($user['birthday'])),
                "gender" => $user['gender'],
                "address" => $address
            ];
        }else{
            $result['customer'] = [
                "name" => $post['customer']['name'] ?? null,
                "email" => $post['customer']['email'] ?? null,
                "phone" => $post['customer']['phone'] ?? null,
                "domicile" => $post['customer']['domicile'] ?? null,
                "birthdate" => $post['customer']['birthdate'] ?? null,
                "gender" => $post['customer']['gender'] ?? null,
                "address" => $post['customer']['address'] ?? []
            ];
        }

        $result['outlet'] = [
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_address' => $outlet['outlet_address'],
            'delivery_order' => $outlet['delivery_order'],
            'today' => $outlet['today']
        ];
        $result['item'] = $items;
        $result['subtotal_product'] = $subtotalProduct;
        $subtotal = $post['subtotal'];

        $earnedPoint = app($this->online_trx)->countTranscationPoint($post, $user);
        $cashback = $earnedPoint['cashback'] ?? 0;
        if ($cashback) {
            $result['point_earned'] = [
                'value' => MyHelper::requestNumber($cashback, '_CURRENCY'),
                'text' => MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
            ];
        }

        $listDelivery = $this->listDelivery();
        if (!$request->delivery_name && !$request->delivery_method) {
        	$deliv = $listDelivery[0] ?? null;
        } else {
        	$deliv = $this->findDelivery($listDelivery, $request->delivery_name, $request->delivery_method);
        }
        if (empty($deliv) && $request->delivery_name && $request->delivery_method) {
        	$error_msg[] = 'Pengiriman tidak ditemukan';
        }
        $post['shipping'] = $deliv['price'] ?? 0;

        $result['id_user_address'] = $address['id_user_address'] ?? null;
        $result['subtotal'] = $subtotal;
        $result['tax'] = $post['tax'];
        $result['shipping'] = $post['shipping'];
        $result['discount'] = $post['discount'];
        $result['grandtotal'] = (int)$result['subtotal'] + (int)(-$post['discount']) + (int)$post['service'] + (int)$post['tax'] + $post['shipping'];
        $result['subscription'] = 0;
        $result['used_point'] = 0;
        $balance = app($this->balance)->balanceNow($user->id);
        $result['points'] = (int) $balance;
        $result['total_payment'] = $result['grandtotal'] - $result['used_point'];
        $result['discount'] = (int) $result['discount'];
        $result['currency'] = 'Rp';
        $result['complete_profile'] = true;
        $result['payment_detail'] = [];
        $result['continue_checkout'] = (empty($error_msg) ? true : false);
        $messages_all_title = (empty($error_msg) ? null : 'TRANSAKSI TIDAK DAPAT DILANJUTKAN');
        $fake_request = new Request(['show_all' => 1]);
        $result['available_payment'] = app($this->online_trx)->availablePayment($fake_request)['result'] ?? [];
        
        $result = app($this->promo_trx)->applyPromoCheckout($result,$post);

        $result['payment_detail'][] = [
            'name'          => 'Subtotal Order ('.$totalItem.' item):',
            "is_discount"   => 0,
            'amount'        => number_format(((int) $result['subtotal']),0,',','.')
        ];

        $result['payment_detail'][] = [
            'name'          => 'Pengiriman:',
            "is_discount"   => 0,
            'amount'        => number_format(((int) $result['shipping']),0,',','.')
        ];

        if (!empty($result['tax'])) {
	        $result['payment_detail'][] = [
	            'name'          => 'Tax:',
	            "is_discount"   => 0,
	            'amount'        => number_format(((int) round($result['tax'])),0,',','.')

	        ];
        }

        $paymentDetailPromo = app($this->promo_trx)->paymentDetailPromo($result);
        $result['payment_detail'] = array_merge($result['payment_detail'], $paymentDetailPromo);

        if($result['promo_deals']){
            if($result['promo_deals']['is_error']){
                $result['continue_checkout'] = false;
                $messages_all_title = 'VOUCHER ANDA TIDAK DAPAT DIGUNAKAN';
                $error_msg = ['Silahkan gunakan voucher yang berlaku atau tidak menggunakan voucher sama sekali.'];
            }
        }
        if($result['promo_code']){
            if($result['promo_code']['is_error']){
                $result['continue_checkout'] = false;
                $messages_all_title = 'PROMO ANDA TIDAK DAPAT DIGUNAKAN';
                $error_msg = ['Silahkan gunakan promo yang berlaku atau tidak menggunakan promo sama sekali.'];
            }
        }

        if (count($error_msg) > 1 && (!empty($post['item']) || !empty($post['item_service']))) {
            $error_msg = ['Produk yang anda pilih tidak tersedia. Silakan cek kembali pesanan anda'];
        }
        
        $finalRes = [
        	'customer' => $result['customer'],
        	'item' => $result['item'],
        	'id_user_address' => $result['id_user_address'],
        	'subtotal' => $result['subtotal'],
        	'shipping' => $result['shipping'],
        	'tax' => $post['tax'],
        	'discount' => $result['discount'],
        	'discount_delivery' => $result['discount_delivery'],
        	'grandtotal' => $result['grandtotal'],
        	'subscription' => $result['subscription'],
        	'used_point' => $result['used_point'],
        	'points' => $result['points'],
        	'total_payment' => $result['total_payment'],
        	'currency' => $result['currency'],
        	'complete_profile' => $result['complete_profile'],
        	'payment_detail' => $result['payment_detail'],
            'point_earned' => $result['point_earned'] ?? null,
            'continue_checkout' => $result['continue_checkout'],
            'available_payment' => $result['available_payment'],
            'available_delivery' => $listDelivery,
            'selected_delivery' => $deliv,
            'available_voucher' => $result['available_voucher'],
            'promo_deals' => $result['promo_deals'],
            'promo_code' => $result['promo_code'],
            'messages_all' => (empty($error_msg) ? null : implode('.', $error_msg)),
            'messages_all_title' => $messages_all_title,
        ];

        return MyHelper::checkGet($finalRes);
    }

    public function newTransactionShop(NewTransaction $request) {
        $post = $request->json()->all();

        if(empty($post['item'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item can not be empty']
            ]);
        }

        if(!empty($request->user()->id)){
            $user = User::where('id', $request->user()->id)
                ->leftJoin('cities', 'cities.id_city', 'users.id_city')
                ->select('users.*', 'cities.city_name')
                ->with('memberships')
                ->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
        }

        if($user['complete_profile'] == 0){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Please complete your profile']
            ]);
        }

        if(empty($post['transaction_from'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Parameter transaction_from can not be empty']
            ]);
        }

        if(empty($post['id_user_address'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['ID user address can not be empty']
            ]);
        }
        $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
        if(empty($address)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address user not found']
            ]);
        }

        $post['latitude'] = $address['latitude']??null;
        $post['longitude'] = $address['longitude']??null;

        $outletShop = Setting::where('key', 'default_outlet')->first()['value'] ?? null;
        $outlet = Outlet::where('id_outlet', $outletShop)->first();
        if(empty($outlet)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet default not found']
            ]);
        }
        $post['id_outlet'] = $outlet['id_outlet'];

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
            ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        $dataItem = [];
        $post['item'] = app($this->online_trx)->mergeProducts($post['item'] ?? []);

        foreach ($post['item'] as $keyProduct => $valueProduct) {
            $this_discount = $valueProduct['discount'] ?? 0;
            $checkProduct = Product::where('id_product', $valueProduct['id_product'])->first();
            if (empty($checkProduct)) {
                return [
                    'status'    => 'fail',
                    'messages'  => ['Product Not Found']
                ];
            }

            if (!$checkProduct['product_variant_status']) {
                $checkDetailProduct = ProductDetail::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $post['id_outlet']])->first();
                $currentProductStock = $checkDetailProduct['product_detail_stock_item'] ?? 0;
                $currentProductServiceStock = $checkDetailProduct['product_detail_stock_service'] ?? 0;
                $stockItem = 1;
                if ($valueProduct['qty'] > $currentProductStock) {
                    return [
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].' tidak tersedia dan akan terhapus dari cart.']
                    ];
                }

                if ($checkDetailProduct['product_detail_visibility'] == 'Hidden' || (empty($checkDetailProduct) && $checkProduct['product_visibility'] == 'Hidden')) {
                    return [
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].' tidak tersedia dan akan terhapus dari cart.']
                    ];
                }
            }

            if(!isset($valueProduct['note'])){
                $valueProduct['note'] = null;
            }

            $productPrice = 0;

            if ($outlet['outlet_different_price']) {
                $checkPriceProduct = ProductSpecialPrice::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $post['id_outlet']])->first();
                if(!isset($checkPriceProduct['product_special_price'])){
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Product Price Not Valid']
                    ];
                }
                $productPrice = $checkPriceProduct['product_special_price'];
            } else {
                $checkPriceProduct = ProductGlobalPrice::where(['id_product' => $checkProduct['id_product']])->first();

                if(isset($checkPriceProduct['product_global_price'])){
                    $productPrice = $checkPriceProduct['product_global_price'];
                }else{
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Product Price Not Valid']
                    ];
                }
            }

            $dataItem[] = [
                "id_custom" => $valueProduct['id_custom'],
                "id_brand" => $brand['id_brand'],
                "id_product" => $checkProduct['id_product'],
                "product_code" => $checkProduct['product_code'],
                "product_name" => $checkProduct['product_name'],
                "product_price" => (int) $productPrice,
                "subtotal" => (int) $productPrice * $valueProduct['qty'],
                "qty" => $valueProduct['qty']
            ];
        }

        $post['item'] = $dataItem;
        $grandTotal = app($this->setting_trx)->grandTotal();
        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);

                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Price Product not found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Not Found with product '.$post['sub']->original['product']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['sub']->original['product']];
                            }
                        }
                    }

                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['subtotal'] = array_sum($post['sub']['subtotal']);
            } else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }

        $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
        if(empty($address)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address user not found']
            ]);
        }

        if (isset($post['transaction_payment_status'])) {
            $post['transaction_payment_status'] = $post['transaction_payment_status'];
        } else {
            $post['transaction_payment_status'] = 'Pending';
        }

        if (count($user['memberships']) > 0) {
            $post['membership_level']    = $user['memberships'][0]['membership_name'];
            $post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
        } else {
            $post['membership_level']    = null;
            $post['membership_promo_id'] = null;
        }

        $earnedPoint = app($this->online_trx)->countTranscationPoint($post, $user);
        $cashback = $earnedPoint['cashback'] ?? 0;

        $listDelivery = $this->listDelivery();
        $deliv = $this->findDelivery($listDelivery, $request->delivery_name, $request->delivery_method);
        if (empty($deliv)) {
        	return response()->json([
                'status'    => 'fail',
                'messages'  => ['Pengiriman tidak ditemukan']
            ]);
        }
        $post['shipping'] = $deliv['price'];
        $post['tax'] = ($outlet['is_tax']/100) * $post['subtotal'];
        $grandTotal = (int)$post['subtotal'] + (int)$post['tax'] + (int)$post['shipping'];

        DB::beginTransaction();

        $transaction = [
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $request->user()->id,
            'transaction_date'            => date('Y-m-d H:i:s'),
            'transaction_shipment'        => $post['shipping'] ?? 0,
            'transaction_service'         => $post['service'] ?? 0,
            'transaction_discount'        => $post['discount'] ?? 0,
            'transaction_discount_delivery' => $post['discount_delivery'] ?? 0,
            'transaction_discount_item' 	=> $discountItem ?? 0,
            'transaction_discount_bill' 	=> $discountBill ?? 0,
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_gross'  		  => $post['subtotal'],
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $grandTotal,
            'transaction_cashback_earned' => $cashback,
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'membership_level'            => $post['membership_level'],
            'membership_promo_id'         => $post['membership_promo_id'],
            'latitude'                    => $post['latitude'],
            'longitude'                   => $post['longitude'],
            'void_date'                   => null,
            'transaction_from'            => $post['transaction_from'],
            'scope'                       => 'apps'
        ];

        $useragent = $_SERVER['HTTP_USER_AGENT'];
        if(stristr($useragent,'iOS')) $useragent = 'IOS';
        elseif(stristr($useragent,'okhttp')) $useragent = 'Android';
        else $useragent = null;

        if($useragent){
            $transaction['transaction_device_type'] = $useragent;
        }

        $insertTransaction = Transaction::create($transaction);

        if (!$insertTransaction) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }

        $lastReceipt = Transaction::where('id_outlet', $insertTransaction['id_outlet'])->orderBy('transaction_receipt_number', 'desc')->first()['transaction_receipt_number']??'';
        $lastReceipt = substr($lastReceipt, -5);
        $lastReceipt = (int)$lastReceipt;
        $countReciptNumber = $lastReceipt+1;
        $receipt = '#'.substr($outlet['outlet_code'], -4).'-'.sprintf("%05d", $countReciptNumber);
        $updateReceiptNumber = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->update([
            'transaction_receipt_number' => $receipt
        ]);
        if (!$updateReceiptNumber) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Failed']
            ]);
        }
        $insertTransaction['transaction_receipt_number'] = $receipt;


        $createTransactionShop = TransactionShop::create([
        	'id_transaction' => $insertTransaction['id_transaction'],
			'delivery_method' =>  $request->delivery_method,
			'delivery_name' =>  $request->delivery_name,
			'destination_name' => $user['name'],
			'destination_phone' => $user['phone'],
			'destination_address' => $address['address'],
			'destination_short_address' => $address['short_address'],
			'destination_address_name' => $address['name'],
			'destination_note' => (empty($post['notes']) ? $address['description']:$post['notes']),
			'destination_latitude' => $address['latitude'],
			'destination_longitude' => $address['longitude'],
        ]);

        if (!$createTransactionShop) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Shop Failed']
            ]);
        }

        $userTrxProduct = [];
        foreach ($post['item'] as $itemProduct){

            $dataProduct = [
                'id_transaction'               => $insertTransaction['id_transaction'],
                'id_product'                   => $itemProduct['id_product'],
                'type'                         => 'Product',
                'id_outlet'                    => $insertTransaction['id_outlet'],
                'id_brand'                     => $itemProduct['id_brand'],
                'id_user'                      => $insertTransaction['id_user'],
                'transaction_product_qty'      => $itemProduct['qty'],
                'transaction_product_price'    => $itemProduct['product_price'],
                'transaction_product_price_base' => $itemProduct['product_price'],
                'transaction_product_discount'   => 0,
                'transaction_product_discount_all'   => 0,
                'transaction_product_base_discount' => 0,
                'transaction_product_qty_discount'  => 0,
                'transaction_product_subtotal' => $itemProduct['subtotal'],
                'transaction_product_net' => $itemProduct['subtotal'],
                'transaction_product_note'     => null,
                'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])).' '.date('H:i:s'),
                'updated_at'                   => date('Y-m-d H:i:s')
            ];

            $trxProduct = TransactionProduct::create($dataProduct);
            if (!$trxProduct) {
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'messages'  => ['Insert Product Transaction Failed']
                ];
            }

            $dataUserTrxProduct = [
                'id_user'       => $insertTransaction['id_user'],
                'id_product'    => $itemProduct['id_product'],
                'product_qty'   => $itemProduct['qty'],
                'last_trx_date' => $insertTransaction['transaction_date']
            ];
            array_push($userTrxProduct, $dataUserTrxProduct);
        }

        $insertUserTrxProduct = app($this->transaction)->insertUserTrxProduct($userTrxProduct);
        if ($insertUserTrxProduct == 'fail') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Product Transaction Failed']
            ]);
        }

        $applyPromo = app($this->promo_trx)->applyPromoNewTrx($insertTransaction);
        if ($applyPromo['status'] == 'fail') {
        	DB::rollback();
            return $applyPromo;
        }

        $insertTransaction = $applyPromo['result'] ?? $insertTransaction;
        $dataDailyTrx = [
            'id_transaction'    => $insertTransaction['id_transaction'],
            'id_outlet'         => $outlet['id_outlet'],
            'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
            'id_user'           => $user['id'],
            'referral_code'     => NULL
        ];
        DailyTransactions::create($dataDailyTrx);
        DB::commit();

        if(!empty($insertTransaction['id_transaction']) && $insertTransaction['transaction_grandtotal'] == 0){
            $trx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->first();
            $this->bookProductStock($trx['id_transaction']);
            optional($trx)->recalculateTaxandMDR();
            $trx->triggerPaymentCompleted();
        }
        return response()->json([
            'status'   => 'success',
            'result'   => $insertTransaction
        ]);
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

    public function shopList(Request $request) {
    	$user = $request->user();

    	$list = Transaction::where('transaction_from', 'shop')
    			->join('transaction_shops','transactions.id_transaction', 'transaction_shops.id_transaction')
    			->where('id_user', $user->id)
    			->orderBy('transaction_date', 'desc')
    			->with(['outlet.brands', 'products.product_group', 'products.photos']);

		switch (strtolower($request->status)) {
			case 'unpaid':
				$list->where('transaction_payment_status','Pending');
				break;

			case 'ongoing':
				$list->whereNotIn('transaction_shops.shop_status', ['Rejected by Admin', 'Rejected by Customer', 'Completed'])
				->where('transaction_payment_status','Completed');
				break;

			case 'complete':
				$list->where(function($q) {
					$q->whereIn('transaction_shops.shop_status', ['Rejected by Admin', 'Rejected by Customer', 'Completed'])
					->orWhere('transaction_payment_status','Cancelled');
				});
				break;
			
			default:
				// code...
				break;
		}

		$list = $list->paginate(10)->toArray();

		$resData = [];
		foreach ($list['data'] ?? [] as $val) {

			$orders = [];
			foreach ($val['products'] as $product) {
				$orders[] = [
					'product_name' => $product['product_group']['product_group_name'] . ' ' . $product['variant_name'],
					'product_qty' => $product['pivot']['transaction_product_qty'],
					'product_price' => $product['pivot']['transaction_product_price'],
					'product_subtotal' => $product['pivot']['transaction_product_subtotal'],
					'product_subtotal_pretty' => MyHelper::requestNumber((int) $product['pivot']['transaction_product_subtotal'],'_CURRENCY'),
					'photo' => $product['photos'][0]['url_product_photo']
				];
			}

			$shopStatus = $this->shopStatus($val['shop_status']);
			if ($val['transaction_payment_status'] == 'Pending') {
				$status = 'unpaid';
				$shopStatus = 'Menunggu Pembayaran';
			} elseif ($val['transaction_payment_status'] == 'Cancelled') {
				$status = 'cancelled';
				$shopStatus = 'Dibatalkan';
			} elseif (in_array($val['shop_status'], ['Rejected by Admin', 'Rejected by Customer', 'Completed']) && $val['transaction_payment_status'] == 'Completed') {
				$status = 'completed';
			} else {
				$status = 'ongoing';
			}

			$resData[] = [
				'id_transaction' => $val['id_transaction'],
				'transaction_receipt_number' => $val['transaction_receipt_number'],
				'transaction_date' => $val['transaction_date'],
				'customer_name' => $val['destination_name'],
				'status' => $status,
				'shop_status' => $val['shop_status'],
				'order_status' => $shopStatus,
				'order' => $orders
			];
		}

		$list['data'] = $resData;
		return MyHelper::checkGet($list);
    }

    public function shopDetail(Request $request) {

    	$user = $request->user();
    	$detail = Transaction::where('transaction_from', 'shop')
    			->join('transaction_shops','transactions.id_transaction', 'transaction_shops.id_transaction')
    			->where(function ($q) use ($request) {
    				$q->where('transactions.id_transaction', $request->id_transaction);
    				$q->orWhere('transactions.transaction_receipt_number', $request->transaction_receipt_number);
    			})
    			->orderBy('transaction_date', 'desc')
    			->with(
    				'outlet.brands', 
    				'transaction_products.product.photos',
    				'transaction_products.product.product_group'
    			)
    			->select(
    				'transactions.*', 
    				'transaction_shops.*', 
    				'transactions.completed_at as trx_completed_at',
    				'transaction_shops.completed_at as shop_completed_at'
    			);

    	if(empty($request->admin)){
            $detail = $detail->where('id_user', $user->id);
        }

    	$detail = $detail->first();

		if (!$detail) {
			return [
				'status' => 'fail',
				'messages' => ['Transaction not found']
			];
		}

        $trxPromo = $this->transactionPromo($detail);

		$products = [];
		$subtotalProduct = 0;
		$totalProductQty = 0;
		foreach ($detail['transaction_products'] as $product) {
			if ($product['type'] == 'Product') {
				$products[] = [
					'product_name' => $product['product']['product_name'],
					'product_qty' => $product['transaction_product_qty'],
					'product_price' => $product['transaction_product_price'],
					'product_price_pretty' => MyHelper::requestNumber((int) $product['transaction_product_price'],'_CURRENCY'),
					'product_subtotal' => $product['transaction_product_subtotal'],
					'product_subtotal_pretty' => MyHelper::requestNumber((int) $product['transaction_product_subtotal'],'_CURRENCY'),
					'photo' => $product['product']['photos'][0]['url_product_photo']
				];
				$subtotalProduct += abs($product['transaction_product_subtotal']);
				$totalProductQty += abs($product['transaction_product_qty']);
			}
		}

		$shopStatus = $this->shopStatus($detail['shop_status']);
		if ($detail['transaction_payment_status'] == 'Pending') {
			$status = 'unpaid';
			$shopStatus = 'Menunggu Pembayaran';
		} elseif ($detail['transaction_payment_status'] == 'Cancelled') {
			$status = 'cancelled';
			$shopStatus = 'Dibatalkan';
		} elseif (in_array($detail['shop_status'], ['Rejected by Admin', 'Rejected by Customer', 'Completed']) && $detail['transaction_payment_status'] == 'Completed') {
			$status = 'completed';
		} else {
			$status = 'ongoing';
		}

		$paymentDetail = [];
        $paymentDetail[] = [
            'name'          => 'Subtotal Order (' . $totalProductQty . ' item)',
            "is_discount"   => 0,
            'amount'        => number_format(((int) $detail['transaction_subtotal']),0,',','.')
        ];

        $paymentDetail[] = [
            'name'          => 'Pengiriman',
            "is_discount"   => 0,
            'amount'        => number_format(((int) $detail['transaction_shipment']),0,',','.')
        ];

        if (!empty($detail['transaction_tax']) && empty($request->admin)) {
	        $paymentDetail[] = [
	            'name'          => 'Tax',
	            "is_discount"   => 0,
	            'amount'        => number_format(((int) round($detail['transaction_tax'])),0,',','.')

	        ];
        }

        if($paymentDetail && isset($trxPromo)){
            $lastKey = array_key_last($paymentDetail);
            for($i = 0; $i < count($trxPromo); $i++){
                $KeyPosition = 1 + $i;
                $paymentDetail[$lastKey+$KeyPosition] = $trxPromo[$i];
            }
        }

        $trx = Transaction::where('id_transaction', $detail['id_transaction'])->first();
		$trxPayment = app($this->trx_outlet_service)->transactionPayment($trx);
    	$paymentMethod = null;
    	foreach ($trxPayment['payment'] as $p) {
    		$paymentMethod = $p['name'];
    		if (strtolower($p['name']) != 'balance') {
    			break;
    		}
    	}

    	$paymentMethodDetail = null;
    	if ($paymentMethod) {
	    	$paymentMethodDetail = [
	            'text'  => 'Metode Pembayaran',
	            'value' => $paymentMethod
	        ];
    	}

    	$custDetail = [
    		'name' => $detail['destination_name'],
    		'phone' => $detail['destination_phone'],
    		'destination_address' => $detail['destination_address'],
    		'destination_short_address' => $detail['destination_short_address'],
    		'destination_address_name' => $detail['destination_address_name'],
    		'destination_note' => $detail['destination_note'],
    		'destination_latitude' => $detail['destination_latitude'],
    		'destination_longitude' => $detail['destination_longitude'],
    	];

    	$listDelivery = $this->listDelivery();
    	$delivDetail = null;
    	$isOdd = date('i', strtotime($detail['transaction_date']));
    	foreach ($listDelivery as $d) {
    		if ($d['delivery_method'] == $detail['delivery_method'] && $d['delivery_name'] == $detail['delivery_name']) {
    			$delivDetail = $d;
    			$delivDetail['price'] = $detail['transaction_shipment'];
    			$delivDetail['delivery_number'] = 'INVH2120010180';
    			$delivDetail['live_tracking_url'] = $isOdd % 2 ? 'https://www.google.com/' : null;
    			break;
    		}
    	}

    	$statusLog = [];
    	if ($detail['trx_completed_at']) {
	    	$statusLog[] = [
	    		'text'  => $this->shopStatus('Pending'),
	            'date'  => $detail['trx_completed_at']
	    	];
    	}
    	if ($detail['received_at']) {
    		$statusLog[] = [
	    		'text'  => $this->shopStatus('Received'),
	            'date'  => $detail['received_at']
	    	];
    	}
    	if ($detail['ready_at']) {
    		$statusLog[] = [
	    		'text'  => $this->shopStatus('Ready'),
	            'date'  => $detail['ready_at']
	    	];
    	}
    	if ($detail['delivery_at']) {
    		$statusLog[] = [
	    		'text'  => $this->shopStatus('Delivery'),
	            'date'  => $detail['delivery_at']
	    	];
    	}
    	if ($detail['arrived_at']) {
    		$statusLog[] = [
	    		'text'  => $this->shopStatus('Arrived'),
	            'date'  => $detail['arrived_at']
	    	];
    	}
    	if ($detail['shop_completed_at']) {
    		$statusLog[] = [
	    		'text'  => $this->shopStatus('Completed'),
	            'date'  => $detail['shop_completed_at']
	    	];
    	}
    	if ($detail['shop_status'] == 'Rejected by Admin') {
    		$statusLog[] = [
	    		'text'  => $this->shopStatus('Rejected by Admin') . ($detail['reject_reason'] ? '(' . $detail['reject_reason'] . ')' : null),
	            'date'  => $detail['rejected_at']
	    	];
    	}
    	if ($detail['shop_status'] == 'Rejected by Customer') {
    		$statusLog[] = [
	    		'text'  => $this->shopStatus('Rejected by Customer') . ($detail['reject_reason'] ? '(' . $detail['reject_reason'] . ')' : null),
	            'date'  => $detail['rejected_at']
	    	];
    	}

    	$statusLog = array_reverse($statusLog);
    	foreach ($statusLog as $key => $val) {
    		$statusLog[$key]['time'] = MyHelper::adjustTimezone($val['date'], 7, 'H:i');
    		$statusLog[$key]['date'] = MyHelper::adjustTimezone($val['date'], 7, 'd/m/Y');
    		$statusLog[$key]['datetime'] = $val['date'];
    	}

        $outlet_transaction = Outlet::where('id_outlet',$detail['id_outlet'])->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet_transaction['id_city'])->first()['time_zone_utc']??null;
        $date_time = $this->getTimezone($detail['transaction_date'], $timeZone);

		$res = [
			'id_transaction' => $detail['id_transaction'],
			'transaction_receipt_number' => $detail['transaction_receipt_number'],
			'transaction_date' => $date_time['time'],
			'transaction_date_zone' => $date_time['time_zone_id'],
			'transaction_subtotal' => $detail['transaction_subtotal'],
			'transaction_grandtotal' => $detail['transaction_grandtotal'],
			'transaction_product_subtotal' => $subtotalProduct,
			'transaction_tax' => $detail['transaction_tax'],
            'mdr' => $detail['mdr'],
			'currency' => 'Rp',
			'status' => $status,
			'shop_status' => $detail['shop_status'],
			'order_status' => $shopStatus,
			'transaction_payment_status' => $detail['transaction_payment_status'],
			'customer_detail' => $custDetail,
			'delivery_detail' => $delivDetail,
			'product' => $products,
			'payment_detail' => $paymentDetail,
			'payment_method' => $paymentMethodDetail,
			'status_log' => $statusLog
		];
		
		return MyHelper::checkGet($res);
    }

    public function shopStatus($status)
    {
    	$arr = [
    		'Pending' => 'Menunggu Konfirmasi',
	    	'Received' => 'Pesanan Diproses',
	    	'Ready' => 'Pesanan Siap Dikirim',
	    	'Delivery' => 'Pesanan Telah Dikirim',
	    	'Arrived' => 'Pesanan Telah Sampai',
	    	'Completed' => 'Selesai',
	    	'Rejected by Admin' => 'Dibatalkan',
	    	'Rejected by Customer' => 'Pesanan Ditolak'
	    ];

	    return $arr[$status] ?? $status;
    }

    public function transactionPromo(Transaction $trx){
        $trx = clone $trx;
        $promo_discount = [];
        $promos = TransactionPromo::where('id_transaction', $trx['id_transaction'])->get()->toArray();
        if($promos){
            $promo_discount[0]=[
                "name"  => "Promo / Discount:",
                "desc"  => "",
                "is_discount" => 0,
                "amount" => null 
            ];
            foreach($promos as $p => $promo){
                if($promo['promo_type']=='Promo Campaign'){
                    $promo['promo_name'] = PromoCampaign::where('promo_title',$promo['promo_name'])->select('campaign_name')->first()['campaign_name'];
                }
                $promo_discount[$p+1] = [
                    "name"  => $promo['promo_name'],
                    "desc"  => "",
                    "is_discount" => 1,
                    "amount" => '- '.MyHelper::requestNumber($promo['discount_value'],'_CURRENCY')
                ];
            }
        }
        return $promo_discount;
    }

    public function listDelivery()
    {
    	$deliveries = app($this->online_trx)->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] ?? [];
    	foreach ($deliveries as &$d) {
    		$d['text'] = $d['delivery_name'] . ' (1 -2 hari)';
    		$d['est'] = MyHelper::adjustTimezone(date("Y-m-d", strtotime("+2 day")), null, 'j F Y', true);
    		$d['price'] = 10000;
    	}

    	return $deliveries;
    }

    public function findDelivery(array $listDeliv, $delivName = null, $delivMethod = null)
    {
    	if (!$delivName || !$delivMethod) {
    		return null;
    	}

    	$res = null;
    	foreach ($listDeliv as $d) {
    		if ($delivName == $d['delivery_name'] && $delivMethod == $d['delivery_method']) {
    			$res = $d;
    		}
    	}

    	return $res;
    }

    public function getTimezone($time = null, $time_zone_utc = 7, $format = 'Y-m-d H:i'){
        $data['time_zone_id'] = 'WIB';
        $default_time_zone_utc = 7;
        $time_diff = $time_zone_utc - $default_time_zone_utc;
        if(isset($time)){
        $data['time'] = date($format, strtotime('+'.$time_diff.' hour',strtotime($time)));
        }else{
        $data['time'] = date($format, strtotime('+'.$time_diff.' hour'));
        }
        switch ($time_zone_utc) {
            case 8:
                $data['time_zone_id'] = 'WITA';
            break;
            case 9:
                $data['time_zone_id'] = 'WIT';
            break;
        }
        return $data;
    }

    public function listShop(Request $request)
    {
        $list = Transaction::where('transaction_from', 'shop')
            ->join('transaction_shops','transactions.id_transaction', 'transaction_shops.id_transaction')
            ->join('users','transactions.id_user','=','users.id')
            ->join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
            ->leftJoin('transaction_products','transactions.id_transaction','=','transaction_products.id_transaction')
            ->leftJoin('products','products.id_product','=','transaction_products.id_product')
            ->leftJoin('transaction_payment_midtrans', 'transactions.id_transaction', '=', 'transaction_payment_midtrans.id_transaction')
            ->leftJoin('transaction_payment_xendits', 'transactions.id_transaction', '=', 'transaction_payment_xendits.id_transaction')
            ->with('user')
            ->select(
                'transaction_shops.*',
                'products.*',
                'transaction_products.*',
                'outlets.*',
                'users.*',
                'transactions.*'
	            )
            ->groupBy('transactions.id_transaction');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->getQuery()->getCountForPagination();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'id_transaction',
                'transaction_date',
                'outlet_code',
                'transaction_receipt_number',
                'name',
                'phone',
                'transaction_grandtotal',
                'transaction_payment_status',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $list->orderBy($colname, $column['dir']);
                }
            }
        }
        $list->orderBy('transactions.id_transaction', $column['dir'] ?? 'DESC');

        if ($request->page) {
            $list = $list->paginate($request->length ?: 15);
            $list->each(function($item) {
                $item->images = array_map(function($item) {
                    return config('url.storage_url_api').$item;
                }, json_decode($item->images) ?? []);
            });
            $list = $list->toArray();
            if (is_null($countTotal)) {
                $countTotal = $list['total'];
            }
            // needed by datatables
            $list['recordsTotal'] = $countTotal;
            $list['recordsFiltered'] = $list['total'];
            $list['data'] = array_map(function($val){
                $outlet = Outlet::where('id_outlet',$val['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                $date_time = $this->getTimezone($val['transaction_date'], $timeZone);
                $val['transaction_date'] = $date_time['time'].' '.$date_time['time_zone_id'];
                return $val;
            },$list['data']);
        } else {
            $list = $list->get();
            $list = array_map(function($val){
                $outlet = Outlet::where('id_outlet',$val['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                $date_time = $this->getTimezone($val['transaction_date'], $timeZone);
                $val['transaction_date'] = $date_time['time'].' '.$date_time['time_zone_id'];
                return $val;
            },$list->toArray());
        }
        return MyHelper::checkGet($list);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function($model2) use ($model, $where, $new_rule){
            $inner = [
                'transaction_receipt_number',
                'order_id',
                'outlet_name',
                'outlet_code',
                'transaction_grandtotal',
                'transaction_payment_status'
            ];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['name', 'phone', 'email'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('users.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['id_outlet'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('transactions.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }

            $inner = ['payment'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $explode = explode('-', $rul['parameter']);
                        $paymentGateway = $explode[0];
                        $paymentMethod = $explode[1];
                        if($paymentGateway == 'Cash'){
                            $model2->$where('transactions.trasaction_payment_type', 'Cash');
                        }elseif($paymentGateway == 'Midtrans'){
                            $model2->$where('transaction_payment_midtrans.payment_type',  $paymentMethod);
                        }elseif($paymentGateway == 'Xendit'){
                            $model2->$where('transaction_payment_xendits.type',  $paymentMethod);
                        }
                    }
                }
            }
        });

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(transaction_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }
}
