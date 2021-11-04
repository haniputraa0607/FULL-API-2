<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\UserAddress;
use App\Jobs\ExportFranchiseJob;
use App\Jobs\FindingHSHomeService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Product\Entities\ProductDetail;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use App\Http\Models\TransactionPayment;
use App\Http\Models\User;
use App\Http\Models\Product;
use App\Http\Models\StockLog;
use Modules\ProductService\Entities\ProductServiceUse;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionHomeServiceStatusUpdate;
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

        $address = UserAddress::where('id_user', $user->id)->where('id_user_address', $post['id_user_address'])->first();
        if(empty($address)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address user not found']
            ]);
        }

        $post['latitude'] = $address['latitude'];
        $post['longitude'] = $address['longitude'];

        if($post['preference_hair_stylist'] == 'favorite' && empty($post['id_user_hair_stylist'])){
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User hair stylist can not be empty']
                ]);
            }
        }

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
        $checkHS = $this->checkAvailableHS($post);
        $idHs = $checkHS['id_user_hair_stylist']??null;
        $errAll = array_merge($errAll, $checkHS['error_all']??[]);

        $post['item_service'] = $this->mergeService($post['item_service']);
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
                $outlet = Outlet::where('id_outlet', $hs['id_outlet'])->first();
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
        }

        if(!empty($errAll)){
            $continueCheckOut = false;
        }
        $result['preference_hair_stylist'] = $post['preference_hair_stylist'];
        $result['id_user_hair_stylist'] = $idHs;
        $result['booking_date'] = $post['booking_date'];
        $result['booking_time'] = $post['booking_time'];
        $result['booking_date_display'] = MyHelper::dateFormatInd($post['booking_date'].' '.$post['booking_time'], true, true);
        $result['item_service'] = $itemService;
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
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User hair stylist can not be empty']
                ]);
            }
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
        $checkHS = $this->checkAvailableHS($post);
        $idHs = $checkHS['id_user_hair_stylist']??null;
        $errAll = array_merge($errAll, $checkHS['error_all']??[]);

        $post['item_service'] = $this->mergeService($post['item_service']);
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
                unset($item[$key]);
                continue;
            }

            if(!empty($idHs) && $post['preference_hair_stylist'] == 'favorite'){
                $hs = UserHairStylist::where('id_user_hair_stylist', $idHs)->where('user_hair_stylist_status', 'Active')->first();
                $outlet = Outlet::where('id_outlet', $hs['id_outlet'])->first();
                if(empty($hs)){
                    $err[] = "Outlet hair stylist not found";
                    unset($item[$key]);
                    continue;
                }

                if(!empty($service['product_service_use'])){
                    $getProductUse = ProductServiceUse::join('product_detail', 'product_detail.id_product', 'product_service_use.id_product')
                        ->where('product_service_use.id_product_service', $service['id_product'])
                        ->where('product_detail.id_outlet', $outlet['id_outlet'])->get()->toArray();
                    if(count($service['product_service_use']) != count($getProductUse)){
                        $err[] = 'Stok habis';
                        unset($item[$key]);
                        continue;
                    }

                    foreach ($getProductUse as $stock){
                        $use = $stock['quantity_use'] * $item['qty'];
                        if($use > $stock['product_detail_stock_service']){
                            $err[] = 'Stok habis';
                            unset($item[$key]);
                            continue;
                        }
                    }
                }

                $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
                $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

                if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                    $err[] = 'Service tidak tersedia';
                    unset($item[$key]);
                    continue;
                }

                if(empty($service['product_price'])){
                    $err[] = 'Service tidak tersedia';
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
        $result['preference_hair_stylist'] = $post['preference_hair_stylist'];
        $result['id_user_hair_stylist'] = $idHs;
        $result['booking_date'] = $post['booking_date'];
        $result['booking_time'] = $post['booking_time'];
        $result['booking_date_display'] = MyHelper::dateFormatInd($post['booking_date'].' '.$post['booking_time'], true, true);
        $result['item_service'] = $itemService;
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
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User hair stylist can not be empty']
                ]);
            }
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
        $checkHS = $this->checkAvailableHS($post);
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
                $outlet = Outlet::where('id_outlet', $hs['id_outlet'])->first();
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
            'transaction_from'            => $post['transaction_from']
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


        $createHomeService = TransactionHomeService::create([
            'id_transaction' => $insertTransaction['id_transaction'],
            'id_user_address' => $address['id_user_address'],
            'id_user_hair_stylist' => $idHs,
            'preference_hair_stylist' => $post['preference_hair_stylist'],
            'status' => 'Finding Hair Stylist',
            'schedule_date' => date('Y-m-d', strtotime($post['booking_date'])),
            'schedule_time' => date('H:i:s', strtotime($post['booking_time'])),
            'destination_name' => $user['name'],
            'destination_phone' => $user['phone'],
            'destination_address' => $address['address'],
            'destination_short_address' => $address['short_address'],
            'destination_address_name' => $address['name'],
            'destination_note' => $address['description'],
            'destination_latitude' => $address['latitude'],
            'destination_longitude' => $address['longitude']
        ]);

        if (!$createHomeService) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Home Service Failed']
            ]);
        }

        $insertTrxHMStatusUpdate = TransactionHomeServiceStatusUpdate::create([
            'id_transaction' => $insertTransaction['id_transaction'],
            'status' => 'Finding Hair Stylist'
        ]);
        if (!$insertTrxHMStatusUpdate) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Home Service Status Update Failed']
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

        DB::commit();
        if(!empty($arrHs)){
            FindingHSHomeService::dispatch(['id_transaction' => $insertTransaction['id_transaction'], 'arr_id_hs' => $arrHs])->allOnConnection('finding_hs_queue');
        }

        return response()->json([
            'status'   => 'success',
            'result'   => $insertTransaction
        ]);
    }

    function checkAvailableHS($post){
        $bookDate = date('Y-m-d', strtotime($post['booking_date']));
        $bookTime = date('H:i:s', strtotime($post['booking_time']));
        $currentDate = date('Y-m-d H:i:s');
        $bookDateTime = date('Y-m-d H:i', strtotime($bookDate.' '.$bookTime));
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

            if(empty($val['latitude']) && empty($val['longitude'])){
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

            $listHs = $listHs->whereNotIn('id_user_hair_stylist', $hsNotAvailable)->get()->toArray();
            foreach ($listHs as $val){
                if(empty($val['latitude']) && empty($val['longitude'])){
                    continue;
                }

                $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $val['latitude'], $val['longitude'], "K");
                if($distance <= 0 || $distance > $maximumRadius){
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
                $errAll[] = "Tidak ada hair stylist yang available didekat Anda";
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
}
