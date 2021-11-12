<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\LogBalance;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionMultiplePayment;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\TransactionPaymentManual;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionPaymentOffline;
use App\Http\Models\TransactionProduct;
use App\Http\Models\UserAddress;
use App\Jobs\ExportFranchiseJob;
use App\Jobs\FindingHairStylistHomeService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Brand\Entities\Brand;
use Modules\IPay88\Entities\TransactionPaymentIpay88;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductStockLog;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use App\Http\Models\TransactionPayment;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\StockLog;
use Modules\ProductService\Entities\ProductServiceUse;
use Modules\Transaction\Entities\LogInvalidTransaction;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionHomeServiceStatusUpdate;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\Transaction\Http\Requests\Transaction\NewTransaction;
use Modules\UserFeedback\Entities\UserFeedbackLog;
use DB;

class ApiTransactionHomeService extends Controller
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

        if(empty($post['item_service'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item Service can not be empty']
            ]);
        }

        if(!empty($request->user()->id)){
            $user = User::where('id', $request->user()->id)->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
        }

        if($post['preference_hair_stylist'] == 'favorite' && empty($post['id_user_hair_stylist'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User hair stylist can not be empty']
            ]);
        }

        $bookNow = false;
        if(strtolower($post['booking_time']) == 'sekarang'){
            $bookNow = true;
            $post['booking_time'] = date('H:i', strtotime("+2 minutes", strtotime($post['booking_time_user'])));
        }

        $errAll = [];
        $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
        if(empty($address)){
            $errAll[] = 'Alamat tidak ditemukan';
        }

        $post['latitude'] = $address['latitude']??null;
        $post['longitude'] = $address['longitude']??null;

        $itemService = [];
        $arrProccessingTime = [];
        $continueCheckOut = true;
        $subtotal = 0;

        //process get time start and end
        foreach ($post['item_service']??[] as $key=>$item){
            $processingTime = Product::where('products.id_product', $item['id_product'])->first()['processing_time_service']??0;
            $arrProccessingTime[] = $processingTime * $item['qty'];
        }

        $post['sum_time'] = array_sum($arrProccessingTime);
        $checkHS = $this->checkAvailableHS($post, [], $user);
        $idHs = $checkHS['id_user_hair_stylist']??null;
        $errAll = array_merge($errAll, $checkHS['error_all']??[]);

        $post['item_service'] = $this->mergeService($post['item_service']);
        $outlet = Outlet::where('outlet_code', '00000')->first();
        foreach ($post['item_service']??[] as $key=>$item){
            $err = [];
            $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                ->leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                ->where('products.id_product', $item['id_product'])
                ->select('products.*', 'product_global_price as product_price', 'brand_product.id_brand')
                ->with('product_service_use')
                ->first();

            if(empty($service)){
                $err[] = 'Service tidak tersedia';
            }

            if(!empty($idHs) && $post['preference_hair_stylist'] == 'favorite'){
                $hs = UserHairStylist::where('id_user_hair_stylist', $idHs)->where('user_hair_stylist_status', 'Active')->first();
                if(empty($hs)){
                    $err[] = "Outlet hair stylist not found";
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
                            break;
                        }
                    }
                }

                $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
                $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                    $err[] = 'Service tidak tersedia';
                }

                if(empty($service['product_price'])){
                    $err[] = 'Service tidak tersedia';
                }
            }

            $itemService[$key] = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $service['id_brand'],
                "id_product" => $service['id_product'],
                "product_code" => $service['product_code'],
                "product_name" => $service['product_name'],
                "product_price" => (int)$service['product_price'],
                "subtotal" => (int)$service['product_price'] * $item['qty'],
                "qty" => $item['qty'],
                "error_msg" => (empty($err)? null:implode(".", array_unique($err)))
            ];

            if(!empty($err)){
                $continueCheckOut = false;
            }
            $subtotal = $subtotal + ((int)$service['product_price'] * $item['qty']);
        }

        if(!empty($errAll)){
            $continueCheckOut = false;
        }
        unset($address['description']);
        $result['id_user_address'] = $address['id_user_address'];
        $result['notes'] = (empty($post['notes']) ? $address['description']:$post['notes']);
        $result['preference_hair_stylist'] = $post['preference_hair_stylist'];
        $result['id_user_hair_stylist'] = $idHs;
        $result['booking_date'] = $post['booking_date'];
        $result['booking_time'] = $post['booking_time'];
        if($bookNow){
            $result['booking_time_user'] = $post['booking_time'];
            $result['booking_time'] = 'Sekarang';
        }
        $result['booking_date_display'] = MyHelper::dateFormatInd($post['booking_date'].' '.$post['booking_time'], true, true);
        $result['address'] = $address;
        $result['item_service'] = $itemService;
        $result['subtotal'] = $subtotal;
        $result['currency'] = 'Rp';
        $result['complete_profile'] = (empty($user->complete_profile) ?false:true);
        $result['continue_checkout'] = $continueCheckOut;
        $result['messages_all'] = (empty($errAll)? null:implode(".", array_unique($errAll)));
        return MyHelper::checkGet($result);
    }

    public function mergeService($items){
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        foreach ($items as $item) {
            $new_item = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $item['id_brand'],
                "id_product" => $item['id_product'],
                "product_code" => $item['product_code'],
                "product_name" => $item['product_name'],
                "product_price" => $item['product_price'],
                "error_msg" => $item['error_msg']??""
            ];
            $pos = array_search($new_item, $new_items);
            if($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['qty'];
                $id_custom[] = $item['id_custom']??0;
            } else {
                $item_qtys[$pos] += $item['qty'];
            }
        }
        // update qty
        foreach ($new_items as $key => &$value) {
            $value['qty'] = $item_qtys[$key];
            $value['id_custom'] = $id_custom[$key];
        }

        return $new_items;
    }

    public function check(Request $request) {
        $post = $request->json()->all();

        if(empty($post['item_service'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item Service can not be empty']
            ]);
        }

        if(!empty($request->user()->id)){
            $user = User::where('id', $request->user()->id)->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
        }

        if($post['preference_hair_stylist'] == 'favorite' && empty($post['id_user_hair_stylist'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User hair stylist can not be empty']
            ]);
        }

        $bookNow = false;
        if(strtolower($post['booking_time']) == 'sekarang'){
            $bookNow = true;
            $post['booking_time'] = date('H:i', strtotime("+2 minutes", strtotime($post['booking_time_user'])));
        }

        $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
        if(empty($address)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address user not found']
            ]);
        }

        $post['latitude'] = $address['latitude'];
        $post['longitude'] = $address['longitude'];

        $errAll = [];
        $itemService = [];
        $arrProccessingTime = [];
        $continueCheckOut = true;

        //process get time start and end
        foreach ($post['item_service']??[] as $key=>$item){
            $processingTime = Product::where('products.id_product', $item['id_product'])->first()['processing_time_service']??0;
            $arrProccessingTime[] = $processingTime * $item['qty'];
        }

        $post['sum_time'] = array_sum($arrProccessingTime);
        $checkHS = $this->checkAvailableHS($post, [], $user);
        $idHs = $checkHS['id_user_hair_stylist']??null;
        $errAll = array_merge($errAll, $checkHS['error_all']??[]);

        $post['item_service'] = $this->mergeService($post['item_service']);
        $outlet = Outlet::where('outlet_code', '00000')->first();
        foreach ($post['item_service']??[] as $key=>$item){
            $err = [];
            $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                ->leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                ->where('products.id_product', $item['id_product'])
                ->select('products.*', 'product_global_price as product_price', 'brand_product.id_brand')
                ->with('product_service_use')
                ->first();

            if(empty($service)){
                $err[] = 'Service tidak tersedia';
                $errAll[] = 'Service tidak tersedia';
                unset($item[$key]);
                continue;
            }

            if(!empty($idHs) && $post['preference_hair_stylist'] == 'favorite'){
                $hs = UserHairStylist::where('id_user_hair_stylist', $idHs)->where('user_hair_stylist_status', 'Active')->first();
                if(empty($hs)){
                    $err[] = "Outlet hair stylist not found";
                    $errAll[] = 'Outlet hair stylist not found';
                    unset($item[$key]);
                    continue;
                }

                if(!empty($service['product_service_use'])){
                    $getProductUse = ProductServiceUse::join('product_detail', 'product_detail.id_product', 'product_service_use.id_product')
                        ->where('product_service_use.id_product_service', $service['id_product'])
                        ->where('product_detail.id_outlet', $outlet['id_outlet'])->get()->toArray();
                    if(count($service['product_service_use']) != count($getProductUse)){
                        $err[] = 'Stok habis';
                        $errAll[] = 'Stok habis';
                        unset($item[$key]);
                        continue;
                    }

                    foreach ($getProductUse as $stock){
                        $use = $stock['quantity_use'] * $item['qty'];
                        if($use > $stock['product_detail_stock_service']){
                            $err[] = 'Stok habis';
                            $errAll[] = 'Stok habis';
                            unset($item[$key]);
                            continue;
                        }
                    }
                }

                $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
                $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                    $err[] = 'Service tidak tersedia';
                    $errAll[] = 'Service tidak tersedia';
                    unset($item[$key]);
                    continue;
                }

                if(empty($service['product_price'])){
                    $err[] = 'Service tidak tersedia';
                    $errAll[] = 'Service tidak tersedia';
                    unset($item[$key]);
                    continue;
                }
            }

            $itemService[$key] = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $service['id_brand'],
                "id_product" => $service['id_product'],
                "product_code" => $service['product_code'],
                "product_name" => $service['product_name'],
                "product_price" => (int)$service['product_price'],
                "subtotal" => (int)$service['product_price'] * $item['qty'],
                "qty" => $item['qty'],
                "error_msg" => (empty($err)? null:implode(".", array_unique($err)))
            ];

            if(!empty($err)){
                $continueCheckOut = false;
            }
        }

        $post['item_service'] = $itemService;
        $grandTotal = app($this->setting_trx)->grandTotal();
        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Product Service not found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Service Not Found with product '.$post['sub']->original['product']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Service Not Valid with product '.$post['sub']->original['product']];
                            }
                        }
                    }

                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['subtotal'] = array_sum($post['sub']['subtotal']);
            } elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);

                if (isset($post['tax']->original['messages'])) {
                    $mes = $post['tax']->original['messages'];

                    if ($post['tax']->original['messages'] == ['Product Service not found']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Service Not Found with product '.$post['tax']->original['product']];
                        }
                    }

                    if ($post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Service Not Valid with product '.$post['tax']->original['product']];
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }
            }
            else {
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

        unset($address['description']);
        $result['customer'] = [
            "name" => $user['name'],
            "email" => $user['email'],
            "domicile" => $user['city_name'],
            "birthdate" => date('Y-m-d', strtotime($user['birthday'])),
            "gender" => $user['gender'],
            "address" => $address
        ];

        if(!empty($errAll)){
            $continueCheckOut = false;
        }
        $result['id_user_address'] = $address['id_user_address'];
        $result['notes'] = (empty($post['notes']) ? $address['description']:$post['notes']);
        $result['preference_hair_stylist'] = $post['preference_hair_stylist'];
        $result['id_user_hair_stylist'] = $idHs;
        $result['booking_date'] = $post['booking_date'];
        $result['booking_time'] = $post['booking_time'];
        if($bookNow){
            $result['booking_time_user'] = $post['booking_time'];
            $result['booking_time'] = 'Sekarang';
        }
        $result['booking_date_display'] = MyHelper::dateFormatInd($post['booking_date'].' '.$post['booking_time'], true, true);
        $result['item_service'] = array_values($itemService);
        $result['subtotal'] = $post['subtotal'];
        $result['tax'] = (int) $post['tax'];
        $result['grandtotal'] = (int)$result['subtotal'] + (int)$post['tax'] ;
        $balance = app($this->balance)->balanceNow($user->id);
        $result['points'] = (int) $balance;
        $result['total_payment'] = $result['grandtotal'];

        $earnedPoint = app($this->online_trx)->countTranscationPoint($post, $user);
        $cashback = $earnedPoint['cashback'] ?? 0;
        if ($cashback) {
            $result['point_earned'] = [
                'value' => MyHelper::requestNumber($cashback, '_CURRENCY'),
                'text' => MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
            ];
        }

        $result['currency'] = 'Rp';
        $result['complete_profile'] = (empty($user->complete_profile) ?false:true);
        $result['continue_checkout'] = $continueCheckOut;
        $result['messages_all'] = (empty($errAll)? null:implode(".", array_unique($errAll)));
        return MyHelper::checkGet($result);
    }

    public function newTransactionHomeService(NewTransaction $request) {
        $post = $request->json()->all();

        if(empty($post['item_service'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item Service can not be empty']
            ]);
        }

        if(!empty($request->user()->id)){
            $user = User::where('id', $request->user()->id)->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
        }

        if($post['preference_hair_stylist'] == 'favorite' && empty($post['id_user_hair_stylist'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User hair stylist can not be empty']
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

        $bookNow = false;
        if(strtolower($post['booking_time']) == 'sekarang'){
            $bookNow = true;
            $post['booking_time'] = date('H:i', strtotime("+2 minutes", strtotime($post['booking_time_user'])));
        }

        $post['latitude'] = $address['latitude'];
        $post['longitude'] = $address['longitude'];

        $itemService = [];
        $arrProccessingTime = [];

        //process get time start and end
        foreach ($post['item_service']??[] as $key=>$item){
            $processingTime = Product::where('products.id_product', $item['id_product'])->first()['processing_time_service']??0;
            $arrProccessingTime[] = $processingTime * $item['qty'];
        }

        $post['sum_time'] = array_sum($arrProccessingTime);
        $checkHS = $this->checkAvailableHS($post, [], $user);
        if(!empty($checkHS['error_all'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => [(empty($checkHS['error_all'])? null:implode(".", array_unique($checkHS['error_all'])))]
            ]);
        }
        $idHs = $checkHS['id_user_hair_stylist'];
        $arrHs = $checkHS['all_id_hs'];

        $post['item_service'] = $this->mergeService($post['item_service']);
        $errItem = [];
        $post['id_outlet'] = null;
        $outlet = Outlet::where('outlet_code', '00000')->first();
        foreach ($post['item_service']??[] as $key=>$item){
            $detailStock = [];
            $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                ->leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                ->where('products.id_product', $item['id_product'])
                ->select('products.*', 'product_global_price as product_price', 'brand_product.id_brand')
                ->with('product_service_use')
                ->first();

            if(empty($service)){
                $errItem[] = 'Service tidak tersedia';
            }

            if(!empty($idHs) && $post['preference_hair_stylist'] == 'favorite'){
                $hs = UserHairStylist::where('id_user_hair_stylist', $idHs)->where('user_hair_stylist_status', 'Active')->first();
                if(empty($hs)){
                    $errItem[] = "Outlet hair stylist not found";
                }

                $post['id_outlet'] = $outlet['id_outlet'];
                if(!empty($service['product_service_use'])){
                    $getProductUse = ProductServiceUse::join('product_detail', 'product_detail.id_product', 'product_service_use.id_product')
                        ->where('product_service_use.id_product_service', $service['id_product'])
                        ->where('product_detail.id_outlet', $outlet['id_outlet'])->get()->toArray();
                    if(count($service['product_service_use']) != count($getProductUse)){
                        $errItem[] = 'Stok habis';
                    }

                    foreach ($getProductUse as $stock){
                        $use = $stock['quantity_use'] * $item['qty'];
                        if($use > $stock['product_detail_stock_service']){
                            $errItem[] = 'Stok habis';
                            break;
                        }

                        $detailStock[] = [
                            'id_product' => $stock['id_product'],
                            'quantity_use' => $use,
                        ];
                    }
                }

                $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
                $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                    $errItem[] = 'Service tidak tersedia';
                }

                if(empty($service['product_price'])){
                    $errItem[] = 'Service tidak tersedia';
                }
            }

            $itemService[$key] = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $service['id_brand'],
                "id_product" => $service['id_product'],
                "product_code" => $service['product_code'],
                "product_name" => $service['product_name'],
                "product_price" => (int)$service['product_price'],
                "subtotal" => (int)$service['product_price'] * $item['qty'],
                "qty" => $item['qty'],
                "detail_stock" => $detailStock
            ];
        }

        if(!empty($errItem)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => [(empty($errItem)? null:implode(".", array_unique($errItem)))]
            ]);
        }

        $post['item_service'] = $itemService;
        $grandTotal = app($this->setting_trx)->grandTotal();
        foreach ($grandTotal as $keyTotal => $valueTotal) {
            if ($valueTotal == 'subtotal') {
                $post['sub'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                if (gettype($post['sub']) != 'array') {
                    $mes = ['Data Not Valid'];

                    if (isset($post['sub']->original['messages'])) {
                        $mes = $post['sub']->original['messages'];

                        if ($post['sub']->original['messages'] == ['Product Service not found']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Service Not Found with product '.$post['sub']->original['product']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Service Not Valid with product '.$post['sub']->original['product']];
                            }
                        }
                    }

                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['subtotal'] = array_sum($post['sub']['subtotal']);
            } elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);

                if (isset($post['tax']->original['messages'])) {
                    $mes = $post['tax']->original['messages'];

                    if ($post['tax']->original['messages'] == ['Product Service not found']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Service Not Found with product '.$post['tax']->original['product']];
                        }
                    }

                    if ($post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                        if (isset($post['tax']->original['product'])) {
                            $mes = ['Price Service Not Valid with product '.$post['tax']->original['product']];
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }
            }
            else {
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

        DB::beginTransaction();
        UserFeedbackLog::where('id_user',$request->user()->id)->delete();
        $id=$request->user()->id;
        $transaction = [
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'transaction_date'            => date('Y-m-d H:i:s'),
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_gross'  		  => $post['subtotal'],
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => (int)$post['subtotal'] + (int)$post['tax'],
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

        $receipt = config('configs.PREFIX_TRANSACTION_NUMBER').'-'.MyHelper::createrandom(4,'Angka').time().substr($insertTransaction['id_outlet'], 0, 4);
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


        $createHomeService = TransactionHomeService::create([
            'id_transaction' => $insertTransaction['id_transaction'],
            'id_user_address' => $address['id_user_address'],
            'id_user_hair_stylist' => $idHs,
            'preference_hair_stylist' => $post['preference_hair_stylist'],
            'status' => null,
            'schedule_date' => date('Y-m-d', strtotime($post['booking_date'])),
            'schedule_set_time' => ($bookNow? 'right now':'set time'),
            'schedule_time' => date('H:i:s', strtotime($post['booking_time'])),
            'destination_name' => $user['name'],
            'destination_phone' => $user['phone'],
            'destination_address' => $address['address'],
            'destination_short_address' => $address['short_address'],
            'destination_address_name' => $address['name'],
            'destination_note' => (empty($post['notes']) ? $address['description']:$post['notes']),
            'destination_latitude' => $address['latitude'],
            'destination_longitude' => $address['longitude'],
            'counter_finding_hair_stylist' => (!empty($idHs) ? 1 : 0)
        ]);

        if (!$createHomeService) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Home Service Failed']
            ]);
        }

        $userTrxProduct = [];
        foreach ($post['item_service'] as $itemProduct){

            $dataProduct = [
                'id_transaction'               => $insertTransaction['id_transaction'],
                'id_product'                   => $itemProduct['id_product'],
                'type'                         => 'Service',
                'id_outlet'                   => $insertTransaction['id_outlet'],
                'id_brand'                     => $itemProduct['id_brand'],
                'id_user'                      => $insertTransaction['id_user'],
                'transaction_product_qty'      => $itemProduct['qty'],
                'transaction_product_price'    => $itemProduct['product_price'],
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

            $trx_product = TransactionProduct::create($dataProduct);
            if (!$trx_product) {
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'messages'  => ['Insert Product Service Transaction Failed']
                ];
            }

            $insertProductUse = [];
            foreach ($itemProduct['detail_stock'] as $stock){
                $insertProductUse[] = [
                    'id_transaction' => $insertTransaction['id_transaction'],
                    'id_transaction_product' => $trx_product['id_transaction_product'],
                    'id_product' => $stock['id_product'],
                    'quantity_use' => $stock['quantity_use'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            if(!empty($insertProductUse)){
                $save = TransactionProductServiceUse::insert($insertProductUse);
                if(!$save){
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Insert Data Product Service Use Transaction Failed']
                    ];
                }
            }

            $dataUserTrxProduct = [
                'id_user'       => $insertTransaction['id_user'],
                'id_product'    => $itemProduct['id_product'],
                'product_qty'   => 1,
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

        if($post['preference_hair_stylist'] == 'favorite'){
            app($this->online_trx)->bookHS($insertTransaction['id_transaction']);
            $this->bookProductServiceStockHM($insertTransaction['id_transaction']);
        }

        DB::commit();
        if(!empty($arrHs)){
            FindingHairStylistHomeService::dispatch(['id_transaction' => $insertTransaction['id_transaction'], 'id_transaction_home_service' => $createHomeService['id_transaction_home_service'],'arr_id_hs' => $arrHs])->allOnConnection('findinghairstylistqueue');
        }

        return response()->json([
            'status'   => 'success',
            'result'   => $insertTransaction
        ]);
    }

    function checkAvailableHS($post, $rejectHS = [], $user = []){
        $userTimeZone = (empty($user['user_time_zone_utc']) ? 7 : $user['user_time_zone_utc']);
        $diffTimeZone = $userTimeZone - 7;
        $currentDate = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d H:i:s', strtotime("+".$diffTimeZone." hour", strtotime($currentDate)));
        $bookDate = date('Y-m-d', strtotime($post['booking_date']));
        $bookTime = date('H:i:s', strtotime($post['booking_time']));
        $bookDateTime = date('Y-m-d H:i:s', strtotime($bookDate.' '.$bookTime));

        if(strtotime($currentDate) > strtotime($bookDateTime)){
            $errAll[] = "Waktu pemesanan Anda tidak valid";
        }
        $startTime = $bookTime;
        $endTime = date('H:i', strtotime("+".$post['sum_time']." minutes", strtotime($startTime)));
        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];
        $bookDay = $day[date('D', strtotime($bookDate))];
        $maximumRadius = (int)(Setting::where('key', 'home_service_hs_maximum_radius')->first()['value']??25);
        if($post['preference_hair_stylist'] == 'favorite'){
            $hs = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->where('user_hair_stylist_status', 'Active')->first();
            if(empty($hs)){
                $errAll[] = "Hair stylist tidak ditemukan";
            }

            if(empty($hs['latitude']) && empty($hs['longitude'])){
                $errAll[] = "Hair stylist tidak aktif";
            }
            $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $hs['latitude'], $hs['longitude'], "K");

            if($distance <= 0 || $distance > $maximumRadius){
                $errAll[] = "Hair stylist jauh dari lokasi Anda";
            }

            if($bookDate == date('Y-m-d') && $hs['home_service_status'] == 0){
                $errAll[] = "Hair stylist tidak available";
            }

            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                    ->whereNotNull('approve_at')->where('id_user_hair_stylist', $post['id_user_hair_stylist'])
                    ->whereDate('date', $bookDate)
                    ->first()['shift']??'';
            if(!empty($shift)){
                $idOutletSchedule = OutletSchedule::where('id_outlet', $hs['id_outlet'])
                        ->where('day', $bookDay)->first()['id_outlet_schedule']??null;
                $getTimeShift = app($this->product)->getTimeShift(strtolower($shift),$hs['id_outlet'], $idOutletSchedule);
                if(!empty($getTimeShift['end'])){
                    $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
                    if(strtotime($shiftTimeEnd) > strtotime($bookTime)){
                        $errAll[] = "Hair stylist tidak tersedian silahkan ubah tanggal pemesanan";
                    }
                }
            }

            $hsNotAvailable = HairstylistNotAvailable::where('booking_date', $bookDate)
                ->where('booking_time', '>=',$startTime)
                ->where('booking_time', '<=',$endTime)
                ->where('id_user_hair_stylist', $post['id_user_hair_stylist'])
                ->first();

            if(!empty($hsNotAvailable)){
                $errAll[] = "Hair stylist tidak tersedian silahkan ubah tanggal pemesanan";
            }

            $idHs = $post['id_user_hair_stylist'];
        }else{
            $arrIDHs = [];
            $hsNotAvailable = HairstylistNotAvailable::where('booking_date', $bookDate)
                ->where('booking_time', '>=',$startTime)
                ->where('booking_time', '<=',$endTime)
                ->pluck('id_user_hair_stylist')->toArray();

            $listHs = UserHairStylist::where('user_hair_stylist_status', 'Active');

            if($post['preference_hair_stylist'] !== 'all'){
                $listHs = $listHs->where('gender', $post['preference_hair_stylist']);
            }

            $hsNotAvailable = array_unique(array_merge($hsNotAvailable, $rejectHS));
            $listHs = $listHs->whereNotIn('id_user_hair_stylist', $hsNotAvailable)->get()->toArray();
            foreach ($listHs as $val){
                if(empty($val['latitude']) && empty($val['longitude'])){
                    continue;
                }

                $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $val['latitude'], $val['longitude'], "K");
                if($distance > $maximumRadius){
                    continue;
                }

                if($bookDate == date('Y-m-d') && $val['home_service_status'] == 0){
                    continue;
                }

                //check schedule hs
                $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                        ->whereNotNull('approve_at')->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                        ->whereDate('date', $bookDate)
                        ->first()['shift']??'';
                if(!empty($shift)){
                    $idOutletSchedule = OutletSchedule::where('id_outlet', $val['id_outlet'])
                            ->where('day', $bookDay)->first()['id_outlet_schedule']??null;
                    $getTimeShift = app($this->product)->getTimeShift(strtolower($shift),$val['id_outlet'], $idOutletSchedule);
                    if(!empty($getTimeShift['end'])){
                        $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
                        if(strtotime($shiftTimeEnd) > strtotime($bookTime)){
                            continue;
                        }
                    }
                }

                $arrIDHs[] = [
                    'id_user_hair_stylist' => $val['id_user_hair_stylist'],
                    'distance' => $distance
                ];
            }

            if(empty($arrIDHs)){
                $errAll[] = "Tidak ada hair stylist yang available didekat Anda diwaktu yang Anda pilih";
            }else{
                usort($arrIDHs, function($a, $b) {
                    return $a['distance'] > $b['distance'];
                });

                $arrIdHs = array_column($arrIDHs, 'id_user_hair_stylist');
            }
        }

        return [
            'id_user_hair_stylist' => $idHs??null,
            'all_id_hs' => $arrIdHs??[],
            'error_all' => $errAll??[]
        ];
    }

    function bookProductServiceStockHM($id_transaction){
        $getAllProduct = TransactionProduct::where('id_transaction', $id_transaction)->get()->toArray();

        foreach ($getAllProduct as $stock){
            $getProduct = TransactionProductServiceUse::where('id_transaction_product', $stock['id_transaction_product'])->get()->toArray();
            foreach ($getProduct as $p){
                $productStock = ProductDetail::where(['id_product' => $p['id_product'], 'id_outlet' => $stock['id_outlet']])->first();
                $currentStock = $productStock['product_detail_stock_item'];
                $currentStockService = $productStock['product_detail_stock_service'];
                $updateDetail = $productStock->update(['product_detail_stock_service' => $currentStockService - $p['quantity_use']]);
                if(!$updateDetail){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Gagal memperbarui stok']
                    ]);
                }
                ProductStockLog::create([
                    'id_product' => $p['id_product'],
                    'id_transaction' => $stock['id_transaction'],
                    'stock_service' => -$p['quantity_use'],
                    'stock_item_before' => $currentStock,
                    'stock_service_before' => $currentStockService,
                    'stock_item_after' => $currentStock,
                    'stock_service_after' => $currentStockService - $p['quantity_use']
                ]);
            }
        }

        return $updateDetail??true;
    }

    function cancelBookProductServiceStockHM($id_transaction){
        $getAllProduct = TransactionProduct::where('id_transaction', $id_transaction)->get()->toArray();

        foreach ($getAllProduct as $stock){
            $getProduct = TransactionProductServiceUse::where('id_transaction_product', $stock['id_transaction_product'])->get()->toArray();
            foreach ($getProduct as $p){
                $productStock = ProductDetail::where(['id_product' => $p['id_product'], 'id_outlet' => $stock['id_outlet']])->first();
                $currentStock = $productStock['product_detail_stock_item'];
                $currentStockService = $productStock['product_detail_stock_service'];
                $updateDetail = $productStock->update(['product_detail_stock_service' => $currentStockService + $p['quantity_use']]);
                if(!$updateDetail){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Gagal memperbarui stok']
                    ]);
                }
                ProductStockLog::create([
                    'id_product' => $p['id_product'],
                    'id_transaction' => $stock['id_transaction'],
                    'stock_service' => $p['quantity_use'],
                    'stock_item_before' => $currentStock,
                    'stock_service_before' => $currentStockService,
                    'stock_item_after' => $currentStock,
                    'stock_service_after' => $currentStockService + $p['quantity_use']
                ]);
            }
        }

        return $updateDetail??true;
    }

    public function listHomeService(Request $request)
    {
        $list = Transaction::where('transaction_from', 'home-service')
            ->join('transaction_home_services','transactions.id_transaction', 'transaction_home_services.id_transaction')
            ->join('users','transactions.id_user','=','users.id')
            ->with('user')
            ->select(
                'transaction_home_services.*',
                'users.*',
                'transactions.*'
            )
            ->groupBy('transactions.id_transaction');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->count();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'id_transaction',
                'transaction_date',
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
        } else {
            $list = $list->get();
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
                'transaction_grandtotal',
                'transaction_payment_status',
                'status'
            ];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        if($col_name == 'status' && $rul['parameter'] == 'Waiting Complete Payment'){
                            $model2->whereNull('status');
                        }else{
                            $model2->$where($col_name, $rul['operator'], $rul['parameter']);
                        }
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
        });

        if ($rules = $new_rule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $model->where(\DB::raw('DATE(transaction_date)'), $rul['operator'], $rul['parameter']);
            }
        }
    }

    public function detailTransaction(Request $request)
    {
        if ($request->json('transaction_receipt_number') !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $request->json('transaction_receipt_number')])->first();
            if($trx) {
                $id = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id = $request->json('id_transaction');
        }

        $trx = Transaction::where(['transactions.id_transaction' => $id])
            ->join('transaction_home_services','transaction_home_services.id_transaction','=','transactions.id_transaction')
            ->leftJoin('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','=','transaction_home_services.id_user_hair_stylist')
            ->leftJoin('outlets', 'outlets.id_outlet', 'user_hair_stylist.id_outlet')
            ->first();

        if(!$trx){
            return MyHelper::checkGet($trx);
        }

        $trxPayment = $this->transactionPayment($trx);
        $trx['payment'] = $trxPayment['payment'];

        $trx->load('user');
        $result = [
            'id_transaction'                => $trx['id_transaction'],
            'transaction_receipt_number'    => $trx['transaction_receipt_number'],
            'receipt_qrcode' 				=> 'https://chart.googleapis.com/chart?chl=' . $trx['transaction_receipt_number'] . '&chs=250x250&cht=qr&chld=H%7C0',
            'transaction_date'              => date('d M Y H:i', strtotime($trx['transaction_date'])),
            'transaction_grandtotal'        => MyHelper::requestNumber($trx['transaction_grandtotal'],'_CURRENCY'),
            'transaction_subtotal'          => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY'),
            'transaction_discount'          => MyHelper::requestNumber($trx['transaction_discount'],'_CURRENCY'),
            'transaction_cashback_earned'   => MyHelper::requestNumber($trx['transaction_cashback_earned'],'_POINT'),
            'trasaction_payment_type'       => $trx['trasaction_payment_type'],
            'trasaction_type'               => $trx['trasaction_type'],
            'transaction_payment_status'    => $trx['transaction_payment_status'],
            'booking_date'                  => $trx['schedule_date'],
            'booking_time'                  => $trx['schedule_time'],
            'destination_name'              => $trx['destination_name'],
            'destination_phone'              => $trx['destination_phone'],
            'destination_address'            => $trx['destination_address'],
            'destination_short_address'      => $trx['destination_short_address'],
            'destination_address_name'       => $trx['destination_address_name'],
            'destination_note'              => $trx['destination_note'],
            'hair_stylist_status'           => $trx['status'],
            'hair_stylist_name'             => $trx['fullname'],
            'hair_stylist_outlet_name'      => $trx['outlet_name'],
            'continue_payment'              => $trxPayment['continue_payment'],
            'payment_gateway'               => $trxPayment['payment_gateway'],
            'payment_type'                  => $trxPayment['payment_type'],
            'payment_redirect_url'          => $trxPayment['payment_redirect_url'],
            'payment_redirect_url_app'      => $trxPayment['payment_redirect_url_app'],
            'payment_token'                 => $trxPayment['payment_token'],
            'total_payment'                 => (int) $trxPayment['total_payment'],
            'timer_shopeepay'               => $trxPayment['timer_shopeepay'],
            'message_timeout_shopeepay'     => $trxPayment['message_timeout_shopeepay'],
            'user'							=> [
                'phone' => $trx['user']['phone'],
                'name' 	=> $trx['user']['name'],
                'email' => $trx['user']['email']
            ],

        ];

        $trxServices = TransactionProduct::where('id_transaction', $trx['id_transaction'])
            ->with(['product'])->get()->toArray();
        $totalItem = 0;
        foreach ($trxServices as $ts){
            $totalItem += $ts['transaction_product_qty'];
        }
        $result['product_service'] = $trxServices;
        $trx['transaction_item_service_total'] = $totalItem;

        $lastLog = LogInvalidTransaction::where('id_transaction', $trx['id_transaction'])->orderBy('updated_at', 'desc')->first();

        $result['image_invalid_flag'] = NULL;
        if(!empty($trx['image_invalid_flag'])){
            $result['image_invalid_flag'] =  config('url.storage_url_api').$trx['image_invalid_flag'];
        }

        $result['transaction_flag_invalid'] =  $trx['transaction_flag_invalid'];
        $result['flag_reason'] =  $lastLog['reason'] ?? '';
        $result['payment_detail'] = $this->transactionPaymentDetail($trx);

        if(!isset($trx['payment'])){
            $result['transaction_payment'] = null;
        }else{
            foreach ($trx['payment'] as $key => $value) {
                if ($value['name'] == 'Balance') {
                    $result['transaction_payment'][$key] = [
                        'name'      => (env('POINT_NAME')) ? env('POINT_NAME') : $value['name'],
                        'is_balance'=> 1,
                        'amount'    => MyHelper::requestNumber($value['amount'],'_POINT')
                    ];
                } else {
                    $result['transaction_payment'][$key] = [
                        'name'      => $value['name'],
                        'amount'    => MyHelper::requestNumber($value['amount'],'_CURRENCY')
                    ];
                }
            }
        }

        return MyHelper::checkGet($result);
    }

    public function transactionPayment(Transaction $trx)
    {
        $trx = clone $trx;
        $trx = $trx->toArray();
        $redirectUrlApp = "";
        $redirectUrl = "";
        $tokenPayment = "";
        $continuePayment = false;
        $totalPayment = 0;
        $shopeeTimer = 0;
        $shopeeMessage = "";
        $paymentType = "";
        $paymentGateway = "";
        switch ($trx['trasaction_payment_type']) {
            case 'Balance':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get()->toArray();
                if ($multiPayment) {
                    foreach ($multiPayment as $keyMP => $mp) {
                        switch ($mp['type']) {
                            case 'Balance':
                                $log = LogBalance::where('id_reference', $mp['id_transaction'])->where('source', 'Online Transaction')->first();
                                if ($log['balance'] < 0) {
                                    $trx['balance'] = $log['balance'];
                                    $trx['check'] = 'tidak topup';
                                } else {
                                    $trx['balance'] = $trx['transaction_grandtotal'] - $log['balance'];
                                    $trx['check'] = 'topup';
                                }
                                $trx['payment'][] = [
                                    'name'      => 'Balance',
                                    'amount'    => $trx['balance']
                                ];
                                break;
                            case 'Manual':
                                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $trx['id_transaction'])->first();
                                $trx['payment'] = $payment;
                                $trx['payment'][] = [
                                    'name'      => 'Cash',
                                    'amount'    => $payment['payment_nominal']
                                ];
                                break;
                            case 'Midtrans':
                                $payMidtrans = TransactionPaymentMidtran::find($mp['id_payment']);
                                $payment['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                                $payment['amount']    = $payMidtrans->gross_amount;
                                $trx['payment'][] = $payment;
                                if($trx['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)) {
                                    $redirectUrl = $payMidtrans->redirect_url;
                                    $tokenPayment = $payMidtrans->token;
                                    $continuePayment =  true;
                                    $totalPayment = $payMidtrans->gross_amount;
                                    $paymentType = strtoupper($payMidtrans->payment_type);
                                    $paymentGateway = 'Midtrans';
                                }
                                break;
                            case 'Ovo':
                                $payment = TransactionPaymentOvo::find($mp['id_payment']);
                                $payment['name']    = 'OVO';
                                $trx['payment'][] = $payment;
                                break;
                            case 'IPay88':
                                $PayIpay = TransactionPaymentIpay88::find($mp['id_payment']);
                                $payment['name']    = $PayIpay->payment_method;
                                $payment['amount']    = $PayIpay->amount / 100;
                                $trx['payment'][] = $payment;
                                if($trx['transaction_payment_status'] == 'Pending'){
                                    $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$trx['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                                    $continuePayment =  true;
                                    $totalPayment = $PayIpay->amount / 100;
                                    $paymentType = strtoupper($PayIpay->payment_method);
                                    $paymentGateway = 'IPay88';
                                }
                                break;
                            case 'Shopeepay':
                                $shopeePay = TransactionPaymentShopeePay::find($mp['id_payment']);
                                $payment['name']    = 'ShopeePay';
                                $payment['amount']  = $shopeePay->amount / 100;
                                $payment['reject']  = $shopeePay->err_reason?:'payment expired';
                                $trx['payment'][]  = $payment;
                                if($trx['transaction_payment_status'] == 'Pending'){
                                    $redirectUrl = $shopeePay->redirect_url_http;
                                    $redirectUrlApp = $shopeePay->redirect_url_app;
                                    $continuePayment =  true;
                                    $totalPayment = $shopeePay->amount / 100;
                                    $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                                    $shopeeMessage ='Sorry, your payment has expired';
                                    $paymentGateway = 'Shopeepay';
                                }
                                break;
                            case 'Offline':
                                $payment = TransactionPaymentOffline::where('id_transaction', $trx['id_transaction'])->get();
                                foreach ($payment as $key => $value) {
                                    $trx['payment'][$key] = [
                                        'name'      => $value['payment_bank'],
                                        'amount'    => $value['payment_amount']
                                    ];
                                }
                                break;
                            default:
                                break;
                        }
                    }
                } else {
                    $log = LogBalance::where('id_reference', $trx['id_transaction'])->first();
                    if ($log['balance'] < 0) {
                        $trx['balance'] = $log['balance'];
                        $trx['check'] = 'tidak topup';
                    } else {
                        $trx['balance'] = $trx['transaction_grandtotal'] - $log['balance'];
                        $trx['check'] = 'topup';
                    }
                    $trx['payment'][] = [
                        'name'      => 'Balance',
                        'amount'    => $trx['balance']
                    ];
                }
                break;
            case 'Manual':
                $payment = TransactionPaymentManual::with('manual_payment_method.manual_payment')->where('id_transaction', $trx['id_transaction'])->first();
                $trx['payment'] = $payment;
                $trx['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['payment_nominal']
                ];
                break;
            case 'Midtrans':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Midtrans'){
                        $payMidtrans = TransactionPaymentMidtran::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = strtoupper(str_replace('_', ' ', $payMidtrans->payment_type)).' '.strtoupper($payMidtrans->bank);
                        $payment[$dataKey]['amount']    = $payMidtrans->gross_amount;
                        if($trx['transaction_payment_status'] == 'Pending' && !empty($payMidtrans->token)){
                            $redirectUrl = $payMidtrans->redirect_url;
                            $tokenPayment = $payMidtrans->token;
                            $continuePayment =  true;
                            $totalPayment = $payMidtrans->gross_amount;
                            $paymentType = strtoupper($payMidtrans->payment_type);
                            $paymentGateway = 'Midtrans';
                        }

                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Ovo':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Ovo'){
                        $payment[$dataKey] = TransactionPaymentOvo::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = 'OVO';
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Ipay88':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'IPay88'){
                        $PayIpay = TransactionPaymentIpay88::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']    = $PayIpay->payment_method;
                        $payment[$dataKey]['amount']    = $PayIpay->amount / 100;

                        if($trx['transaction_payment_status'] == 'Pending'){
                            $redirectUrl = config('url.api_url').'/api/ipay88/pay?type=trx&id_reference='.$trx['id_transaction'].'&payment_id='.$PayIpay->payment_id;
                            $continuePayment =  true;
                            $totalPayment = $PayIpay->amount / 100;
                            $paymentType = strtoupper($PayIpay->payment_method);
                            $paymentGateway = 'Ipay88';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey] = $dataPay;
                        $trx['balance'] = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']          = 'Balance';
                        $payment[$dataKey]['amount']        = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Shopeepay':
                $multiPayment = TransactionMultiplePayment::where('id_transaction', $trx['id_transaction'])->get();
                $payment = [];
                foreach($multiPayment as $dataKey => $dataPay){
                    if($dataPay['type'] == 'Shopeepay'){
                        $payShopee = TransactionPaymentShopeePay::find($dataPay['id_payment']);
                        $payment[$dataKey]['name']      = 'ShopeePay';
                        $payment[$dataKey]['amount']    = $payShopee->amount / 100;
                        $payment[$dataKey]['reject']    = $payShopee->err_reason?:'payment expired';
                        if($trx['transaction_payment_status'] == 'Pending') {
                            $redirectUrl = $payShopee->redirect_url_http;
                            $redirectUrlApp = $payShopee->redirect_url_app;
                            $continuePayment =  true;
                            $totalPayment = $payShopee->amount / 100;
                            $shopeeTimer = (int) MyHelper::setting('shopeepay_validity_period', 'value', 300);
                            $shopeeMessage ='Sorry, your payment has expired';
                            $paymentGateway = 'Shopeepay';
                        }
                    }else{
                        $dataPay = TransactionPaymentBalance::find($dataPay['id_payment']);
                        $payment[$dataKey]              = $dataPay;
                        $trx['balance']                = $dataPay['balance_nominal'];
                        $payment[$dataKey]['name']      = 'Balance';
                        $payment[$dataKey]['amount']    = $dataPay['balance_nominal'];
                    }
                }
                $trx['payment'] = $payment;
                break;
            case 'Offline':
                $payment = TransactionPaymentOffline::where('id_transaction', $trx['id_transaction'])->get();
                foreach ($payment as $key => $value) {
                    $trx['payment'][$key] = [
                        'name'      => $value['payment_bank'],
                        'amount'    => $value['payment_amount']
                    ];
                }
                break;

            case 'Cash':
                $payment = TransactionPaymentCash::where('id_transaction', $trx['id_transaction'])->first();
                $trx['payment'] = [];
                $trx['payment'][] = [
                    'name'      => 'Cash',
                    'amount'    => $payment['cash_nominal']
                ];
                break;

            default:
                break;
        }

        $res = [
            'payment' 					=> $trx['payment'] ?? [],
            'continue_payment'          => $continuePayment,
            'payment_gateway'           => $paymentGateway,
            'payment_type'              => $paymentType,
            'payment_redirect_url'      => $redirectUrl,
            'payment_redirect_url_app'  => $redirectUrlApp,
            'payment_token'             => $tokenPayment,
            'total_payment'             => (int)$totalPayment,
            'timer_shopeepay'           => $shopeeTimer,
            'message_timeout_shopeepay' => $shopeeMessage,
        ];

        return $res;
    }

    public function transactionPaymentDetail(Transaction $trx)
    {
        $trx = clone $trx;
        $trx->load(
            'transaction_vouchers.deals_voucher.deal',
            'promo_campaign_promo_code.promo_campaign',
            'transaction_payment_subscription.subscription_user_voucher',
            'subscription_user_voucher'
    	);

        $paymentDetail = [];
        $totalItem = $trx['transaction_item_service_total'];
        $paymentDetail[] = [
            'name'      => 'Subtotal',
            'desc'      => $totalItem . ' items',
            'amount'    => MyHelper::requestNumber($trx['transaction_subtotal'],'_CURRENCY')
        ];
        $paymentDetail[] = [
            'name'      => 'Tax',
            'desc'      => '10%',
            'amount'    => MyHelper::requestNumber($trx['transaction_tax'],'_CURRENCY')
        ];

        if ($trx['transaction_discount']) {
            $discount = abs($trx['transaction_discount']);
            $p = 0;
            if (!empty($trx['transaction_vouchers'])) {
                foreach ($trx['transaction_vouchers'] as $valueVoc) {
                    $result['promo']['code'][$p++]   = $valueVoc['deals_voucher']['voucher_code'];
                    $paymentDetail[] = [
                        'name'          => 'Diskon',
                        'desc'          => 'Promo',
                        "is_discount"   => 1,
                        'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                    ];
                }
            }

            if (!empty($trx['promo_campaign_promo_code'])) {
                $result['promo']['code'][$p++]   = $trx['promo_campaign_promo_code']['promo_code'];
                $paymentDetail[] = [
                    'name'          => 'Diskon',
                    'desc'          => 'Promo',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                ];
            }

            if (!empty($trx['id_subscription_user_voucher']) && !empty($trx['transaction_discount'])) {
                $paymentDetail[] = [
                    'name'          => 'Subscription',
                    'desc'          => 'Diskon',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($discount,'_CURRENCY')
                ];
            }
        }

        return $paymentDetail;
    }
}
