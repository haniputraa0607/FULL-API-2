<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\DailyTransactions;
use App\Http\Models\OauthAccessToken;
use App\Jobs\DisburseJob;
use App\Jobs\FraudJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use App\Http\Models\ProductCategory;
use Lcobucci\JWT\Parser;
use Modules\Brand\Entities\Brand;
use Modules\Brand\Entities\BrandProduct;
use App\Http\Models\ProductModifier;
use App\Http\Models\User;
use App\Http\Models\UserAddress;
use App\Http\Models\Outlet;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Http\Models\TransactionProductModifier;
use Modules\Product\Entities\ProductStockLog;
use Modules\ProductBundling\Entities\BundlingOutlet;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use Modules\ProductVariant\Entities\ProductVariantGroupDetail;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;
use Modules\ProductVariant\Entities\TransactionProductVariant;
use App\Http\Models\TransactionShipment;
use App\Http\Models\TransactionPickup;
use App\Http\Models\TransactionPickupGoSend;
use App\Http\Models\TransactionPaymentMidtran;
use App\Http\Models\TransactionAdvanceOrder;
use App\Http\Models\LogPoint;
use App\Http\Models\LogBalance;
use App\Http\Models\ManualPaymentMethod;
use App\Http\Models\UserOutlet;
use App\Http\Models\TransactionSetting;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\SettingFraud\Entities\FraudSetting;
use App\Http\Models\Configs;
use App\Http\Models\Holiday;
use App\Http\Models\OutletToken;
use App\Http\Models\UserLocationDetail;
use App\Http\Models\Deal;
use App\Http\Models\TransactionVoucher;
use App\Http\Models\DealsUser;
use Modules\PromoCampaign\Entities\PromoCampaign;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\PromoCampaignReferral;
use Modules\PromoCampaign\Entities\PromoCampaignReferralTransaction;
use Modules\PromoCampaign\Entities\UserReferralCode;
use Modules\PromoCampaign\Entities\UserPromo;
use Modules\Subscription\Entities\TransactionPaymentSubscription;
use Modules\Subscription\Entities\Subscription;
use Modules\Subscription\Entities\SubscriptionUser;
use Modules\Subscription\Entities\SubscriptionUserVoucher;
use Modules\PromoCampaign\Entities\PromoCampaignReport;

use Modules\Balance\Http\Controllers\NewTopupController;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use Modules\Outlet\Entities\DeliveryOutlet;

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\Request as RequestGuzzle;
use Guzzle\Http\Message\Response as ResponseGuzzle;
use Guzzle\Http\Exception\ServerErrorResponseException;

use Modules\Transaction\Entities\TransactionBundlingProduct;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionOutletService;
use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceUse;
use Modules\UserFeedback\Entities\UserFeedbackLog;

use DB;
use DateTime;
use App\Lib\MyHelper;
use App\Lib\Midtrans;
use App\Lib\GoSend;
use App\Lib\WeHelpYou;
use App\Lib\PushNotificationHelper;

use Modules\Transaction\Http\Requests\Transaction\NewTransaction;
use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;
use Modules\Transaction\Http\Requests\CheckTransaction;
use Modules\ProductVariant\Entities\ProductVariant;
use App\Http\Models\TransactionMultiplePayment;
use Modules\ProductBundling\Entities\Bundling;
use Modules\Transaction\Entities\HairstylistNotAvailable;

class ApiOnlineTransaction extends Controller
{
    public $saveImage = "img/payment/manual/";

    function __construct() {
        ini_set('max_execution_time', 0);
        date_default_timezone_set('Asia/Jakarta');

        $this->balance       = "Modules\Balance\Http\Controllers\BalanceController";
        $this->membership    = "Modules\Membership\Http\Controllers\ApiMembership";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->transaction   = "Modules\Transaction\Http\Controllers\ApiTransaction";
        $this->notif         = "Modules\Transaction\Http\Controllers\ApiNotification";
        $this->setting_fraud = "Modules\SettingFraud\Http\Controllers\ApiFraud";
        $this->setting_trx   = "Modules\Transaction\Http\Controllers\ApiSettingTransactionV2";
        $this->promo_campaign       = "Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign";
        $this->subscription_use     = "Modules\Subscription\Http\Controllers\ApiSubscriptionUse";
        $this->promo       = "Modules\PromoCampaign\Http\Controllers\ApiPromo";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->plastic       = "Modules\Plastic\Http\Controllers\PlasticController";
        $this->voucher  = "Modules\Deals\Http\Controllers\ApiDealsVoucher";
        $this->subscription  = "Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher";
        $this->bundling      = "Modules\ProductBundling\Http\Controllers\ApiBundlingController";
        $this->product      = "Modules\Product\Http\Controllers\ApiProductController";
    }

    public function newTransaction(NewTransaction $request) {
        $post = $request->json()->all();
        $bearerToken = $request->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        if(empty($post['item']) &&
            empty($post['item_service'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item/Item Service can not be empty']
            ]);
        }

        if(empty($post['outlet_code']) && empty($post['id_outlet'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['ID/Code outlet can not be empty']
            ]);
        }

        if(empty($post['transaction_from'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Parameter transaction_from can not be empty']
            ]);
        }

        if(empty($post['type'])){
            $post['type'] = null;
        }

        $post['item'] = $this->mergeProducts($post['item']??[]);
        if (isset($post['pin']) && strtolower($post['payment_type']) == 'balance') {
            if (!password_verify($post['pin'], $request->user()->password)) {
                return [
                    'status' => 'fail',
                    'messages' => ['Incorrect PIN']
                ];
            }
        }
        if ($post['type'] == 'Delivery Order' && $request->courier == 'gosend') {
        	$post['type'] = 'GO-SEND';
        	$request->type = 'GO-SEND';
        }
        // return $post;
        $totalPrice = 0;
        $totalWeight = 0;
        $totalDiscount = 0;
        $grandTotal = app($this->setting_trx)->grandTotal();
        $order_id = null;
        $id_pickup_go_send = null;
        $promo_code_ref = null;

        if (isset($post['headers'])) {
            unset($post['headers']);
        }
        if($post['type'] == 'Advance Order'){
            $post['id_outlet'] = Setting::where('key','default_outlet')->pluck('value')->first();
        }
        $dataInsertProduct = [];
        $productMidtrans = [];
        $dataDetailProduct = [];
        $userTrxProduct = [];

        if(!empty($post['outlet_code'])){
            $outlet = Outlet::where('outlet_code', $post['outlet_code'])->with('today')->first();
            $post['id_outlet'] = $outlet['id_outlet']??null;
            if (empty($outlet)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet Not Found']
                ]);
            }
        }elseif(isset($post['id_outlet'])){
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->first();
            if (empty($outlet)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet Not Found']
                    ]);
            }
        }else{
            $outlet = optional();
        }

        if($post['type'] == 'Delivery' && !$outlet->delivery_order) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Maaf, Outlet ini tidak support untuk delivery order']
                ]);
        }

        $issetDate = false;
        if (isset($post['transaction_date'])) {
            $issetDate = true;
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }

        //cek outlet active
        if(isset($outlet['outlet_status']) && $outlet['outlet_status'] == 'Inactive'){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet tutup']
            ]);
        }

        //cek outlet holiday
        if($issetDate == false){
            $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                    ->where('id_outlet', $outlet['id_outlet'])->whereDay('date_holidays.date', date('d'))->whereMonth('date_holidays.date', date('m'))->get();
            if(count($holiday) > 0){
                foreach($holiday as $i => $holi){
                    if($holi['yearly'] == '0'){
                        if($holi['date'] == date('Y-m-d')){
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Outlet tutup']
                            ]);
                        }
                    }else{
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Outlet tutup']
                        ]);
                    }
                }
            }

            if($outlet['today']['is_closed'] == '1'){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet tutup']
                ]);
            }

             if($outlet['today']['close'] && $outlet['today']['open']){

                $settingTime = Setting::where('key', 'processing_time')->first();
                if($settingTime && $settingTime->value){
                    // if($outlet['today']['close'] && date('H:i') > date('H:i', strtotime('-'.$settingTime->value.' minutes' ,strtotime($outlet['today']['close'])))){
                    if($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close']))){
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Outlet tutup']
                        ]);
                    }
                }

                //cek outlet open - close hour
                if(($outlet['today']['open'] && date('H:i') < date('H:i', strtotime($outlet['today']['open']))) || ($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close'])))){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Outlet tutup']
                    ]);
                }
            }
        }

        if (isset($post['transaction_payment_status'])) {
            $post['transaction_payment_status'] = $post['transaction_payment_status'];
        } else {
            $post['transaction_payment_status'] = 'Pending';
        }

        if (!isset($post['id_user'])) {
            $id = $request->user()->id;
        } else {
            $id = $post['id_user'];
        }

        $user = User::leftJoin('cities', 'cities.id_city', 'users.id_city')
                ->select('users.*', 'cities.city_name')
                ->with('memberships')->where('id', $id)->first();
        if (empty($user)) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['User Not Found']
            ]);
        }

        if($user['complete_profile'] == 0){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Please complete your profile']
            ]);
        }

        //suspend
        if(isset($user['is_suspended']) && $user['is_suspended'] == '1'){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Akun Anda telah diblokir karena menunjukkan aktivitas mencurigakan. Untuk informasi lebih lanjut harap hubungi customer service kami di hello@example.id']
            ]);
        }

        //check validation email
        if(isset($user['email'])){
            $domain = substr($user['email'], strpos($user['email'], "@") + 1);
            if(!filter_var($user['email'], FILTER_VALIDATE_EMAIL) ||
                checkdnsrr($domain, 'MX') === false){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Alamat email anda tidak valid, silahkan gunakan alamat email yang valid.']
                ]);
            }
        }

        //check data customer
        if(empty($post['customer']) || empty($post['customer']['name'])){
            $post['customer'] = [
                "name" => $user['name'],
                "email" => $user['email'],
                "domicile" => $user['city_name'],
                "birthdate" => date('Y-m-d', strtotime($user['birthday'])),
                "gender" => $user['gender'],
            ];
        }

        $config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->first()->is_active;

        if (count($user['memberships']) > 0) {
            $post['membership_level']    = $user['memberships'][0]['membership_name'];
            $post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
        } else {
            $post['membership_level']    = null;
            $post['membership_promo_id'] = null;
        }

        if ($post['type'] == 'Delivery') {
            $userAddress = UserAddress::where(['id_user' => $id, 'id_user_address' => $post['id_user_address']])->first();

            if (empty($userAddress)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Address Not Found']
                ]);
            }
        }

        $totalDisProduct = 0;
        if($scopeUser == 'apps'){
            // $productDis = $this->countDis($post);
            $productDis = app($this->setting_trx)->discountProduct($post);
            if ($productDis) {
                $totalDisProduct = $productDis;
            }

            // return $totalDiscount;

            // remove bonus item
            $pct = new PromoCampaignTools();
            $post['item'] = $pct->removeBonusItem($post['item']);

            // check promo code and referral
            $promo_error = [];
            $use_referral = false;
            $discount_promo = [];
            $promo_discount = 0;
            $promo_discount_item = 0;
            $promo_discount_bill = 0;
            $promo_source = null;
            $promo_valid = false;
            $promo_type = null;

            if($request->json('promo_code') || $request->json('id_deals_user') || $request->json('id_subscription_user')){
                // change is used flag to 0
                $update_deals 	= DealsUser::where('id_user','=',$request->user()->id)->where('is_used','=',1)->update(['is_used' => 0]);
                $update_subs 	= SubscriptionUser::where('id_user','=',$request->user()->id)->where('is_used','=',1)->update(['is_used' => 0]);
                $removePromo 	= UserPromo::where('id_user',$request->user()->id)->delete();
            }

            if($request->json('promo_code') && !$request->json('id_deals_user')){
                $code=PromoCampaignPromoCode::where('promo_code',$request->promo_code)
                    ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', '=', 'promo_campaign_promo_codes.id_promo_campaign')
                    ->where( function($q){
                        $q->where(function($q2) {
                            $q2->where('code_type', 'Multiple')
                                ->where(function($q3) {
                                    $q3->whereColumn('usage','<','code_limit')
                                        ->orWhere('code_limit',0);
                                });

                        })
                            ->orWhere(function($q2) {
                                $q2->where('code_type','Single')
                                    ->where(function($q3) {
                                        $q3->whereColumn('total_coupon','>','used_code')
                                            ->orWhere('total_coupon',0);
                                    });
                            });
                    })
                    ->first();
                if ($code)
                {
                    $promo_type = $code->promo_type;
                    $post['id_promo_campaign_promo_code'] = $code->id_promo_campaign_promo_code;
                    if ($code->promo_type != 'Discount delivery' && $code->promo_type != 'Discount bill') {
                        if($code->promo_type == "Referral"){
                            $promo_code_ref = $request->json('promo_code');
                            $use_referral = true;
                        }

                        $validate_user=$pct->validateUser($code->id_promo_campaign, $request->user()->id, $request->user()->phone, $request->device_type, $request->device_id, $errore,$code->id_promo_campaign_promo_code);

                        $discount_promo=$pct->validatePromo($request, $code->id_promo_campaign, $request->id_outlet, $post['item'], $errors);

                        if ( !empty($errore) || !empty($errors)) {
                            $errors = array_merge($errore??[], $errors??[]);
                            DB::rollback();
                            return [
                                'status'=>'fail',
                                'messages'=>$errors??['Promo code not valid']
                            ];
                        }

                        $promo_source 	= 'promo_code';
                        $promo_valid 	= true;
                        $promo_discount	= $discount_promo['discount'];
                        $promo_discount_item = abs($promo_discount);
                    }
                    else{
                        $promo_source 	= 'promo_code';
                        $promo_valid 	= true;
                    }
                }
                else
                {
                    return [
                        'status'=>'fail',
                        'messages'=>['Promo code not valid']
                    ];
                }
            }
            elseif($request->json('id_deals_user') && !$request->json('promo_code'))
            {
                $deals = app($this->promo_campaign)->checkVoucher($request->id_deals_user, 1);

                if($deals)
                {
                    $promo_type = $deals->dealVoucher->deals->promo_type;
                    if ($promo_type != 'Discount delivery' && $promo_type != 'Discount bill') {
                        $discount_promo=$pct->validatePromo($request, $deals->dealVoucher->id_deals, $request->id_outlet, $post['item'], $errors, 'deals');

                        if ( !empty($errors) ) {
                            DB::rollback();
                            return [
                                'status'=>'fail',
                                'messages'=> $errors??['Voucher is not valid']
                            ];
                        }

                        $promo_source = 'voucher_online';
                        $promo_valid = true;
                        $promo_discount = $discount_promo['discount'];
                        $promo_discount_item = abs($promo_discount);
                    }
                    else{
                        $promo_source = 'voucher_online';
                        $promo_valid = true;
                    }
                }
                else
                {
                    return [
                        'status'=>'fail',
                        'messages'=>['Voucher is not valid']
                    ];
                }
            }
            elseif($request->json('id_deals_user') && $request->json('promo_code'))
            {
                return [
                    'status'=>'fail',
                    'messages'=>['Promo is not valid']
                ];
            }
        }

        $error_msg=[];
        //check product service
        if(!empty($post['item_service'])){
            $productService = $this->checkServiceProduct($post, $outlet);
            $post['item_service'] = $productService['item_service']??[];
            if(!empty($productService['error_message']??[])){
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'product_sold_out_status' => true,
                    'messages'  => $productService['error_message']
                ]);
            }
        }

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

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid'] || $post['sub']->original['messages'] == ['Price Service Product Not Valid']) {
                            if (isset($post['sub']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['sub']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }
                    }

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                $post['subtotal_final'] = array_sum($post['sub']['subtotal_final']);
                $post['subtotal'] = array_sum($post['sub']['subtotal']);
                $post['subtotal'] = $post['subtotal'] - $totalDisProduct;
            } elseif ($valueTotal == 'discount') {
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

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                // $post['discount'] = $post['dis'] + $totalDisProduct; 
                $post['discount'] = $totalDisProduct;
            }elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                $mes = ['Data Not Valid'];

                    if (isset($post['tax']->original['messages'])) {
                        $mes = $post['tax']->original['messages'];

                        if ($post['tax']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
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

        $post['discount'] = ($scopeUser == 'apps'? $post['discount'] + $promo_discount:0);
        $post['point'] = ($scopeUser == 'apps'? app($this->setting_trx)->countTransaction('point', $post):0);
        $post['cashback'] = ($scopeUser == 'apps'? app($this->setting_trx)->countTransaction('cashback', $post):0);

        //count some trx user
        $countUserTrx = Transaction::where('id_user', $id)->where('transaction_payment_status', 'Completed')->count();

        if($scopeUser == 'apps'){
            $countSettingCashback = TransactionSetting::get();

            // return $countSettingCashback;
            if ($countUserTrx < count($countSettingCashback)) {
                // return $countUserTrx;
                $post['cashback'] = $post['cashback'] * $countSettingCashback[$countUserTrx]['cashback_percent'] / 100;

                if ($post['cashback'] > $countSettingCashback[$countUserTrx]['cashback_maximum']) {
                    $post['cashback'] = $countSettingCashback[$countUserTrx]['cashback_maximum'];
                }
            } else {

                $maxCash = Setting::where('key', 'cashback_maximum')->first();

                if (count($user['memberships']) > 0) {
                    $post['point'] = $post['point'] * ($user['memberships'][0]['benefit_point_multiplier']) / 100;
                    $post['cashback'] = $post['cashback'] * ($user['memberships'][0]['benefit_cashback_multiplier']) / 100;

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
                    if ($totalCashMax < $post['cashback']) {
                        $post['cashback'] = $totalCashMax;
                    }
                } else {
                    $post['cashback'] = $post['cashback'];
                }
            }
        }

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if ($post['payment_type'] && $post['payment_type'] != 'Balance') {
            $available_payment = $this->availablePayment(new Request())['result'] ?? [];
            if (!in_array($post['payment_type'], array_column($available_payment, 'payment_gateway'))) {
                return [
                    'status' => 'fail',
                    'messages' => 'Metode pembayaran yang dipilih tidak tersedia untuk saat ini'
                ];
            }
        }

        if (!isset($post['shipping'])) {
            $post['shipping'] = 0;
        }

        if (!isset($post['subtotal'])) {
            $post['subtotal'] = 0;
        }

        if (!isset($post['subtotal_final'])) {
            $post['subtotal_final'] = 0;
        }

        if (!isset($post['discount'])) {
            $post['discount'] = 0;
        }

        if (!isset($post['discount_delivery'])) {
            $post['discount_delivery'] = 0;
        }

        if (!isset($post['service'])) {
            $post['service'] = 0;
        }

        if (!isset($post['tax'])) {
            $post['tax'] = 0;
        }

        if (isset($post['payment_type']) && $post['payment_type'] == 'Balance') {
            $post['cashback'] = 0;
            $post['point']    = 0;
        }

        if($scopeUser == 'apps') {
            $post['discount'] = -$post['discount'];
            $post['discount_delivery'] = -$post['discount_delivery'];

            if ($request->json('promo_code') || $request->json('id_deals_user') || $request->json('id_subscription_user')) {
                if ($request->json('id_subscription_user')) {
                    $promo_source = 'subscription';
                }
                $check = $this->checkPromoGetPoint($promo_source);
                if ( $check == 0 ) {
                    $post['cashback'] = 0;
                    $post['point']    = 0;
                }
            }

            // apply cashback
            if ($use_referral){
                $referral_rule = PromoCampaignReferral::where('id_promo_campaign',$code->id_promo_campaign)->first();
                if(!$referral_rule){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Referrer Cashback Failed']
                    ]);
                }
                $referred_cashback = 0;
                if($referral_rule->referred_promo_type == 'Cashback'){
                    if($referral_rule->referred_promo_unit == 'Percent'){
                        $referred_discount_percent = $referral_rule->referred_promo_value<=100?$referral_rule->referred_promo_value:100;
                        $referred_cashback = $post['subtotal']*$referred_discount_percent/100;
                    }else{
                        if($post['subtotal'] >= $referral_rule->referred_min_value){
                            $referred_cashback = $referral_rule->referred_promo_value<=$post['subtotal']?$referral_rule->referred_promo_value:$post['subtotal'];
                        }
                    }
                }
                $post['cashback'] = $referred_cashback;
            }
        }

        $detailPayment = [
            'subtotal' => $post['subtotal'],
            'shipping' => $post['shipping'],
            'tax'      => $post['tax'],
            'service'  => $post['service'],
            'discount' => $post['discount'],
        ];

        // return $detailPayment;
        $post['grandTotal'] = (int)$post['subtotal'] + (int)$post['discount'] + (int)$post['service'] + (int)$post['tax'] + (int)$post['shipping'] + (int)$post['discount_delivery'];
        // return $post;
        if ($post['type'] == 'Delivery') {
            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $userAddress['name'],
                    'phone'       => $userAddress['phone'],
                    'address'     => $userAddress['address'],
                    'postal_code' => $userAddress['postal_code']
                ],
            ];

            $dataShipping = [
                'first_name'  => $userAddress['name'],
                'phone'       => $userAddress['phone'],
                'address'     => $userAddress['address'],
                'postal_code' => $userAddress['postal_code']
            ];
        } elseif($post['type'] == 'Pickup Order') {
            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $user['name'],
                    'phone'       => $user['phone']
                ],
            ];
        } elseif($post['type'] == 'GO-SEND' || $post['type'] == 'Delivery Order'){
            //check key GO-SEND
            $dataAddress = $post['destination'];
            $dataAddress['latitude'] = number_format($dataAddress['latitude'],8);
            $dataAddress['longitude'] = number_format($dataAddress['longitude'],8);
            if($dataAddress['id_user_address']??false){
                $dataAddressKeys = ['id_user_address'=>$dataAddress['id_user_address']];
            }else{
                $dataAddressKeys = [
                    'latitude' => number_format($dataAddress['latitude'],8),
                    'longitude' => number_format($dataAddress['longitude'],8)
                ];
            }
            $dataAddressKeys['id_user'] = $user['id'];
            $addressx = UserAddress::where($dataAddressKeys)->first();
            if(!$addressx){
                $addressx = UserAddress::create($dataAddressKeys+$dataAddress);
            }elseif(!$addressx->favorite){
                $addressx->update($dataAddress);
            }

            if ($post['type'] == 'GO-SEND') {
	            $checkKey = GoSend::checkKey();
	            if(is_array($checkKey) && $checkKey['status'] == 'fail'){
	                DB::rollback();
	                return response()->json($checkKey);
	            }
            }else{
            	$courierWHY = WeHelpYou::getCourier($request->courier, $request, $outlet);
	            if(!$courierWHY){
	                DB::rollback();
	                return response()->json([
	                	'status' => 'fail',
	                	'messages'  => ['Gagal menghitung biaya pengantaran. Silakan coba kembali']
	                ]);
	            }
            }

            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name'  => $user['name'],
                    'phone'       => $user['phone']
                ],
            ];
            $dataShipping = [
                'name'        => $user['name'],
                'phone'       => $user['phone'],
                'address'     => $post['destination']['address']
            ];
        }

        if (!isset($post['latitude'])) {
            $post['latitude'] = null;
        }

        if (!isset($post['longitude'])) {
            $post['longitude'] = null;
        }

        $distance = NULL;
        if(isset($post['latitude']) &&  isset($post['longitude'])){
            $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $outlet['outlet_latitude'], $outlet['outlet_longitude'], "K");
        }

        if (!isset($post['notes'])) {
            $post['notes'] = null;
        }

        $type = $post['type'];
        $isFree = '0';
        $shippingGoSend = 0;

        if($post['type'] == 'GO-SEND' || $post['type'] == 'Delivery Order'){
            if (!($outlet['outlet_latitude'] 
            	&& $outlet['outlet_longitude'] 
            	&& $outlet['outlet_phone'] 
            	&& $outlet['outlet_address'])
            	&& MyHelper::validatePhoneGoSend($outlet['outlet_phone'])
            	&& MyHelper::validatePhoneWehelpyou($outlet['outlet_phone'])
        	) {
                app($this->outlet)->sendNotifIncompleteOutlet($outlet['id_outlet']);
                $outlet->notify_admin = 1;
                $outlet->save();
                return [
                    'status' => 'fail',
                    'messages' => ['Outlet tidak dapat melakukan pengiriman']
                ];
            }
            $coor_origin = [
                'latitude' => number_format($outlet['outlet_latitude'],8),
                'longitude' => number_format($outlet['outlet_longitude'],8)
            ];
            $coor_destination = [
                'latitude' => number_format($post['destination']['latitude'],8),
                'longitude' => number_format($post['destination']['longitude'],8)
            ];
            $type = 'Pickup Order';

            if ($post['type'] == 'GO-SEND') {
	            $shippingGoSendx = GoSend::getPrice($coor_origin,$coor_destination);
	            $shippingGoSend = $shippingGoSendx[GoSend::getShipmentMethod()]['price']['total_price']??null;
	            if($shippingGoSend === null){
	                $errorGosend = array_column($shippingGoSendx[GoSend::getShipmentMethod()]['errors']??[],'message');
	                if(isset($errorGosend[0])){
	                    if($errorGosend[0] == 'Booking distance exceeds 40 kilometres'){
	                        $errorGosend[0] = 'Lokasi tujuan melebihi jarak maksimum pengantaran';
	                    }elseif($errorGosend[0] == 'Origin and destination cannot be same'){
	                        $errorGosend[0] = 'Lokasi outlet dan tujuan tidak boleh di lokasi yang sama';
	                    }elseif($errorGosend[0] == 'Origin and destination cannot be same'){
	                        $errorGosend[0] = 'Lokasi outlet dan tujuan tidak boleh di lokasi yang sama';
	                    }elseif($errorGosend[0] == 'The service is not yet available in this region'){
	                        $errorGosend[0] = 'Pengiriman tidak tersedia di lokasi Anda';
	                    }elseif($errorGosend[0] == "Sender's location is not serviceable"){
	                        $errorGosend[0] = 'Pengiriman tidak tersedia di lokasi Anda';
	                    }
	                }
	                $error_msg += $errorGosend?:['Gagal menghitung biaya pengantaran. Silakan coba kembali'];
	            }else{
	            	$post['shipping'] = $shippingGoSend;
	            	$shippingGoSend = 0;
	            }
	            $shipment_method = 'GO-SEND';
            	$shipment_courier = 'GO-SEND';
            }else{
            	$post['shipping'] = $courierWHY['price'];
	            $shipment_method = 'Wehelpyou';
            	$shipment_courier = $courierWHY['courier'];
            	if (WeHelpYou::isNotEnoughCredit($post['shipping'])) {
            		return [
	                    'status' => 'fail',
	                    'messages' => ['Gagal menghitung biaya pengantaran. Silakan coba kembali']
	                ];
            	}
            }
            //cek free delivery
            // if($post['is_free'] == 'yes'){
            //     $isFree = '1';
            // }
            $isFree = 0;
        }

        if ($post['grandTotal'] < 0 || $post['subtotal'] < 0) {
            return [
                'status' => 'fail',
                'messages' => ['Invalid transaction']
            ];
        }

        if($scopeUser == 'apps'){
            if ($promo_valid) {
                if (isset($promo_type) && ($promo_type == 'Discount delivery' || $promo_type == 'Discount bill')) {
                    $check_promo = app($this->promo)->checkPromo($request, $request->user(), $promo_source, $code ?? $deals, $request->id_outlet, $post['item'], $post['shipping']+$shippingGoSend, $post['sub']['subtotal_per_brand'], $promo_error_product);

                    if ($check_promo['status'] == 'fail') {
                        DB::rollback();
                        return $check_promo;
                    }
                    $post['discount_delivery'] = (-$check_promo['data']['discount_delivery'])??0;
                    $post['discount'] = (-$check_promo['data']['discount']) ?? 0;
                    $promo_discount_bill = abs($check_promo['data']['discount']) ?? 0 ;
                    $post['grandTotal'] = $post['grandTotal'] + (int) $post['discount_delivery'] + (int) $post['discount'];
                }
                // check minimum subtotal
                $check_min_basket = app($this->promo)->checkMinBasketSize($promo_source, $code??$deals, $post['sub']['subtotal_per_brand']);

                if (!$check_min_basket) {
                    DB::rollback();
                    return [
                        'status'=>'fail',
                        'messages'=>['Total pembelian minimum belum terpenuhi']
                    ];
                }
            }
            // check promo subscription type discount and discount delivery
            if ( $request->json('id_subscription_user') )
            {
                // $post_subs['delivery_fee'] = $shippingGoSend+$post['transaction_shipments'];
                $post_subs['delivery_fee'] = $shippingGoSend;
                $post_subs = $post+$post_subs;

                $check_subs = app($this->subscription_use)->checkDiscount($request, $post_subs);

                if ($check_subs['status'] == 'fail') {
                    return $check_subs;
                }

                if ($check_subs['result']['type'] == 'discount_delivery') {
                    $post['discount_delivery'] = -$check_subs['result']['value'];
                    $post['grandTotal'] = $post['grandTotal'] + (int) $post['discount_delivery'];
                }elseif ($check_subs['result']['type'] == 'discount') {
                    $post['discount'] = -$check_subs['result']['value'];
                    $promo_discount_bill = abs($check_subs['result']['value']) ?? 0 ;
                    $post['grandTotal'] = $post['grandTotal'] + (int) $post['discount'];
                }
            }
        }

        DB::beginTransaction();
        UserFeedbackLog::where('id_user',$request->user()->id)->delete();
        $transaction = [
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'id_promo_campaign_promo_code'           => $post['id_promo_campaign_promo_code']??null,
            'transaction_date'            => $post['transaction_date'],
            // 'transaction_receipt_number'  => 'TRX-'.app($this->setting_trx)->getrandomnumber(8).'-'.date('YmdHis'),
            'trasaction_type'             => $type,
            'shipment_method'             => $shipment_method ?? null,
            'shipment_courier'            => $shipment_courier ?? null,
            'transaction_notes'           => $post['notes'],
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_gross'  		  => $post['subtotal_final'],
            'transaction_shipment'        => $post['shipping'],
            'transaction_shipment_go_send'=> $shippingGoSend,
            'transaction_is_free'         => $isFree,
            'transaction_service'         => $post['service'],
            'transaction_discount'        => $post['discount'],
            'transaction_discount_delivery' => $post['discount_delivery'],
            'transaction_discount_item' 	=> $promo_discount_item??0,
            'transaction_discount_bill' 	=> $promo_discount_bill??0,
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $post['grandTotal'] + $shippingGoSend + $post['shipping'],
            'transaction_point_earned'    => $post['point'],
            'transaction_cashback_earned' => $post['cashback'],
            'trasaction_payment_type'     => $post['payment_type'],
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'membership_level'            => $post['membership_level'],
            'membership_promo_id'         => $post['membership_promo_id'],
            'latitude'                    => $post['latitude'],
            'longitude'                   => $post['longitude'],
            'distance_customer'           => $distance,
            'void_date'                   => null,
            'transaction_from'            => $post['transaction_from']
        ];

        if($request->user()->complete_profile == 1){
            $transaction['calculate_achievement'] = 'not yet';
        }else{
            $transaction['calculate_achievement'] = 'no';
        }

        if($transaction['transaction_grandtotal'] == 0){
            $transaction['transaction_payment_status'] = 'Completed';
        }

        $newTopupController = new NewTopupController();
        $checkHashBefore = $newTopupController->checkHash('log_balances', $id);
        if (!$checkHashBefore) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Your previous transaction data is invalid']
            ]);
        }

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

        if($post['transaction_from'] == 'outlet-service'){
            $createOutletService = TransactionOutletService::create([
                'id_transaction' => $insertTransaction['id_transaction'],
                'customer_name' => $post['customer']['name'],
                'customer_email' => $post['customer']['email'],
                'customer_domicile' => $post['customer']['domicile'],
                'customer_birtdate' => $post['customer']['birthdate'],
                'customer_gender' => $post['customer']['gender']
            ]);

            if (!$createOutletService) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Outlet Service Failed']
                ]);
            }
        }

        if($scopeUser == 'apps'){
            // add report referral
            if($use_referral){
                $addPromoCounter = PromoCampaignReferralTransaction::create([
                    'id_promo_campaign_promo_code' =>$code->id_promo_campaign_promo_code,
                    'id_user' => $insertTransaction['id_user'],
                    'id_referrer' => UserReferralCode::select('id_user')->where('id_promo_campaign_promo_code',$code->id_promo_campaign_promo_code)->pluck('id_user')->first(),
                    'id_transaction' => $insertTransaction['id_transaction'],
                    'referred_bonus_type' => $promo_discount?'Product Discount':'Cashback',
                    'referred_bonus' => $promo_discount?:$insertTransaction['transaction_cashback_earned']
                ]);
                if(!$addPromoCounter){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Transaction Failed']
                    ]);
                }

                $promo_code_ref = $request->promo_code;
            }

            // add transaction voucher
            if($request->json('id_deals_user')){
                $update_voucher = DealsUser::where('id_deals_user','=',$request->id_deals_user)->update(['used_at' => date('Y-m-d H:i:s'), 'is_used' => 0]);
                $update_deals = Deal::where('id_deals','=',$deals->dealVoucher['deals']['id_deals'])->update(['deals_total_used' => $deals->dealVoucher['deals']['deals_total_used']+1]);
                $addTransactionVoucher = TransactionVoucher::create([
                    'id_deals_voucher' => $deals['id_deals_voucher'],
                    'id_user' => $insertTransaction['id_user'],
                    'id_transaction' => $insertTransaction['id_transaction']
                ]);
                if(!$addTransactionVoucher){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Transaction Failed']
                    ]);
                }
            }

            // add payment subscription
            if ( $request->json('id_subscription_user') )
            {
                $subscription_total = app($this->subscription_use)->calculate($request, $request->id_subscription_user, $insertTransaction['transaction_subtotal'], $post['sub']['subtotal_per_brand'], $post['item'], $post['id_outlet'], $subs_error, $errorProduct, $subs_product, $subs_applied_product);

                if (!empty($subs_error)) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $subs_error??['Promo not valid']
                    ]);
                }
                $subscription_type = $subscription_total['type'];
                $subscription_total = $subscription_total['value'];
                $subscription['grandtotal'] = $insertTransaction['transaction_grandtotal'] - $subscription_total;
                $data_subs = app($this->subscription_use)->checkSubscription( $request->json('id_subscription_user') );
                $data_subs_detail = $data_subs->load(['subscription_user.subscription' => function($q){
                    $q->select('id_subscription', 'subscription_discount_type');
                }]);
                $subs_discount_type = $data_subs_detail->subscription_user->subscription->subscription_discount_type ?? null;


                if ($subs_discount_type == 'payment_method') {

                    $insert_subs_data['id_transaction'] = $insertTransaction['id_transaction'];
                    $insert_subs_data['id_subscription_user_voucher'] = $data_subs->id_subscription_user_voucher;
                    $insert_subs_data['subscription_nominal'] = $subscription_total;
                    $insert_subs_trx = TransactionPaymentSubscription::create($insert_subs_data);

                    if (!$insert_subs_trx) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Insert Transaction Failed']
                        ]);
                    }
                }

                $update_trx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->update([
                    'id_subscription_user_voucher' => $data_subs->id_subscription_user_voucher
                ]);

                $update_subs_voucher = SubscriptionUserVoucher::where('id_subscription_user_voucher','=',$data_subs->id_subscription_user_voucher)
                    ->update([
                        'used_at' => date('Y-m-d H:i:s'),
                        'id_transaction' => $insertTransaction['id_transaction']
                    ]);

                if (!$update_subs_voucher) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Transaction Failed']
                    ]);
                }

                if ($subs_discount_type == 'payment_method') {
                    //update when total = 0
                    if(($transaction['transaction_grandtotal'] - $subscription_total) == 0){
                        $updateTrx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->update([
                            'transaction_payment_status' => 'Completed',
                            'completed_at' => date('Y-m-d H:i:s')
                        ]);
                        $insertTransaction['transaction_payment_status'] = 'Completed';
                        $insertTransaction['transaction_grandtotal'] = 0;
                    }
                }
            }

            // add promo campaign report
            if($request->json('promo_code'))
            {
                $promo_campaign_report = app($this->promo_campaign)->addReport(
                    $code->id_promo_campaign,
                    $code->id_promo_campaign_promo_code,
                    $insertTransaction['id_transaction'],
                    $insertTransaction['id_outlet'],
                    $request->device_id?:'',
                    $request->device_type?:''
                );

                if (!$promo_campaign_report) {
                    DB::rollBack();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Transaction Failed']
                    ]);
                }
            }
        }

        //update receipt
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

        MyHelper::updateFlagTransactionOnline($insertTransaction, 'pending', $user);

        $insertTransaction['transaction_receipt_number'] = $receipt;
        //process add product service
        if(!empty($post['item_service'])){
            $insertService = $this->insertServiceProduct($post['item_service']??[], $insertTransaction, $outlet, $post, $productMidtrans, $userTrxProduct);
            if(isset($insertService['status']) && $insertService['status'] == 'fail'){
                return response()->json($insertService);
            }
        }

        $totalProductQty = 0;
        foreach (($discount_promo['item']??$post['item']) as $keyProduct => $valueProduct) {

            $this_discount=$valueProduct['discount']??0;

            $checkProduct = Product::where('id_product', $valueProduct['id_product'])->first();
            if (empty($checkProduct)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Product Not Found']
                ]);
            }

            if(!$checkProduct['product_variant_status']){
                $checkDetailProduct = ProductDetail::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $post['id_outlet']])->first();
                $currentProductStock = $checkDetailProduct['product_detail_stock_item']??0;
                $currentProductServiceStock = $checkDetailProduct['product_detail_stock_service']??0;
                $stockItem = 1;
                if ($valueProduct['qty'] > $currentProductStock) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].' tidak tersedia dan akan terhapus dari cart.']
                    ]);
                }

                if ($checkDetailProduct['product_detail_visibility'] == 'Hidden' || (empty($checkDetailProduct) && $checkProduct['product_visibility'] == 'Hidden')) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].' tidak tersedia dan akan terhapus dari cart.']
                    ]);
                }
            }

            if(!isset($valueProduct['note'])){
                $valueProduct['note'] = null;
            }

            $productPrice = 0;

            if($outlet['outlet_different_price']){
                $checkPriceProduct = ProductSpecialPrice::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $post['id_outlet']])->first();
                if(!isset($checkPriceProduct['product_special_price'])){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Product Price Not Valid']
                    ]);
                }
                $productPrice = $checkPriceProduct['product_special_price'];
            }else{
                $checkPriceProduct = ProductGlobalPrice::where(['id_product' => $checkProduct['id_product']])->first();

                if(isset($checkPriceProduct['product_global_price'])){
                    $productPrice = $checkPriceProduct['product_global_price'];
                }else{
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Product Price Not Valid']
                    ]);
                }
            }

            if($checkProduct['product_variant_status'] && !empty($valueProduct['id_product_variant_group'])){
                $productVariantGroup = ProductVariantGroup::where('id_product_variant_group', $valueProduct['id_product_variant_group'])->first();
                $detailProductVariantGroup = ProductVariantGroupDetail::where('id_product_variant_group', $valueProduct['id_product_variant_group'])
                    ->where('id_outlet', $post['id_outlet'])
                    ->first();
                $currentProductStock = $detailProductVariantGroup['product_variant_group_detail_stock_item']??0;
                $currentProductServiceStock = $detailProductVariantGroup['product_variant_group_detail_stock_service']??0;
                $stockItemVariant = 1;
                if($valueProduct['qty'] > $currentProductStock){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].' tidak tersedia dan akan terhapus dari cart.']
                    ]);
                }

                if($detailProductVariantGroup['product_variant_group_visibility'] == 'Hidden' || (empty($detailProductVariantGroup) && $productVariantGroup['product_variant_group_visibility'] == 'Hidden')){
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].' tidak tersedia dan akan terhapus dari cart.']
                    ]);
                }
            }

            $dataProduct = [
                'id_transaction'               => $insertTransaction['id_transaction'],
                'id_product'                   => $checkProduct['id_product'],
                'type'                         => $checkProduct['product_type'],
                'id_product_variant_group'     => $valueProduct['id_product_variant_group']??null,
                'id_brand'                     => $valueProduct['id_brand']??null,
                'id_outlet'                    => $insertTransaction['id_outlet'],
                'id_user'                      => $insertTransaction['id_user'],
                'transaction_product_qty'      => $valueProduct['qty'],
                'transaction_product_price'    => $valueProduct['transaction_product_price'],
                'transaction_product_price_base' => NULL,
                'transaction_product_price_tax'  => NULL,
                'transaction_product_discount'   => $this_discount,
                'transaction_product_discount_all'   => $this_discount,
                'transaction_product_base_discount' => $valueProduct['base_discount'] ?? 0,
                'transaction_product_qty_discount'  => $valueProduct['qty_discount'] ?? 0,
                // remove discount from subtotal
                // 'transaction_product_subtotal' => ($valueProduct['qty'] * $checkPriceProduct['product_price'])-$this_discount,
                'transaction_product_subtotal' => $valueProduct['transaction_product_subtotal'],
                'transaction_product_net' => $valueProduct['transaction_product_subtotal']-$this_discount,
                'transaction_variant_subtotal' => $valueProduct['transaction_variant_subtotal'],
                'transaction_product_note'     => $valueProduct['note'],
                'created_at'                   => date('Y-m-d', strtotime($insertTransaction['transaction_date'])).' '.date('H:i:s'),
                'updated_at'                   => date('Y-m-d H:i:s')
            ];

            $trx_product = TransactionProduct::create($dataProduct);
            if (!$trx_product) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Product Transaction Failed']
                ]);
            }
            if(strtotime($insertTransaction['transaction_date'])){
                $trx_product->created_at = strtotime($insertTransaction['transaction_date']);
            }

            $insert_modifier = [];
            $mod_subtotal = 0;
            $more_mid_text = '';
            if(isset($valueProduct['modifiers'])){
                foreach ($valueProduct['modifiers'] as $modifier) {
                    $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
                    $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
                    $mod = ProductModifier::select('product_modifiers.id_product_modifier','code',
                        DB::raw('(CASE
                        WHEN product_modifiers.text_detail_trx IS NOT NULL 
                        THEN product_modifiers.text_detail_trx
                        ELSE product_modifiers.text
                    END) as text'),
                        'product_modifier_stock_status','modifier_type',\DB::raw('coalesce(product_modifier_price, 0) as product_modifier_price'), 'id_product_modifier_group', 'modifier_type')
                        // product visible
                        ->leftJoin('product_modifier_details', function($join) use ($post) {
                            $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                                ->where('product_modifier_details.id_outlet',$post['id_outlet']);
                        })
                        ->where(function($query){
                            $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                            ->orWhere(function($q){
                                $q->whereNull('product_modifier_details.product_modifier_visibility')
                                ->where('product_modifiers.product_modifier_visibility', 'Visible');
                            });
                        })
                        // ->where(function($q) {
                        //     $q->where(function($q){
                        //         $q->where('product_modifier_stock_status','Available')->orWhereNull('product_modifier_stock_status');
                        //     });
                        // })
                        ->where(function($q){
                            $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
                        })
                        ->groupBy('product_modifiers.id_product_modifier');
                    if($outlet['outlet_different_price']){
                        $mod->leftJoin('product_modifier_prices',function($join) use ($post){
                            $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                            $join->where('product_modifier_prices.id_outlet',$post['id_outlet']);
                        });
                    }else{
                        $mod->leftJoin('product_modifier_global_prices',function($join) use ($post){
                            $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                        });
                    }
                    $mod = $mod->find($id_product_modifier);
                    if(!$mod){
                        return [
                            'status' => 'fail',
                            'messages' => ['Topping tidak ditemukan']
                        ];
                    }
                    if($mod->product_modifier_stock_status == 'Sold Out'){
                        if ($mod->modifier_type == 'Modifier Group') {
                            return [
                                'status' => 'fail',
                                'product_sold_out_status' => true,
                                'messages' => ['Detail variant yang dipilih untuk produk '.$checkProduct['product_name'].' tidak tersedia.']
                            ];
                        } else {
                            return [
                                'status' => 'fail',
                                'messages' => ['Topping '.$mod->text.' yang dipilih untuk produk '.$checkProduct['product_name'].' tidak tersedia.']
                            ];
                        }
                    }
                    $mod = $mod->toArray();
                    $insert_modifier[] = [
                        'id_transaction_product'=>$trx_product['id_transaction_product'],
                        'id_transaction'=>$insertTransaction['id_transaction'],
                        'id_product'=>$checkProduct['id_product'],
                        'id_product_modifier'=>$id_product_modifier,
                        'id_product_modifier_group'=>$mod['modifier_type'] == 'Modifier Group' ? $mod['id_product_modifier_group'] : null,
                        'id_outlet'=>$insertTransaction['id_outlet'],
                        'id_user'=>$insertTransaction['id_user'],
                        'type'=>$mod['type']??'',
                        'code'=>$mod['code']??'',
                        'text'=>$mod['text']??'',
                        'qty'=>$qty_product_modifier,
                        'transaction_product_modifier_price'=>$mod['product_modifier_price']*$qty_product_modifier,
                        'datetime'=>$insertTransaction['transaction_date']??date(),
                        'trx_type'=>$type,
                        // 'sales_type'=>'',
                        'created_at'                   => date('Y-m-d H:i:s'),
                        'updated_at'                   => date('Y-m-d H:i:s')
                    ];
                    $mod_subtotal += $mod['product_modifier_price']*$qty_product_modifier;
                    if($qty_product_modifier>1){
                        $more_mid_text .= ','.$qty_product_modifier.'x '.$mod['text'];
                    }else{
                        $more_mid_text .= ','.$mod['text'];
                    }
                }

            }

            $trx_modifier = TransactionProductModifier::insert($insert_modifier);
            if (!$trx_modifier) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Product Modifier Transaction Failed']
                ]);
            }
            $insert_variants = [];
            foreach ($valueProduct['variants'] as $id_product_variant => $product_variant_price) {
                $insert_variants[] = [
                    'id_transaction_product' => $trx_product['id_transaction_product'],
                    'id_product_variant' => $id_product_variant,
                    'transaction_product_variant_price' => $product_variant_price,
                    'created_at'                   => date('Y-m-d H:i:s'),
                    'updated_at'                   => date('Y-m-d H:i:s')
                ];
            }
            $trx_variants = TransactionProductVariant::insert($insert_variants);
            $trx_product->transaction_modifier_subtotal = $mod_subtotal;
            $trx_product->save();
            $dataProductMidtrans = [
                'id'       => $checkProduct['id_product'],
                'price'    => $productPrice + $mod_subtotal - ($trx_product['transaction_product_discount']/$trx_product['transaction_product_qty']),
                // 'name'     => $checkProduct['product_name'].($more_mid_text?'('.trim($more_mid_text,',').')':''), // name & modifier too long
                'name'     => $checkProduct['product_name'],
                'quantity' => $valueProduct['qty'],
            ];
            array_push($productMidtrans, $dataProductMidtrans);
            $totalWeight += $checkProduct['product_weight'] * $valueProduct['qty'];

            $dataUserTrxProduct = [
                'id_user'       => $insertTransaction['id_user'],
                'id_product'    => $checkProduct['id_product'],
                'product_qty'   => $valueProduct['qty'],
                'last_trx_date' => $insertTransaction['transaction_date']
            ];
            array_push($userTrxProduct, $dataUserTrxProduct);
            $totalProductQty += $valueProduct['qty'];
        }

        array_push($dataDetailProduct, $productMidtrans);

        $dataShip = [
            'id'       => null,
            'price'    => $post['shipping'],
            'name'     => 'Shipping',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataShip);

        $dataService = [
            'id'       => null,
            'price'    => $post['service'],
            'name'     => 'Service',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataService);

        $dataTax = [
            'id'       => null,
            'price'    => $post['tax'],
            'name'     => 'Tax',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataTax);

        $dataDis = [
            'id'       => null,
            'price'    => -$post['discount'],
            'name'     => 'Discount',
            'quantity' => 1,
        ];
        array_push($dataDetailProduct, $dataDis);

        $insertUserTrxProduct = app($this->transaction)->insertUserTrxProduct($userTrxProduct);
        if ($insertUserTrxProduct == 'fail') {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Product Transaction Failed']
            ]);
        }
        if (isset($post['receive_at']) && $post['receive_at']) {
            $post['receive_at'] = date('Y-m-d H:i:s', strtotime($post['receive_at']));
        } else {
            $post['receive_at'] = null;
        }

        if (isset($post['id_admin_outlet_receive'])) {
            $post['id_admin_outlet_receive'] = $post['id_admin_outlet_receive'];
        } else {
            $post['id_admin_outlet_receive'] = null;
        }

        $configAdminOutlet = Configs::where('config_name', 'admin outlet')->first();

        if($configAdminOutlet && $configAdminOutlet['is_active'] == '1'){

            if ($post['type'] == 'Delivery') {
                $configAdminOutlet = Configs::where('config_name', 'admin outlet delivery order')->first();
            }else{
                $configAdminOutlet = Configs::where('config_name', 'admin outlet pickup order')->first();
            }

            if($configAdminOutlet && $configAdminOutlet['is_active'] == '1'){
                $adminOutlet = UserOutlet::where('id_outlet', $insertTransaction['id_outlet'])->orderBy('id_user_outlet');
            }
        }

        //sum balance
        $sumBalance = LogBalance::where('id_user', $id)->sum('balance');
        if ($post['transaction_payment_status'] == 'Completed') {
            $checkMembership = app($this->membership)->calculateMembership($user['phone']);
            if (!$checkMembership) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Recount membership failed']
                ]);
            }
        }

        if (isset($post['payment_type']) || $insertTransaction['transaction_grandtotal'] == 0) {

            if ($post['payment_type'] == 'Balance' || $insertTransaction['transaction_grandtotal'] == 0) {

                if($insertTransaction['transaction_grandtotal'] > 0){
                    $save = app($this->balance)->topUp($insertTransaction['id_user'], ($subscription['grandtotal']??$insertTransaction['transaction_grandtotal']), $insertTransaction['id_transaction']);
    
                    if (!isset($save['status'])) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Transaction failed']]);
                    }
    
                    if ($save['status'] == 'fail') {
                        DB::rollback();
                        return response()->json($save);
                    }
                }else{
                    $save['status'] = 'success'; 
                    $save['type'] = 'no_topup';
                }

                if($save['status'] == 'success'){
                    $checkFraudPoint = app($this->setting_fraud)->fraudTrxPoint($sumBalance, $user, ['id_outlet' => $insertTransaction['id_outlet']]);
                    if(isset($checkFraudPoint['status'])){
                        return response()->json($checkFraudPoint);
                    }
                }
                
                if ($post['transaction_payment_status'] == 'Completed' || $save['type'] == 'no_topup') {

                    if($config_fraud_use_queue == 1){
                        FraudJob::dispatch($user, $insertTransaction, 'transaction')->onConnection('fraudqueue');
                    }else {
                        if($config_fraud_use_queue != 1){
                            $checkFraud = app($this->setting_fraud)->checkFraudTrxOnline($user, $insertTransaction);
                        }
                    }
                }

                if ($save['type'] == 'no_topup') {
                    $mid['order_id'] = $insertTransaction['transaction_receipt_number'];
                    $mid['gross_amount'] = 0;

                    $insertTransaction = Transaction::with('user.memberships', 'outlet', 'productTransaction')->where('transaction_receipt_number', $insertTransaction['transaction_receipt_number'])->first();

                    if($request->json('id_deals_user') && !$request->json('promo_code'))
			        {
			        	$check_trx_voucher = TransactionVoucher::where('id_deals_voucher', $deals['id_deals_voucher'])->where('status','success')->count();

						if(($check_trx_voucher??false) > 1)
						{
							DB::rollBack();
				            return [
				                'status'=>'fail',
				                'messages'=>['Voucher is not valid']
				            ];
				        }
			        }

                    if ($configAdminOutlet && $configAdminOutlet['is_active'] == '1') {
                        $sendAdmin = app($this->notif)->sendNotif($insertTransaction);
                        if (!$sendAdmin) {
                            DB::rollback();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Transaction failed']
                            ]);
                        }
                    }

                    $send = app($this->notif)->notification($mid, $insertTransaction);

                    if (!$send) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Transaction failed']
                        ]);
                    }

                    $sendNotifOutlet = $this->outletNotif($insertTransaction['id_transaction']);
                    // return $sendNotifOutlet;
                    $dataRedirect = $this->dataRedirect($insertTransaction['transaction_receipt_number'], 'trx', '1');

                    if($post['latitude'] && $post['longitude']){
                        $savelocation = $this->saveLocation($post['latitude'], $post['longitude'], $insertTransaction['id_user'], $insertTransaction['id_transaction'], $insertTransaction['id_outlet']);
                     }

                    // PromoCampaignTools::applyReferrerCashback($insertTransaction);

                    /* Add daily Trx*/
                    $dataDailyTrx = [
                        'id_transaction'    => $insertTransaction['id_transaction'],
                        'id_outlet'         => $outlet['id_outlet'],
                        'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
                        'referral_code_use_date'=> date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
                        'id_user'           => $user['id'],
                        'referral_code'     => NULL
                    ];
                    $createDailyTrx = DailyTransactions::create($dataDailyTrx);

                    /* Fraud Referral*/
                    if($promo_code_ref){
                        //======= Start Check Fraud Referral User =======//
                        $data = [
                            'id_user' => $insertTransaction['id_user'],
                            'referral_code' => $promo_code_ref,
                            'referral_code_use_date' => $insertTransaction['transaction_date'],
                            'id_transaction' => $insertTransaction['id_transaction']
                        ];
                        if($config_fraud_use_queue == 1){
                            FraudJob::dispatch($user, $data, 'referral user')->onConnection('fraudqueue');
                            FraudJob::dispatch($user, $data, 'referral')->onConnection('fraudqueue');
                        }else{
                            app($this->setting_fraud)->fraudCheckReferralUser($data);
                            app($this->setting_fraud)->fraudCheckReferral($data);
                        }
                        //======= End Check Fraud Referral User =======//
                    }

                    DB::commit();
                    //insert to disburse job for calculation income outlet
                    DisburseJob::dispatch(['id_transaction' => $insertTransaction['id_transaction']])->onConnection('disbursequeue');

                    //remove for result
                    unset($insertTransaction['user']);
                    unset($insertTransaction['outlet']);
                    unset($insertTransaction['product_transaction']);

                    return response()->json([
                        'status'     => 'success',
                        'redirect'   => false,
                        'result'     => $insertTransaction,
                        'additional' => $dataRedirect
                    ]);
                }
            }

            if ($post['payment_type'] == 'Midtrans') {
                if ($post['transaction_payment_status'] == 'Completed') {
                    //bank
                    $bank = ['BNI', 'Mandiri', 'BCA'];
                    $getBank = array_rand($bank);

                    //payment_method
                    $method = ['credit_card', 'bank_transfer', 'direct_debit'];
                    $getMethod = array_rand($method);

                    $dataInsertMidtrans = [
                        'id_transaction'     => $insertTransaction['id_transaction'],
                        'approval_code'      => 000000,
                        'bank'               => $bank[$getBank],
                        'eci'                => $this->getrandomnumber(2),
                        'transaction_time'   => $insertTransaction['transaction_date'],
                        'gross_amount'       => $insertTransaction['transaction_grandtotal'],
                        'order_id'           => $insertTransaction['transaction_receipt_number'],
                        'payment_type'       => $method[$getMethod],
                        'signature_key'      => $this->getrandomstring(),
                        'status_code'        => 200,
                        'vt_transaction_id'  => $this->getrandomstring(8).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(4).'-'.$this->getrandomstring(12),
                        'transaction_status' => 'capture',
                        'fraud_status'       => 'accept',
                        'status_message'     => 'Veritrans payment notification'
                    ];

                    $insertDataMidtrans = TransactionPaymentMidtran::create($dataInsertMidtrans);
                    if (!$insertDataMidtrans) {
                        DB::rollback();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Insert Data Midtrans Failed']
                        ]);
                    }

                }

            }

            if ($post['payment_type'] == 'Cash') {
                $createTrxPyemntCash = TransactionPaymentCash::create([
                    'id_transaction' => $insertTransaction['id_transaction'],
                    'payment_code' => MyHelper::createrandom(4, null, strtotime(date('Y-m-d H:i:s'))),
                    'cash_nominal' => $insertTransaction['transaction_grandtotal']
                ]);
                if (!$createTrxPyemntCash) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Data transaction payment Failed']
                    ]);
                }

                $multiplePaymentCash = TransactionMultiplePayment::create([
                    'id_transaction' => $insertTransaction['id_transaction'],
                    'type' => 'Cash',
                    'payment_detail' => 'Cash',
                    'id_payment' => $createTrxPyemntCash['id_transaction_payment_cash']
                ]);

                if (!$multiplePaymentCash) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Insert Data multiple payment Failed']
                    ]);
                }

                $dataRedirect = $this->dataRedirect($insertTransaction['transaction_receipt_number'], 'trx', '1');

                if($config_fraud_use_queue == 1){
                    FraudJob::dispatch($user, $insertTransaction, 'transaction')->onConnection('fraudqueue');
                }else {
                    if($config_fraud_use_queue != 1){
                        $checkFraud = app($this->setting_fraud)->checkFraudTrxOnline($user, $insertTransaction);
                    }
                }

                /* Add daily Trx*/
                $dataDailyTrx = [
                    'id_transaction'    => $insertTransaction['id_transaction'],
                    'id_outlet'         => $outlet['id_outlet'],
                    'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
                    'id_user'           => $user['id']
                ];
                DailyTransactions::create($dataDailyTrx);
                DB::commit();

                //remove for result
                unset($insertTransaction['user']);
                unset($insertTransaction['outlet']);
                unset($insertTransaction['product_transaction']);

                return response()->json([
                    'status'     => 'success',
                    'redirect'   => false,
                    'result'     => $insertTransaction,
                    'additional' => $dataRedirect
                ]);
            }
        }

        /* Add daily Trx*/
        $dataDailyTrx = [
            'id_transaction'    => $insertTransaction['id_transaction'],
            'id_outlet'         => $outlet['id_outlet'],
            'transaction_date'  => date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
            'referral_code_use_date'=> date('Y-m-d H:i:s', strtotime($insertTransaction['transaction_date'])),
            'id_user'           => $user['id'],
            'referral_code'     => NULL
        ];
        $createDailyTrx = DailyTransactions::create($dataDailyTrx);

        if($scopeUser == 'apps'){
            /* Fraud Referral*/
            if($promo_code_ref){
                //======= Start Check Fraud Referral User =======//
                $data = [
                    'id_user' => $insertTransaction['id_user'],
                    'referral_code' => $promo_code_ref,
                    'referral_code_use_date' => $insertTransaction['transaction_date'],
                    'id_transaction' => $insertTransaction['id_transaction']
                ];
                if($config_fraud_use_queue == 1){
                    FraudJob::dispatch($user, $data, 'referral user')->onConnection('fraudqueue');
                    FraudJob::dispatch($user, $data, 'referral')->onConnection('fraudqueue');
                }else{
                    app($this->setting_fraud)->fraudCheckReferralUser($data);
                    app($this->setting_fraud)->fraudCheckReferral($data);
                }
                //======= End Check Fraud Referral User =======//
            }

            if($request->json('id_deals_user') && !$request->json('promo_code'))
            {
                $check_trx_voucher = TransactionVoucher::where('id_deals_voucher', $deals['id_deals_voucher'])->where('status','success')->count();

                if(($check_trx_voucher??false) > 1)
                {
                    DB::rollBack();
                    return [
                        'status'=>'fail',
                        'messages'=>['Voucher is not valid']
                    ];
                }
            }
        }

        DB::commit();

        //insert to disburse job for calculation income outlet
        DisburseJob::dispatch(['id_transaction' => $insertTransaction['id_transaction']])->onConnection('disbursequeue');

        $insertTransaction['cancel_message'] = 'Are you sure you want to cancel this transaction?';
        $insertTransaction['timer_shopeepay'] = (int) MyHelper::setting('shopeepay_validity_period','value', 300);
        $insertTransaction['message_timeout_shopeepay'] = "Sorry, your payment has expired";
        return response()->json([
            'status'   => 'success',
            'redirect' => true,
            'result'   => $insertTransaction
        ]);

    }

    /**
     * Get info from given cart data
     * @param  CheckTransaction $request [description]
     * @return View                    [description]
     */
    public function checkTransaction(Request $request) {
        $post = $request->json()->all();
        $bearerToken = $request->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        if(empty($post['item']) && empty($post['item_service'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item/Item Service can not be empty']
            ]);
        }
        if(empty($post['outlet_code']) && empty($post['id_outlet'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['ID/Code outlet can not be empty']
            ]);
        }

        $post['item'] = $this->mergeProducts($post['item']??[]);
        $grandTotal = app($this->setting_trx)->grandTotal();
        $user = $request->user();
        if($user->complete_profile == 0){
            return response()->json([
                'status'    => 'success',
                'result'  => [
                    'complete_profile' => false
                ]
            ]);
        }

        //Check Outlet
        if(!empty($post['outlet_code'])){
            $outlet = Outlet::where('outlet_code', $post['outlet_code'])->with('today')->first();
            $post['id_outlet'] = $outlet['id_outlet']??null;
        }else{
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->first();
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
        $outlet_status = 1;
        //cek outlet active
        if(isset($outlet['outlet_status']) && $outlet['outlet_status'] == 'Inactive'){
            $outlet_status = 0;
        }

        //cek outlet holiday
        if($issetDate == false){
            $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                    ->where('id_outlet', $outlet['id_outlet'])->whereDay('date_holidays.date', date('d'))->whereMonth('date_holidays.date', date('m'))->get();
            if(count($holiday) > 0){
                foreach($holiday as $i => $holi){
                    if($holi['yearly'] == '0'){
                        if($holi['date'] == date('Y-m-d')){
                            $outlet_status = 0;
                        }
                    }else{
                        $outlet_status = 0;
                    }
                }
            }

            if($outlet['today']['is_closed'] == '1'){
                $outlet_status = 0;
            }

            $settingTime = Setting::where('key', 'processing_time')->first();

             if($outlet['today']['close'] && $outlet['today']['close'] != "00:00" && $outlet['today']['open'] && $outlet['today']['open'] != '00:00'){

                if($settingTime && $settingTime->value){
                    if($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close']))){
                        $outlet_status = 0;
                    }
                }

                //cek outlet open - close hour
                if(($outlet['today']['open'] && date('H:i') < date('H:i', strtotime($outlet['today']['open']))) || ($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close'])))){
                    $outlet_status = 0;
                }
            }
        }

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if (!isset($post['shipping'])) {
            $post['shipping'] = 0;
        }

        $shippingGoSend = 0;
        $shippingGoSendPrice = 0;
        $listDelivery = [];
        $error_msg=[];

        if(empty($post['type'])){
            $post['type'] = null;
        }

        if($post['type'] == 'Delivery' && !$outlet->delivery_order) {
            $error_msg[] = 'Maaf, Outlet ini tidak support untuk delivery order';
        }

        if($post['type'] == 'Delivery Order' || $post['type'] == 'GO-SEND'){
            $delivery_outlet = DeliveryOutlet::where('id_outlet', $outlet->id_outlet)->pluck('code')->toArray();
            if (!($outlet['outlet_latitude'] 
            	&& $outlet['outlet_longitude'] 
            	&& $outlet['outlet_phone'] 
            	&& $outlet['outlet_address'])
            	&& MyHelper::validatePhoneGoSend($outlet['outlet_phone'])
            	&& MyHelper::validatePhoneWehelpyou($outlet['outlet_phone'])
        	){
                app($this->outlet)->sendNotifIncompleteOutlet($outlet['id_outlet']);
                $outlet->notify_admin = 1;
                $outlet->save();
                return [
                    'status' => 'fail',
                    'messages' => ['Outlet tidak dapat melakukan pengiriman']
                ];
            }

        	if ($post['type'] == 'GO-SEND' || in_array('gosend', $delivery_outlet)) {
	            $coor_origin = [
	                'latitude' => number_format($outlet['outlet_latitude'],8),
	                'longitude' => number_format($outlet['outlet_longitude'],8)
	            ];
	            $coor_destination = [
	                'latitude' => number_format($post['destination']['latitude'],8),
	                'longitude' => number_format($post['destination']['longitude'],8)
	            ];
	            $type = 'Pickup Order';
	            $shippingGoSendx = GoSend::getPrice($coor_origin,$coor_destination);
	            $shippingGoSend = $shippingGoSendx[GoSend::getShipmentMethod()]['price']['total_price']??null;
	            if($shippingGoSend === null){
	                $errorGosend = array_column($shippingGoSendx[GoSend::getShipmentMethod()]['errors']??[],'message');
	                if(isset($errorGosend[0])){
	                    if($errorGosend[0] == 'Booking distance exceeds 40 kilometres'){
	                        $errorGosend[0] = 'Lokasi tujuan melebihi jarak maksimum pengantaran';
	                    }elseif($errorGosend[0] == 'Origin and destination cannot be same'){
	                        $errorGosend[0] = 'Lokasi outlet dan tujuan tidak boleh di lokasi yang sama';
	                    }elseif($errorGosend[0] == 'Origin and destination cannot be same'){
	                        $errorGosend[0] = 'Lokasi outlet dan tujuan tidak boleh di lokasi yang sama';
	                    }elseif($errorGosend[0] == 'The service is not yet available in this region'){
	                        $errorGosend[0] = 'Pengiriman tidak tersedia di lokasi Anda';
	                    }elseif($errorGosend[0] == "Sender's location is not serviceable"){
	                        $errorGosend[0] = 'Pengiriman tidak tersedia di lokasi Anda';
	                    }
	                }
	                $error_msg += $errorGosend?:['Gagal menghitung biaya pengantaran. Silakan coba kembali'];
	            }
	            
	            if ($post['type'] == 'Delivery Order') {
		            $shippingGoSendPrice = $shippingGoSend;
		            $shippingGoSend = 0;
	            }
        	}
            $isFree = 0;
        }

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

        if (!isset($post['discount_delivery'])) {
            $post['discount_delivery'] = 0;
        }

        $post['discount'] = -$post['discount'];
        $post['discount_delivery'] = -$post['discount_delivery'];
        $totalDisProduct = 0;

        if($scopeUser == 'apps'){
            // hitung product discount
            $productDis = app($this->setting_trx)->discountProduct($post);
            if (is_numeric($productDis)) {
                $totalDisProduct = $productDis;
            }else{
                return $productDis;
            }

            // remove bonus item
            $pct = new PromoCampaignTools();
            $post['item'] = $pct->removeBonusItem($post['item']);
        }

        // check promo code & voucher
        $promo_error=null;
        $promo_source = null;
        $promo_valid = false;
        $subs_valid = false;
        $promo_discount = 0;
        $promo_type = null;
        $use_referral = false;
        $request_promo = $request;
        unset($request_promo['type']);

        if($scopeUser == 'apps'){
            if($request->promo_code && !$request->id_subscription_user && !$request->id_deals_user){
                $code = app($this->promo_campaign)->checkPromoCode($request->promo_code, 1, 1);

                if ($code)
                {
                    if ($code['promo_campaign']['date_end'] < date('Y-m-d H:i:s')) {
                        $error = ['Promo campaign is ended'];
                        $promo_error = app($this->promo_campaign)->promoError('transaction', $error);
                    }
                    else
                    {
                        $promo_type = $code->promo_type;
                        if ($promo_type != 'Discount bill' && $promo_type != 'Discount delivery') {
                            if($code->promo_type == "Referral"){
                                $use_referral = true;
                            }
                            $validate_user = $pct->validateUser($code->id_promo_campaign, $request->user()->id, $request->user()->phone, $request->device_type, $request->device_id, $errore,$code->id_promo_campaign_promo_code);

                            if ($validate_user) {
                                $discount_promo=$pct->validatePromo($request_promo, $code->id_promo_campaign, $request->id_outlet, $post['item'], $errors, 'promo_campaign', $errorProduct, $post['shipping']+$shippingGoSend);

                                $promo_source = 'promo_code';
                                if ( !empty($errore) || !empty($errors) ) {
                                    $promo_error = app($this->promo_campaign)->promoError('transaction', $errore, $errors, $errorProduct);
                                    if ($errorProduct) {
                                        $promo_error['product_label'] = app($this->promo_campaign)->getProduct('promo_campaign', $code['promo_campaign'])['product']??'';
                                        $promo_error['product'] = $pct->getRequiredProduct($code->id_promo_campaign)??null;
                                    }
                                    $promo_source = null;
                                }
                                else{
                                    $promo_valid = true;
                                }
                                $promo_discount=$discount_promo['discount'];
                            }
                            else
                            {
                                $promo_error = app($this->promo_campaign)->promoError('transaction', $errore);
                            }
                        }else{
                            $promo_source 	= 'promo_code';
                            $promo_valid 	= true;
                        }
                    }
                }
                else
                {
                    $error = ['Promo code invalid'];
                    $promo_error = app($this->promo_campaign)->promoError('transaction', $error);
                }
            }
            elseif(!$request->promo_code && !$request->id_subscription_user && $request->id_deals_user)
            {
                $deals = app($this->promo_campaign)->checkVoucher($request->id_deals_user, 1, 1);

                if($deals)
                {
                    $promo_type = $deals->dealVoucher->deals->promo_type;
                    if ($promo_type != 'Discount bill' && $promo_type != 'Discount delivery') {
                        $discount_promo = $pct->validatePromo($request_promo, $deals->dealVoucher->id_deals, $request->id_outlet, $post['item'], $errors, 'deals', $errorProduct, $post['shipping']+$shippingGoSend);

                        $promo_source = 'voucher_online';
                        if ( !empty($errors) ) {
                            $code = $deals->toArray();
                            $promo_error = app($this->promo_campaign)->promoError('transaction', null, $errors, $errorProduct);
                            if ($errorProduct) {
                                $promo_error['product_label'] = app($this->promo_campaign)->getProduct('deals', $code['deal_voucher']['deals'])['product']??'';
                                $promo_error['product'] = $pct->getRequiredProduct($deals->dealVoucher->id_deals, 'deals')??null;
                            }
                            $promo_source = null;
                        }
                        else{
                            $promo_valid = true;
                        }
                        $promo_discount=$discount_promo['discount'];
                    }else{
                        $promo_source 	= 'voucher_online';
                        $promo_valid 	= true;
                    }
                }
                else
                {
                    $error = ['Voucher is not valid'];
                    $promo_error = app($this->promo_campaign)->promoError('transaction', $error);
                }
            } elseif (!$request->promo_code && $request->id_subscription_user && !$request->id_deals_user) {
                $subs = app($this->subscription_use)->checkSubscription($request->id_subscription_user, "outlet", "product", "product_detail", null, null, "brand");
                $promo_source = 'subscription';
                if ($subs) {
                    $subs_valid = true;
                }
            }
        }
        // end check promo code & voucher

        $tree = [];
        // check and group product
        $subtotal = 0;
        $missing_product = 0;
        // return [$discount_promo['item'],$errors];
        $is_advance = 0;

        $tree_promo = []; 
        $subtotal_promo = 0;

        $global_max_order = Outlet::select('max_order')->where('id_outlet',$post['id_outlet'])->pluck('max_order')->first();
        if($global_max_order == null){
            $global_max_order = Setting::select('value')->where('key','max_order')->pluck('value')->first();
            if($global_max_order == null){
                $global_max_order = 100;
            }
        }

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

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                // $post['subtotal'] = array_sum($post['sub']);
                $post['subtotal'] = array_sum($post['sub']['subtotal']);
                $post['subtotal'] = $post['subtotal'] - $totalDisProduct??0;
            } elseif ($valueTotal == 'discount') {
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

                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => $mes
                    ]);
                }

                // $post['discount'] = $post['dis'] + $totalDisProduct;
                $post['discount'] = $totalDisProduct??0;
            }elseif($valueTotal == 'tax'){
                $post['tax'] = app($this->setting_trx)->countTransaction($valueTotal, $post);
                $mes = ['Data Not Valid'];

                    if (isset($post['tax']->original['messages'])) {
                        $mes = $post['tax']->original['messages'];

                        if ($post['tax']->original['messages'] == ['Price Product Not Found']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Found with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
                            }
                        }

                        if ($post['sub']->original['messages'] == ['Price Product Not Valid']) {
                            if (isset($post['tax']->original['product'])) {
                                $mes = ['Price Product Not Valid with product '.$post['tax']->original['product'].' at outlet '.$outlet['outlet_name']];
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

        $promo_missing_product 	= false;
        $missing_bonus_product 	= false;
        $subtotal_per_brand 	= [];
        $totalItem = 0;
        $items = [];
        foreach ($discount_promo['item']??$post['item'] as &$item) {
            // get detail product
            $product = Product::select([
                    'products.id_product','products.product_name','products.product_code','products.product_description',
                    DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = '.$post['id_outlet'].' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' )
                            ELSE product_global_price.product_global_price
                        END) as product_price'),
                    DB::raw('(select product_detail.product_detail_stock_item from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1) as product_stock_status'),
                    DB::raw('(CASE
                            WHEN (select product_detail.max_order from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' ) 
                            is NULL THEN NULL
                            ELSE (select product_detail.max_order from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        END) as max_order'),
                    'brand_product.id_brand', 'products.product_variant_status'
                ])
                ->join('brand_product','brand_product.id_product','=','products.id_product')
                ->leftJoin('product_global_price','product_global_price.id_product','=','products.id_product')
                // brand produk ada di outlet
                ->where('brand_outlet.id_outlet','=',$post['id_outlet'])
                ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility IS NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                    END)')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                    END)')
                ->where(function ($query) use ($post){
                    $query->orWhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' ) is NOT NULL');
                    $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product) is NOT NULL');
                })
                ->with([
                    'photos' => function($query){
                        $query->select('id_product','product_photo');
                    },
                    'product_promo_categories' => function($query){
                        $query->select('product_promo_categories.id_product_promo_category','product_promo_category_name as product_category_name','product_promo_category_order as product_category_order');
                    },
                ])
            ->having('product_price','>',0)
            ->groupBy('products.id_product')
            ->orderBy('products.position')
            ->find($item['id_product']);
            $max_order = $product['max_order'];
            if($max_order==null){
                $max_order = $global_max_order;
            }
            if($max_order&&($item['qty']>$max_order)){
                $is_advance = 1;
                $error_msg[] = MyHelper::simpleReplace(
                    Setting::select('value_text')->where('key','transaction_exceeds_limit_text')->pluck('value_text')->first()?:'Transaksi anda melebihi batas! Maksimal transaksi untuk %product_name% : %max_order%',
                    [
                        'product_name' => $product['product_name'],
                        'max_order' => $max_order
                    ]
                );
                continue;
            }
            if(!$product){
                $missing_product++;
                if (isset($item['bonus']) && $item['bonus'] == 1) {
        			$missing_bonus_product 	= true;
        		}
                if ($item['is_promo'] ?? false) {
                	$promo_missing_product = true;
                }
                continue;
            }
            $product->append('photo');
            $product = $product->toArray();

            if($product['product_variant_status'] && !empty($item['id_product_variant_group'])){
                $product['product_stock_status'] = ProductVariantGroupDetail::where('id_product_variant_group', $item['id_product_variant_group'])
                        ->where('id_outlet', $outlet['id_outlet'])
                        ->first()['product_variant_group_detail_stock_item']??0;
            }

            if($item['qty'] > $product['product_stock_status']){
            	if ((isset($item['bonus']) && $item['bonus'] == 1) || (isset($item['is_promo']) && $item['is_promo'] == 1)) {
            		if (isset($item['bonus']) && $item['bonus'] == 1) {
            			$missing_bonus_product 	= true;
            		}
            		$promo_missing_product = true;
            		continue;
            	}
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
            $product['note'] = $item['note']??'';
            $product['promo_discount'] = 0;
            isset($item['new_price']) ? $product['new_price']=$item['new_price'] : '';
            $product['is_promo'] = 0;
            $product['is_free'] = $item['is_free']??0;
            $product['bonus'] = $item['bonus']??0;

            $product['id_product_variant_group'] = $item['id_product_variant_group'] ?? null;
            if ($product['id_product_variant_group']) {
                $product['product_price'] = $item['transaction_product_price'];
                $product['selected_variant'] = Product::getVariantParentId($item['id_product_variant_group'], Product::getVariantTree($item['id_product'], $outlet)['variants_tree'], array_column($product['extra_modifiers']??[], 'id_product_variant'));
            } elseif ($product['extra_modifiers']??[]) {
                $product['selected_variant'] = array_column($product['extra_modifiers']??[], 'id_product_variant');
            } else {
                $product['selected_variant'] = [];
            }

            $order = array_flip($product['selected_variant']);
            $variants = array_merge(ProductVariant::select('id_product_variant', 'product_variant_name')->whereIn('id_product_variant', array_keys($item['variants']))->get()->toArray(), $product['extra_modifiers']??[]);
            $product['extra_modifiers'] = array_column($product['extra_modifiers']??[], 'id_product_variant');
            $filtered = array_filter($variants, function($i) use ($product) {return in_array($i['id_product_variant'], $product['selected_variant']);});
            if(count($variants) != count($filtered)){
                $variantsss = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')->select('product_variant_name')->where('id_product_variant_group', $product['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                $modifiersss = ProductModifier::whereIn('id_product_modifier', array_column($item['modifiers'], 'id_product_modifier'))->where('modifier_type', 'Modifier Group')->pluck('text')->toArray();
                $error_msg[] = MyHelper::simpleReplace(
                    'Varian %variants% untuk %product_name% tidak tersedia',
                    [
                        'variants' => implode(', ', array_merge($variantsss, $modifiersss)),
                        'product_name' => $product['product_name']
                    ]
                );
                continue;
            }
            usort($variants, function ($a, $b) use ($order) {
                return $order[$a['id_product_variant']] <=> $order[$b['id_product_variant']];
            });
            $product['variants'] = $variants;

            if($product['id_product_variant_group']){
                $productVariantGroup = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first();
                if($productVariantGroup['product_variant_group_visibility'] == 'Hidden'){
                    $error_msg[] = MyHelper::simpleReplace(
                        'Product %product_name% tidak tersedia',
                        [
                            'product_name' => $product['product_name']
                        ]
                    );
                    continue;
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

            $product['product_variant_group_price'] = (int)$product_variant_group_price;

            $product['product_price_total'] = $item['transaction_product_subtotal'];
            $product['product_price_raw'] = (int) $product['product_price'];
            $product['product_price_raw_total'] = (int) $product['product_price'];
            $product['qty_stock'] = (int)$product['product_stock_status'];
            // $product['product_price'] = MyHelper::requestNumber($product['product_price']+$mod_price, '_CURRENCY');
            $product['product_price'] = (int) $product['product_price'];

            if (!$product['bonus']) {
            	$tree[$product['id_brand']]['products'][]=$product;
            	$subtotal += $product['product_price_total'];
            }

            $product['is_promo'] 		= $item['is_promo']??0;
            $product['promo_discount'] 	= $item['discount']??0;
            $tree_promo[$product['id_brand']]['products'][] = $product;
            $subtotal_promo += $product['product_price_total'];

            if (isset($subtotal_per_brand[$item['id_brand']])) {
            	$subtotal_per_brand[$item['id_brand']] += $product['product_price_total'];
            }else{
            	$subtotal_per_brand[$item['id_brand']] = $product['product_price_total'];
            }

            //calculate total item
            $totalItem += $product['qty'];
            // return $product;
            if(!empty($product['product_stock_status'])){
                $product['product_stock_status'] = 'Available';
            }else{
                $product['product_stock_status'] = 'Sold Out';
            }
            $items[] = $product;
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
                "domicile" => $user['city_name'],
                "birthdate" => date('Y-m-d', strtotime($user['birthday'])),
                "gender" => $user['gender'],
            ];
        }else{
            $result['customer'] = [
                "name" => $post['customer']['name']??"",
                "email" => $post['customer']['email']??"",
                "domicile" => $post['customer']['domicile']??"",
                "birthdate" => $post['customer']['birthdate']??"",
                "gender" => $post['customer']['gender']??"",
            ];
        }

        // check service product
        $result['item_service'] = [];
        if(!empty($post['item_service'])){
            $itemServices = $this->checkServiceProduct($post, $outlet);
            $result['item_service'] = $itemServices['item_service']??[];
            $totalItem = $totalItem + $itemServices['total_item_service']??0;
            if(!isset($post['from_new']) || (isset($post['from_new']) && $post['from_new'] === false)){
                $error_msg = array_merge($error_msg, $itemServices['error_message']??[]);
            }
        }

        if($scopeUser == 'apps') {
            if ($promo_missing_product) {
                $promo_valid = false;
                $promo_discount = 0;
                $promo_source = null;
                $discount_promo['discount_delivery'] = 0;
                $error = ['Promo tidak berlaku karena product tidak tersedia'];
                $promo_error_product = $missing_bonus_product ? 0 : 'all';
                $promo_error = app($this->promo_campaign)->promoError('transaction', $error, null, $promo_error_product);
            } elseif ($missing_product) {
                $error_msg[] = MyHelper::simpleReplace(
                    '%missing_product% products not found',
                    [
                        'missing_product' => $missing_product
                    ]
                );
            }

            $post['discount'] = $post['discount'] + $promo_discount;
            $post['discount_delivery'] = $post['discount_delivery'] + ($discount_promo['discount_delivery']??0);
        }

        $outlet['today']['status'] = $outlet_status?'open':'closed';

        $result['outlet'] = [
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_address' => $outlet['outlet_address'],
            'delivery_order' => $outlet['delivery_order'],
            'today' => $outlet['today']
        ];
        $result['item'] = $items;
        $result['subtotal_product_service'] = $itemServices['subtotal_service']??0;
        $result['subtotal_product'] = $subtotal;
        $subtotal += $itemServices['subtotal_service']??0;

        $earnedPoint = $this->countTranscationPoint($post, $user);

        $result['subtotal'] = $subtotal;
        $result['shipping'] = $post['shipping']+$shippingGoSend;
        $result['discount'] = $post['discount'];
        $result['discount_delivery'] = $post['discount_delivery'];
        $result['service'] = (int) $post['service'];
        $result['tax'] = (int) $post['tax'];
        $result['grandtotal'] = (int)$result['subtotal'] + (int)(-$post['discount']) + (int)$post['service'] + (int)$post['tax'] + (int)$post['shipping'] + $shippingGoSend + (int)(-$post['discount_delivery']);
        $result['subscription'] = 0;
        $result['used_point'] = 0;
        $balance = app($this->balance)->balanceNow($user->id);
        $result['points'] = (int) $balance;
        $result['total_promo'] = app($this->promo)->availablePromo();
        $result['pickup_type'] = 1;
        $result['delivery_type'] = $outlet['delivery_order'];
        $result['available_payment'] = null;
        $result['point_earned'] = null;

        if($scopeUser == 'apps') {
            if ($request->id_subscription_user && !$request->promo_code && !$request->id_deals_user) {
                $promo_source = 'subscription';
                $check_subs = app($this->subscription_use)->calculate($request_promo, $request->id_subscription_user, $result['subtotal'], $subtotal_per_brand, $post['item'], $post['id_outlet'], $subs_error, $errorProduct, $subs_product, $subs_applied_product, $result['shipping']);

                if (!empty($subs_error)) {
                    $error = $subs_error;
                    $promo_error = app($this->promo_campaign)->promoError('transaction', $error, null, $errorProduct);
                    $promo_error['product'] = $subs_applied_product ?? null;
                    $promo_error['product_label'] = $subs_product ?? '';
                    $result['subscription'] = 0;
                } else {
                    $promo_valid = true;
                    if ($check_subs['type'] == 'discount_delivery') {
                        $result['grandtotal'] -= $check_subs['value'];
                        $result['discount_delivery'] += $check_subs['value'];
                    } elseif ($check_subs['type'] == 'discount') {
                        $result['grandtotal'] -= $check_subs['value'];
                        $result['discount'] += $check_subs['value'];
                    } else {
                        $result['subscription'] = $check_subs['value'];
                    }
                }
            }

            $result['get_point'] = ($post['payment_type'] != 'Balance') ? $this->checkPromoGetPoint($promo_source) : 0;

            $cashback = $post['cashback'] ?? 0;
            if ($result['get_point'] && $earnedPoint['cashback']) {
                $cashback = $earnedPoint['cashback'];
            }

            if ($use_referral) {
                $referralCashback = $pct->countReferralCashback($code->id_promo_campaign, $subtotal);
                if ($referralCashback['status'] == 'fail') {
                    $promo_error = app($this->promo_campaign)->promoError('transaction', $referralCashback['messages'] ?? ['Gagal menghitung referral cashback']);
                }
                $cashback = $referralCashback['result'] ?? $post['cashback'];
            }

            if ($cashback) {
                $result['point_earned'] = [
                    'value' => MyHelper::requestNumber($cashback, '_CURRENCY'),
                    'text' => MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
                ];
            }

            if (isset($post['payment_type']) && $post['payment_type'] == 'Balance') {
                if($balance >= ($result['grandtotal']-$result['subscription'])){
                    $result['used_point'] = $result['grandtotal'];

                    if ($result['subscription'] >= $result['used_point']) {
                        $result['used_point'] = 0;
                    }else{
                        $result['used_point'] = $result['used_point'] - $result['subscription'];
                    }
                }else{
                    $result['used_point'] = $balance;
                }

                $result['points'] -= $result['used_point'];
            }

            if (!empty($result['subscription']))
            {
                if ($result['subscription'] >= $result['grandtotal']) {
                    $result['grandtotal'] = 0;
                }else{
                    $result['grandtotal'] = $result['grandtotal'] - $result['subscription'];
                }
            }
        }

        $result['total_payment'] = $result['grandtotal'] - $result['used_point'];


        $result['subscription'] = (int) $result['subscription'];
        $result['discount'] = (int) $result['discount'];

        $result['available_delivery'] = $listDelivery;

        if ($post['type'] == 'Delivery Order') {
        	$result['delivery_type'] = $this->showListDelivery($result['delivery_type'], $result['available_delivery']);
        } else {
        	$result['delivery_type'] = $this->showListDeliveryPickup($result['delivery_type'], $post['id_outlet']);
        }

        if ($promo_valid) {
        	// check available shipment, payment
        	$result = app($this->promo)->getTransactionCheckPromoRule($result, $promo_source, $code ?? $deals ?? $request, $post['type']);
        }

        $result['payment_detail'] = [];
        
        //subtotal
        $result['payment_detail'][] = [
            'name'          => 'Subtotal ('.$totalItem.' item)',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($result['subtotal'],'_CURRENCY')
        ];

        //discount product / bill
        if($result['discount'] > 0){
            if($request->id_subscription_user){
                $result['payment_detail'][] = [
                    'name'          => 'Subscription (Diskon)',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($result['discount'],'_CURRENCY')
                ];
            }else{
                $result['payment_detail'][] = [
                    'name'          => 'Diskon (Promo)',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($result['discount'],'_CURRENCY')
                ];
            }
        }

        if ($post['type'] == 'GO-SEND') {
	        //delivery gosend
	        if($result['shipping'] > 0){
	            $result['payment_detail'][] = [
	                'name'          => 'Delivery (GO-SEND)',
	                "is_discount"   => 0,
	                'amount'        => MyHelper::requestNumber($result['shipping'],'_CURRENCY')
	            ];
	        }
        }

        if (isset($delivery['delivery_name'])) {
        	$result['payment_detail'][] = [

	    		'name'          => 'Delivery' . ($delivery['delivery_name'] ? ' (' . $delivery['delivery_name'] . ')' : null),
	            "is_discount"   => 0,
	            'amount'        => MyHelper::requestNumber($delivery['price'],'_CURRENCY')
	    	];
        }

        //discount delivery
        if($result['discount_delivery'] > 0){
            if($request->id_subscription_user){
                $result['payment_detail'][] = [
                    'name'          => 'Subscription (Delivery)',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($result['discount_delivery'],'_CURRENCY')
                ];
            }else{
                $result['payment_detail'][] = [
                    'name'          => 'Diskon (Delivery)',
                    "is_discount"   => 1,
                    'amount'        => '- '.MyHelper::requestNumber($result['discount_delivery'],'_CURRENCY')
                ];
            }
        }

        //add subscription to payment detail
        if($request->id_subscription_user && $result['subscription'] > 0){
            $result['payment_detail'][] = [
                'name'          => 'Subscription',
                "is_discount"   => 1,
                'amount'        => '- '.MyHelper::requestNumber($result['subscription'],'_CURRENCY')
            ];
        }

        if (count($error_msg) > 1 && (!empty($post['item']) || !empty($post['item_service']))) {
            $error_msg = ['Produk atau Service yang anda pilih tidak tersedia. Silakan cek kembali pesanan anda'];
        }

        $result['currency'] = 'Rp';
        $result['complete_profile'] = true;
        return MyHelper::checkGet($result)+['messages'=>$error_msg,'promo_error'=>$promo_error];
    }

    public function checkBundlingProduct($post, $outlet, $subtotal_per_brand = []){
        $error_msg = [];
        $subTotalBundling = 0;
        $totalItemBundling = 0;
        $itemBundlingDetail = [];
        $itemBundling = [];
        $errorBundlingName = [];
        $currentHour = date('H:i:s');
        foreach ($post['item_bundling']??[] as $key=>$bundling){
            if($bundling['bundling_qty'] <= 0){
                $error_msg[] = $bundling['bundling_name'].' qty must not be below 0';
                unset($post['item_bundling'][$key]);
                continue;
            }
            $getBundling = Bundling::where('bundling.id_bundling', $bundling['id_bundling'])
                ->join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')
                ->whereRaw('TIME_TO_SEC("'.$currentHour.'") >= TIME_TO_SEC(time_start) AND TIME_TO_SEC("'.$currentHour.'") <= TIME_TO_SEC(time_end)')
                ->whereRaw('NOW() >= start_date AND NOW() <= end_date')->first();
            if(empty($getBundling)){
                $errorBundlingName[] = $bundling['bundling_name'];
                unset($post['item_bundling'][$key]);
                continue;
            }

            //check count product in bundling
            $getBundlingProduct = BundlingProduct::where('id_bundling', $bundling['id_bundling'])->select('id_product', 'bundling_product_qty')->get()->toArray();
            $arrBundlingQty = array_column($getBundlingProduct, 'bundling_product_qty');
            $arrBundlingIdProduct = array_column($getBundlingProduct, 'id_product');
            if(array_sum($arrBundlingQty) !== count($bundling['products'])){
                $error_msg[] = MyHelper::simpleReplace(
                    'Jumlah product pada bundling %bundling_name% tidak sesuai',
                    [
                        'bundling_name' => $bundling['bundling_name']
                    ]
                );
                unset($post['item_bundling'][$key]);
                continue;
            }

            //check outlet available
            if($getBundling['all_outlet'] == 0 && $getBundling['outlet_available_type'] == 'Selected Outlet'){
                $getBundlingOutlet = BundlingOutlet::where('id_bundling', $bundling['id_bundling'])->where('id_outlet', $post['id_outlet'])->count();

                if(empty($getBundlingOutlet)){
                    $error_msg[] = MyHelper::simpleReplace(
                        'Bundling %bundling_name% tidak bisa digunakan di outlet %outlet_name%',
                        [
                            'bundling_name' => $bundling['bundling_name'],
                            'outlet_name' => $outlet['outlet_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue;
                }
            }elseif($getBundling['all_outlet'] == 0 && $getBundling['outlet_available_type'] == 'Outlet Group Filter'){
                $brands = BrandProduct::whereIn('id_product', $arrBundlingIdProduct)->pluck('id_brand')->toArray();
                $availableBundling = app($this->bundling)->bundlingOutletGroupFilter($post['id_outlet'], $brands);
                if(empty($availableBundling)){
                    $error_msg[] = MyHelper::simpleReplace(
                        'Bundling %bundling_name% tidak bisa digunakan di outlet %outlet_name%',
                        [
                            'bundling_name' => $bundling['bundling_name'],
                            'outlet_name' => $outlet['outlet_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue;
                }
            }

            $bundlingBasePrice = 0;
            $totalModPrice = 0;
            $totalPriceNoDiscount = 0;
            $products = [];
            $productsBundlingDetail = [];
            //check product from bundling
            foreach ($bundling['products'] as $keyProduct => $p){
                $product = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
                    ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
                    ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
                    ->where('bundling_product.id_bundling_product', $p['id_bundling_product'])
                    ->where('products.is_inactive', 0)
                    ->select('products.product_visibility', 'pgp.product_global_price',  'products.product_variant_status',
                        'bundling_product.*', 'bundling.bundling_promo_status','bundling.bundling_name', 'bundling.bundling_code', 'products.*')
                    ->first();

                if(empty($product)){
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }
                $getProductDetail = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first();
                $product['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;
                $id_product_variant_group = $product['id_product_variant_group']??null;

                if($product['visibility_outlet'] == 'Hidden' || (empty($product['visibility_outlet']) && $product['product_visibility'] == 'Hidden')){
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                if(isset($getProductDetail['product_detail_stock_status']) && $getProductDetail['product_detail_stock_status'] == 'Sold Out'){
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }
                $product['note'] = $p['note']??'';
                if($product['product_variant_status'] && !empty($product['id_product_variant_group'])){
                    $checkAvailable = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first();
                    if($checkAvailable['product_variant_group_visibility'] == 'Hidden'){
                        $errorBundlingName[] = $bundling['bundling_name'];
                        unset($post['item_bundling'][$key]);
                        continue 2;
                    }else{
                        if($outlet['outlet_different_price'] == 1){
                            $price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $product['id_product_variant_group'])->where('id_outlet', $post['id_outlet'])->first()['product_variant_group_price']??0;
                        }else{
                            $price = $checkAvailable['product_variant_group_price']??0;
                        }
                    }

                }elseif(!empty($p['id_product'])){
                    if($outlet['outlet_different_price'] == 1){
                        $price = ProductSpecialPrice::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price']??0;
                    }else{
                        $price = $product['product_global_price'];
                    }
                }

                $price = (float)$price??0;
                if($price <= 0){
                    $errorBundlingName[] = $bundling['bundling_name'];
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                $totalPriceNoDiscount = $totalPriceNoDiscount + $price;
                //calculate discount produk
                if(strtolower($product['bundling_product_discount_type']) == 'nominal'){
                    $calculate = ($price - $product['bundling_product_discount']);
                }else{
                    $discount = $price*($product['bundling_product_discount']/100);
                    $discount = ($discount > $product['bundling_product_maximum_discount'] &&  $product['bundling_product_maximum_discount'] > 0? $product['bundling_product_maximum_discount']:$discount);
                    $calculate = ($price - $discount);
                }
                $bundlingBasePrice = $bundlingBasePrice + $calculate;

                // get modifier
                $mod_price = 0;
                $modifiers = [];
                $removed_modifier = [];
                $missing_modifier = 0;
                foreach ($p['modifiers']??[] as $key => $modifier) {
                    $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
                    $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
                    $mod = ProductModifier::select('product_modifiers.id_product_modifier','code',
                        DB::raw('(CASE
                        WHEN product_modifiers.text_detail_trx IS NOT NULL 
                        THEN product_modifiers.text_detail_trx
                        ELSE product_modifiers.text
                    END) as text'),
                        'product_modifier_stock_status',\DB::raw('coalesce(product_modifier_price, 0) as product_modifier_price'), 'modifier_type')
                        // product visible
                        ->leftJoin('product_modifier_details', function($join) use ($post) {
                            $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                                ->where('product_modifier_details.id_outlet',$post['id_outlet']);
                        })
                        ->where(function($q) {
                            $q->where(function($q){
                                $q->where(function($query){
                                    $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                                        ->orWhere(function($q){
                                            $q->whereNull('product_modifier_details.product_modifier_visibility')
                                                ->where('product_modifiers.product_modifier_visibility', 'Visible');
                                        });
                                });
                            })->orWhere('product_modifiers.modifier_type', '=', 'Modifier Group');
                        })
                        ->where(function($q){
                            $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
                        })
                        ->groupBy('product_modifiers.id_product_modifier');
                    if($outlet['outlet_different_price']){
                        $mod->leftJoin('product_modifier_prices',function($join) use ($post){
                            $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                            $join->where('product_modifier_prices.id_outlet',$post['id_outlet']);
                        });
                    }else{
                        $mod->leftJoin('product_modifier_global_prices',function($join) use ($post){
                            $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                        });
                    }
                    $mod = $mod->find($id_product_modifier);
                    if(!$mod){
                        $missing_modifier++;
                        continue;
                    }
                    $mod = $mod->toArray();
                    $scope = $mod['modifier_type'];
                    $mod['qty'] = $qty_product_modifier;
                    $mod['product_modifier_price'] = (int) $mod['product_modifier_price'];
                    if ($scope != 'Modifier Group') {
                        if ($mod['product_modifier_stock_status'] != 'Sold Out') {
                            $modifiers[]=$mod;
                        } else {
                            $removed_modifier[] = $mod['text'];
                        }
                    }
                    $mod_price+=$mod['qty']*$mod['product_modifier_price'];
                }

                if($missing_modifier){
                    $error_msg[] = MyHelper::simpleReplace(
                        '%missing_modifier% topping untuk produk %product_name% pada bundling %bundling_name% tidak tersedia',
                        [
                            'missing_modifier' => $missing_modifier,
                            'product_name' => $product['product_name'],
                            'bundling_name' => $bundling['bundling_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }
                if($removed_modifier){
                    $error_msg[] = MyHelper::simpleReplace(
                        'Topping %removed_modifier% untuk produk %product_name% pada bundling %bundling_name% tidak tersedia',
                        [
                            'removed_modifier' => implode(',',$removed_modifier),
                            'product_name' => $product['product_name'],
                            'bundling_name' => $bundling['bundling_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                $product['selected_variant'] = [];
                $variants = [];
                if(!empty($id_product_variant_group)){
                    $variants = ProductVariantGroup::join('product_variant_pivot as pvp', 'pvp.id_product_variant_group', 'product_variant_groups.id_product_variant_group')
                        ->join('product_variants as pv', 'pv.id_product_variant', 'pvp.id_product_variant')
                        ->select('pv.id_product_variant', 'product_variant_name')
                        ->where('product_variant_groups.id_product_variant_group', $id_product_variant_group)
                        ->orderBy('pv.product_variant_order', 'asc')
                        ->get()->toArray();
                    $product['selected_variant'] = array_column($variants, 'id_product_variant');
                }

                $extraModifier = [];
                if(!empty($p['extra_modifiers'])){
                    $extraModifier = ProductModifier::join('product_modifier_groups as pmg', 'pmg.id_product_modifier_group', 'product_modifiers.id_product_modifier_group')
                                    ->join('product_modifier_group_pivots as pmgp', 'pmgp.id_product_modifier_group', 'pmg.id_product_modifier_group')
                                    ->select('product_modifiers.*', 'pmgp.id_product', 'pmgp.id_product_variant')
                                    ->whereIn('product_modifiers.id_product_modifier', $p['extra_modifiers'])
                                    ->where(function ($q) use ($product){
                                        $q->whereIn('pmgp.id_product_variant', $product['selected_variant'])
                                            ->orWhere('pmgp.id_product', $product['id_product']);
                                    })
                                    ->get()->toArray();
                    foreach ($extraModifier as $m){
                        $variants[] = [
                            'id_product_variant' => $m['id_product_modifier'],
                            'id_product_variant_group' => $m['id_product_modifier_group'],
                            'product_variant_name' => $m['text_detail_trx']
                        ];
                    }
                }

                if(isset($p['extra_modifiers']) && (count($p['extra_modifiers']) != count($extraModifier))){
                    $variantsss = ProductVariant::join('product_variant_pivot', 'product_variant_pivot.id_product_variant', 'product_variants.id_product_variant')->select('product_variant_name')->where('id_product_variant_group', $product['id_product_variant_group'])->pluck('product_variant_name')->toArray();
                    $modifiersss = ProductModifier::whereIn('id_product_modifier', array_column($extraModifier, 'id_product_modifier'))->where('modifier_type', 'Modifier Group')->pluck('text')->toArray();
                    $error_msg[] = MyHelper::simpleReplace(
                        'Varian %variants% untuk %product_name% tidak tersedia pada bundling %bundling_name%',
                        [
                            'variants' => implode(', ', array_merge($variantsss, $modifiersss)),
                            'product_name' => $product['product_name'],
                            'bundling_name' => $bundling['bundling_name']
                        ]
                    );
                    unset($post['item_bundling'][$key]);
                    continue 2;
                }

                $totalModPrice = $totalModPrice + $mod_price;
                $product['variants'] = $variants;
                $products[] = [
                    "id_brand" => $product['id_brand'],
                    "id_product" => $product['id_product'],
                    "id_bundling_product" => $product['id_bundling_product'],
                    "id_product_variant_group" => $product['id_product_variant_group'],
                    "modifiers" => $modifiers,
                    "extra_modifiers" => array_column($extraModifier, 'id_product_modifier'),
                    "product_name" => $product['product_name'],
                    "note" => (!empty($product['note']) ? $product['note'] : ""),
                    "product_code" => $product['product_code'],
                    "selected_variant" => array_merge($product['selected_variant'], $p['extra_modifiers']??[]),
                    "variants"=> $product['variants']
                ];

                $productsBundlingDetail[] = [
                    "product_qty" => 1,
                    "id_brand" => $product['id_brand'],
                    "id_product" => $product['id_product'],
                    "id_bundling_product" => $product['id_bundling_product'],
                    "id_product_variant_group" => $product['id_product_variant_group'],
                    "modifiers" => $modifiers,
                    "extra_modifiers" => array_column($extraModifier, 'id_product_modifier'),
                    "product_name" => $product['product_name'],
                    "note" => (!empty($product['note']) ? $product['note'] : ""),
                    "product_code" => $product['product_code'],
                    "selected_variant" => array_merge($product['selected_variant'], $p['extra_modifiers']??[]),
                    "variants"=> $product['variants']
                ];

                if($product['bundling_promo_status'] == 1){
                    if (isset($subtotal_per_brand[$product['id_brand']])) {
                        $subtotal_per_brand[$product['id_brand']] += ($calculate  + $mod_price) * $bundling['bundling_qty'];
                    }else{
                        $subtotal_per_brand[$product['id_brand']] = ($calculate  + $mod_price) * $bundling['bundling_qty'];
                    }
                    $bundlingNotIncludePromo[] = $bundling['bundling_name'];
                }
            }

            if(!empty($products) && !empty($productsBundlingDetail)){
                $itemBundling[] = [
                    "id_custom" => $bundling['id_custom']??null,
                    "id_bundling" => $getBundling['id_bundling'],
                    "bundling_name" => $getBundling['bundling_name'],
                    "bundling_code" => $getBundling['bundling_code'],
                    "bundling_base_price" => $bundlingBasePrice,
                    "bundling_qty" => $bundling['bundling_qty'],
                    "bundling_price_total" =>  $bundlingBasePrice + $totalModPrice,
                    "products" => $products
                ];

                $productsBundlingDetail = $this->mergeBundlingProducts($productsBundlingDetail, $bundling['bundling_qty']);
                //check for same detail item bundling
                $itemBundlingDetail[] = [
                    "id_custom" => $bundling['id_custom']??null,
                    "id_bundling" => $bundling['id_bundling']??null,
                    'bundling_name' => $bundling['bundling_name'],
                    'bundling_qty' => $bundling['bundling_qty'],
                    'bundling_price_no_discount' => (int)$totalPriceNoDiscount * $bundling['bundling_qty'],
                    'bundling_subtotal' => $bundlingBasePrice * $bundling['bundling_qty'],
                    'bundling_sub_item' => '@'.MyHelper::requestNumber($bundlingBasePrice,'_CURRENCY'),
                    'bundling_sub_item_raw' => $bundlingBasePrice,
                    'bundling_sub_price_no_discount' => (int)$totalPriceNoDiscount,
                    "products" => $productsBundlingDetail
                ];

                $subTotalBundling = $subTotalBundling + (($bundlingBasePrice + $totalModPrice) * $bundling['bundling_qty']);
                $totalItemBundling = $totalItemBundling + $bundling['bundling_qty'];
            }
        }

        $mergeBundlingDetail = $this->mergeBundlingDetail($itemBundlingDetail);
        $mergeBundling = $this->mergeBundling($itemBundling);
        if(!empty($errorBundlingName)){
            $error_msg[] = 'Product '.implode(',', array_unique($errorBundlingName)). ' tidak tersedia dan akan terhapus dari cart.';
        }

        return [
            'total_item_bundling' => $totalItemBundling,
            'subtotal_bundling' => $subTotalBundling,
            'item_bundling' => $mergeBundling,
            'item_bundling_detail' => $mergeBundlingDetail,
            'error_message' => $error_msg,
            'subtotal_per_brand' => $subtotal_per_brand,
            'bundling_not_include_promo' => implode(',', array_unique($bundlingNotIncludePromo??[]))
        ];
    }

    public function checkBundlingIncludePromo($post){
        $arr = [];
        foreach ($post['item_bundling']??[] as $key=>$bundling) {
            $getBundling = Bundling::where('bundling.id_bundling', $bundling['id_bundling'])
                ->join('bundling_today as bt', 'bt.id_bundling', 'bundling.id_bundling')->first();

            if(!empty($getBundling)){
                $getBundlingProduct = BundlingProduct::join('brand_product', 'brand_product.id_product', 'bundling_product.id_product')
                    ->where('bundling_product.id_bundling', $bundling['id_bundling'])
                    ->pluck('brand_product.id_brand')->toArray();

                foreach ($getBundlingProduct as $brand){
                    if($getBundling['bundling_promo_status'] == 1){
                        $arr[] = [
                            'id_brand' => $brand,
                            'id_bundling' => $bundling['id_bundling']
                        ];
                    }
                }
            }
        }

        return $arr;
    }

    public function checkServiceProduct($post, $outlet){
        $error_msg = [];
        $subTotalService = 0;
        $itemService = [];
        $errorServiceName = [];
        $errorHsNotAvailable = [];
        $errorBookTime = [];
        $currentDate = date('Y-m-d H:i');
        $idOutletSchedule = $outlet['today']['id_outlet_schedule']??null;

        foreach ($post['item_service']??[] as $key=>$item){
            $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                        ->leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                        ->where('products.id_product', $item['id_product'])
                        ->select('products.*', 'product_global_price as product_price', 'brand_product.id_brand')
                        ->with(['product_service_use_detail'])
                        ->where('product_type', 'service')
                        ->first();

            if(empty($service)){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }

            foreach ($service['product_service_use_detail'] as $stock){
                if($stock['quantity_use'] > $stock['product_detail_stock_service']){
                    $errorServiceName[] = $item['product_name'];
                    unset($post['item_service'][$key]);
                    continue 2;
                }
            }

            $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $post['id_outlet'])->first();
            $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

            if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }

            if($outlet['outlet_special_status'] == 1){
                $service['product_price'] = ProductSpecialPrice::where('id_product', $item['id_product'])
                    ->where('id_outlet', $outlet['id_outlet'])->first()['product_special_price']??0;
            }

            if(empty($service['product_price'])){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }

            if($service['product_detail_visibility'] == 'Hidden' || (empty($service['product_detail_visibility']) && $service['product_visibility'] == 'Hidden')){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }

            $bookTime = date('Y-m-d H:i', strtotime(date('Y-m-d', strtotime($item['booking_date'])).' '.date('H:i', strtotime($item['booking_time']))));

            //check available hs
            $hs = UserHairStylist::where('id_user_hair_stylist', $item['id_user_hair_stylist'])->where('user_hair_stylist_status', 'Active')->first();
            if(empty($hs)){
                $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
                unset($post['item_service'][$key]);
                continue;
            }

            if(strtotime($currentDate) > strtotime($bookTime)){
                $errorBookTime[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
                unset($post['item_service'][$key]);
                continue;
            }

            //get hs schedule
            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                    ->whereNotNull('approve_at')->where('id_user_hair_stylist', $item['id_user_hair_stylist'])
                    ->whereDate('date', date('Y-m-d', strtotime($item['booking_date'])))
                    ->first()['shift']??'';
            if(empty($shift)){
                $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
                unset($post['item_service'][$key]);
                continue;
            }

            $getTimeShift = app($this->product)->getTimeShift(strtolower($shift), $post['id_outlet'],$idOutletSchedule);
            if(empty($getTimeShift['start']) && empty($getTimeShift['end'])){
                $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
                unset($post['item_service'][$key]);
                continue;
            }

            $shiftTimeStart = date('H:i:s', strtotime($getTimeShift['start']));
            $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
            $time = date('H:i', strtotime($item['booking_time']));
            if((strtotime($time) >= strtotime($shiftTimeStart) && strtotime($time) < strtotime($shiftTimeEnd)) === false){
                $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
                unset($post['item_service'][$key]);
                continue;
            }

            $hsNotAvailable = HairstylistNotAvailable::where('id_outlet', $post['id_outlet'])
                ->where('booking_date', date('Y-m-d', strtotime($item['booking_date'])))
                ->where('booking_time', date('H:i:s', strtotime($item['booking_time'])))
                ->where('id_user_hair_stylist', $item['id_user_hair_stylist'])
                ->first();

            if(!empty($hsNotAvailable)){
                $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
                unset($post['item_service'][$key]);
                continue;
            }

            $itemService[] = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $service['id_brand'],
                "id_product" => $service['id_product'],
                "product_code" => $service['product_code'],
                "product_name" => $service['product_name'],
                "product_price" => (int)$service['product_price'],
                "id_user_hair_stylist" => $hs['id_user_hair_stylist'],
                "user_hair_stylist_name" => $hs['fullname'],
                "booking_date" => $item['booking_date'],
                "booking_date_display" => MyHelper::dateFormatInd($item['booking_date'], true, false),
                "booking_time" => $item['booking_time']
            ];
            $subTotalService = $subTotalService + $service['product_price'];
        }

        $mergeService = $this->mergeService($itemService);
        if(!empty($errorServiceName)){
            $error_msg[] = 'Service '.implode(',', array_unique($errorServiceName)). ' tidak tersedia dan akan terhapus dari cart.';
        }

        if(!empty($errorHsNotAvailable)){
            $error_msg[] = 'Hair stylist '.implode(',', array_unique($errorHsNotAvailable)). ' tidak tersedia dan akan terhapus dari cart.';
        }

        if(!empty($errorBookTime)){
            $error_msg[] = 'Waktu pemesanan untuk hair stylist '.implode(',', array_unique($errorBookTime)). ' telah kadaluarsa.';
        }

        return [
            'total_item_service' => count($mergeService),
            'subtotal_service' => $subTotalService,
            'item_service' => $mergeService,
            'error_message' => $error_msg
        ];
    }

    public function saveLocation($latitude, $longitude, $id_user, $id_transaction, $id_outlet){

        $cek = UserLocationDetail::where('id_reference', $id_transaction)->where('activity', 'Transaction')->first();
        if($cek){
            return true;
        }

        $googlemap = MyHelper::get(env('GEOCODE_URL').$latitude.','.$longitude.'&key='.env('GEOCODE_KEY'));

        if(isset($googlemap['results'][0]['address_components'])){

            $street = null;
            $route = null;
            $level1 = null;
            $level2 = null;
            $level3 = null;
            $level4 = null;
            $level5 = null;
            $country = null;
            $postal = null;
            $address = null;

            foreach($googlemap['results'][0]['address_components'] as $data){
                if($data['types'][0] == 'postal_code'){
                    $postal = $data['long_name'];
                }
                elseif($data['types'][0] == 'route'){
                    $route = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_5'){
                    $level5 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_4'){
                    $level4 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_3'){
                    $level3 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_2'){
                    $level2 = $data['long_name'];
                }
                elseif($data['types'][0] == 'administrative_area_level_1'){
                    $level1 = $data['long_name'];
                }
                elseif($data['types'][0] == 'country'){
                    $country = $data['long_name'];
                }
            }

            if($googlemap['results'][0]['formatted_address']){
                $address = $googlemap['results'][0]['formatted_address'];
            }

            $outletCode = null;
            $outletName = null;

            $outlet = Outlet::find($id_outlet);
            if($outlet){
                $outletCode = $outlet['outlet_code'];
                $outletCode = $outlet['outlet_name'];
            }

            $logactivity = UserLocationDetail::create([
                'id_user' => $id_user,
                'id_reference' => $id_transaction,
                'id_outlet' => $id_outlet,
                'outlet_code' => $outletCode,
                'outlet_name' => $outletName,
                'activity' => 'Transaction',
                'action' => 'Completed',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'response_json' => json_encode($googlemap),
                'route' => $route,
                'street_address' => $street,
                'administrative_area_level_5' => $level5,
                'administrative_area_level_4' => $level4,
                'administrative_area_level_3' => $level3,
                'administrative_area_level_2' => $level2,
                'administrative_area_level_1' => $level1,
                'country' => $country,
                'postal_code' => $postal,
                'formatted_address' => $address
            ]);

            if($logactivity) {
                return true;
            }
        }

        return false;
    }

    public function dataRedirect($id, $type, $success)
    {
        $button = '';

        $list = Transaction::where('transaction_receipt_number', $id)->first();
        if (empty($list)) {
            return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
        }

        $dataEncode = [
            'transaction_receipt_number'   => $id,
            'type' => $type,
        ];

        if (isset($success)) {
            $dataEncode['trx_success'] = $success;
            $button = 'LIHAT NOTA';
        }

        $title = 'Sukses';
        if ($list['transaction_payment_status'] == 'Pending') {
            $title = 'Pending';
        }

        if ($list['transaction_payment_status'] == 'Terbayar') {
            $title = 'Terbayar';
        }

        if ($list['transaction_payment_status'] == 'Sukses') {
            $title = 'Sukses';
        }

        if ($list['transaction_payment_status'] == 'Gagal') {
            $title = 'Gagal';
        }

        $encode = json_encode($dataEncode);
        $base = base64_encode($encode);

        $send = [
            'button'                     => $button,
            'title'                      => $title,
            'payment_status'             => $list['transaction_payment_status'],
            'transaction_receipt_number' => $list['transaction_receipt_number'],
            'transaction_grandtotal'     => $list['transaction_grandtotal'],
            'type'                       => $type,
            'url'                        => env('VIEW_URL').'/transaction/web/view/detail?data='.$base
        ];

        return $send;
    }

    public function outletNotif($id_trx, $fromCron = false)
    {
        $trx = Transaction::where('id_transaction', $id_trx)->first();
        if ($trx['trasaction_type'] == 'Pickup Order') {
            $detail = TransactionPickup::where('id_transaction', $id_trx)->first();
        } else {
            $detail = TransactionShipment::where('id_transaction', $id_trx)->first();
        }

        $dataProduct = TransactionProduct::where('id_transaction', $id_trx)->with('product')->get();

        $count = count($dataProduct);
        $stringBody = "";
        $totalSemua = 0;

        foreach ($dataProduct as $key => $value) {
            $totalSemua += $value['transaction_product_qty'];
            $stringBody .= $value['product']['product_name']." - ".$value['transaction_product_qty']." pcs \n";
        }

        // return $stringBody;

        $outletToken = OutletToken::where('id_outlet', $trx['id_outlet'])->get();

        if (isset($detail['pickup_by'])) {
            if ($detail['pickup_by'] == 'Customer') {
                $type = 'Pickup';
                if(isset($detail['pickup_at'])){
                    $type = $type.' ('.date('H:i', strtotime($detail['pickup_at'])).' )';
                }
            }else{
                $type = 'Delivery';
            }

        } else {
            $type = 'Delivery';
        }

        $user = User::where('id', $trx['id_user'])->first();
        if (!empty($outletToken)) {
            if(env('PUSH_NOTIF_OUTLET') == 'fcm'){
                $tokens = $outletToken->pluck('token')->toArray();
                if(!empty($tokens)){
                    $subject = $type.' - Rp. '.number_format($trx['transaction_grandtotal'], 0, ',', '.').' - '.$totalSemua.' pcs - '.$detail['order_id'].' - '.$user['name'];
                    $dataPush = ['type' => 'trx', 'id_reference'=> $id_trx];
                    if ($detail['pickup_type'] == 'set time') {
                        $replacer = [
                            ['%name%', '%receipt_number%', '%order_id%'],
                            [$user->name, $trx->receipt_number, $detail['order_id']],
                        ];
                        // $setting_msg = json_decode(MyHelper::setting('transaction_set_time_notif_message_outlet','value_text'), true);
                        if (!$fromCron) {
                            $dataPush += [
                                'push_notif_local' => 1,
                                'title_5mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 5 menit lagi'),
                                'msg_5mnt'         => str_replace($replacer[0], $replacer[1], 'Pesanan sudah siap kan?'),
                                'title_15mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 15 menit lagi'),
                                'msg_15mnt'         => str_replace($replacer[0], $replacer[1], 'Segera persiapkan pesanan'),
                                'pickup_time'       => $detail->pickup_at,
                            ];
                        } else {
                            $dataPush += [
                                'push_notif_local' => 0
                            ];                        
                        }
                    } else {
                        $dataPush += [
                            'push_notif_local' => 0
                        ];                        
                    }
                    $push = PushNotificationHelper::sendPush($tokens, $subject, $stringBody, null, $dataPush);
                }
            }else{
                $dataArraySend = [];

                foreach ($outletToken as $key => $value) {
                    $dataOutletSend = [
                        'to'    => $value['token'],
                        'title' => $type.' - Rp. '.number_format($trx['transaction_grandtotal'], 0, ',', '.').' - '.$totalSemua.' pcs - '.$detail['order_id'].' - '.$user['name'].'',
                        'body'  => $stringBody,
                        'data'  => ['order_id' => $detail['order_id']]
                    ];
                    if ($detail['pickup_type'] == 'set time') {
                        $replacer = [
                            ['%name%', '%receipt_number%', '%order_id%'],
                            [$user->name, $trx->receipt_number, $detail['order_id']],
                        ];
                        // $setting_msg = json_decode(MyHelper::setting('transaction_set_time_notif_message_outlet','value_text'), true);
                        if (!$fromCron) {
                            $dataOutletSend += [
                                'push_notif_local' => 1,
                                'title_5mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 5 menit lagi'),
                                'msg_5mnt'         => str_replace($replacer[0], $replacer[1], 'Pesanan sudah siap kan?'),
                                'title_15mnt'       => str_replace($replacer[0], $replacer[1], 'Pesanan %order_id% diambil 15 menit lagi'),
                                'msg_15mnt'         => str_replace($replacer[0], $replacer[1], 'Segera persiapkan pesanan'),
                                'pickup_time'       => $detail->pickup_at,
                            ];
                        } else {
                            $dataOutletSend += [
                                'push_notif_local' => 0
                            ];
                        }
                    }else {
                        $dataOutletSend += [
                            'push_notif_local' => 0
                        ];
                    }
                    array_push($dataArraySend, $dataOutletSend);

                }

                $curl = $this->sendStatus('https://exp.host/--/api/v2/push/send', 'POST', $dataArraySend);
                if (!$curl) {
                    DB::rollback();
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Transaction failed']
                    ]);
                }
            }
        }

        return true;
    }

    public function sendStatus($url, $method, $data=null) {
        $client = new Client;

        $content = array(
            'headers' => [
                'host'            => 'exp.host',
                'accept'          => 'application/json',
                'accept-encoding' => 'gzip, deflate',
                'content-type'    => 'application/json'
            ],
            'json' => (array) $data
        );

        try {
            $response =  $client->request($method, $url, $content);
            return json_decode($response->getBody(), true);
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            try{

                if($e->getResponse()){
                    $response = $e->getResponse()->getBody()->getContents();

                    $error = json_decode($response, true);

                    if(!$error) {
                        return $e->getResponse()->getBody();
                    } else {
                        return $error;
                    }
                } else return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];

            } catch(Exception $e) {
                return ['status' => 'fail', 'messages' => [0 => 'Check your internet connection.']];
            }
        }
    }

     public function getrandomstring($length = 120) {

       global $template;
       settype($template, "string");

       $template = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring;
    }

     public function getrandomnumber($length) {

       global $template;
       settype($template, "string");

       $template = "0987654321";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring;
    }

    public function checkPromoGetPoint($promo_source)
    {
    	if (empty($promo_source)) {
    		return 1;
    	}

    	if ($promo_source != 'promo_code' && $promo_source != 'voucher_online' && $promo_source != 'voucher_offline' && $promo_source != 'subscription') {
    		return 0;
    	}

    	$config = app($this->promo)->promoGetCashbackRule();
    	// $getData = Configs::whereIn('config_name',['promo code get point','voucher offline get point','voucher online get point'])->get()->toArray();

    	// foreach ($getData as $key => $value) {
    	// 	$config[$value['config_name']] = $value['is_active'];
    	// }

    	if ($promo_source == 'promo_code') {
    		if ($config['promo code get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	if ($promo_source == 'voucher_online') {
    		if ($config['voucher online get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	if ($promo_source == 'voucher_offline') {
    		if ($config['voucher offline get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	if ($promo_source == 'subscription') {
    		if ($config['subscription get point'] == 1) {
    			return 1;
    		}else{
    			return 0;
    		}
    	}

    	return 0;
    }
    public function cancelTransaction(Request $request)
    {
        if ($request->id) {
            $trx = Transaction::where(['id_transaction' => $request->id, 'id_user' => $request->user()->id])->where('transaction_payment_status', '<>', 'Completed')->first();
        } else {
            $trx = Transaction::where(['transaction_receipt_number' => $request->receipt_number, 'id_user' => $request->user()->id])->where('transaction_payment_status', '<>', 'Completed')->first();
        }
        if (!$trx) {
            return MyHelper::checkGet([],'Transaction not found');
        }

        if($trx->transaction_payment_status != 'Pending'){
            return MyHelper::checkGet([],'Transaction cannot be canceled');
        }
        $user = $request->user();
        $payment_type = $trx->trasaction_payment_type;
        if ($payment_type == 'Balance') {
            $multi_payment = TransactionMultiplePayment::select('type')->where('id_transaction', $trx->id_transaction)->pluck('type')->toArray();
            foreach ($multi_payment as $pm) {
                if ($pm != 'Balance') {
                    $payment_type = $pm;
                    break;
                }
            }
        }
        switch (strtolower($payment_type)) {
            case 'ipay88':
                $errors = '';

                $cancel = \Modules\IPay88\Lib\IPay88::create()->cancel('trx',$trx,$errors, $request->last_url);

                if($cancel){
                    return ['status'=>'success'];
                }
                return [
                    'status'=>'fail',
                    'messages' => $errors?:['Something went wrong']
                ];
            case 'midtrans':
                Midtrans::expire($trx->transaction_receipt_number);
                $singleTrx = $trx;
                $singleTrx->load('outlet_name');
                $now = date('Y-m-d H:i:s');
                DB::beginTransaction();

                MyHelper::updateFlagTransactionOnline($singleTrx, 'cancel', $user);

                $singleTrx->transaction_payment_status = 'Cancelled';
                $singleTrx->void_date = $now;
                $singleTrx->save();

                //reversal balance
                $logBalance = LogBalance::where('id_reference', $singleTrx->id_transaction)->whereIn('source', ['Online Transaction', 'Transaction'])->where('balance', '<', 0)->get();
                foreach($logBalance as $logB){
                    $reversal = app($this->balance)->addLogBalance( $singleTrx->id_user, abs($logB['balance']), $singleTrx->id_transaction, 'Reversal', $singleTrx->transaction_grandtotal);
                    if (!$reversal) {
                        DB::rollback();
                        continue;
                    }
                    $order_id = TransactionPickup::select('order_id')->where('id_transaction', $singleTrx->id_transaction)->pluck('order_id')->first();
                    $send = app($this->autocrm)->SendAutoCRM('Transaction Failed Point Refund', $user->phone,
                        [
                            "outlet_name"       => $singleTrx->outlet_name->outlet_name,
                            "transaction_date"  => $singleTrx->transaction_date,
                            'id_transaction'    => $singleTrx->id_transaction,
                            'receipt_number'    => $singleTrx->transaction_receipt_number,
                            'received_point'    => (string) abs($logB['balance']),
                            'order_id'          => $order_id,
                        ]
                    );
                }

                // delete promo campaign report
                if ($singleTrx->id_promo_campaign_promo_code) {
                    $update_promo_report = app($this->promo_campaign)->deleteReport($singleTrx->id_transaction, $singleTrx->id_promo_campaign_promo_code);
                    if (!$update_promo_report) {
                        DB::rollBack();
                        return ['status'=>'fail', 'messages' => ['Failed revert promo']];
                    }   
                }

                // return voucher
                $update_voucher = app($this->voucher)->returnVoucher($singleTrx->id_transaction);

                // return subscription
                $update_subscription = app($this->subscription)->returnSubscription($singleTrx->id_transaction);

                if (!$update_voucher) {
                    DB::rollback();
                    return ['status'=>'fail', 'messages' => ['Failed return voucher']];
                }
                DB::commit();
                return ['status'=>'success'];
        }
        return ['status' => 'fail', 'messages' => ["Cancel $payment_type transaction is not supported yet"]];
    }

    public function availablePayment(Request $request)
    {
        $availablePayment = config('payment_method');

        $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
        $payments = [];

        $config = [
            'credit_card_payment_gateway' => MyHelper::setting('credit_card_payment_gateway', 'value', 'Ipay88')
        ];
        $last_status = [];
        foreach ($setting as $value) {
            $payment = $availablePayment[$value['code'] ?? ''] ?? false;
            if (!$payment) {
                unset($availablePayment[$value['code']]);
                continue;
            }

            if (is_array($payment['available_time'] ?? false)) {
                $available_time = $payment['available_time'];
                $current_time = time();
                if ($current_time < strtotime($available_time['start']) || $current_time > strtotime($available_time['end'])) {
                    $value['status'] = 0;
                }
            }

            if (!($payment['status'] ?? false) || (!$request->show_all && !($value['status'] ?? false))) {
                unset($availablePayment[$value['code']]);
                continue;
            }

            if(!is_numeric($payment['status'])){
                $var = explode(':',$payment['status']);
                if(($config[$var[0]]??false) != ($var[1]??true)) {
                    $last_status[$var[0]] = $value['status'];
                    unset($availablePayment[$value['code']]);
                    continue;
                }
            }
            $payments[] = [
                'code'            => $value['code'],
                'payment_gateway' => $payment['payment_gateway'],
                'payment_method'  => $payment['payment_method'],
                'logo'            => $payment['logo'],
                'text'            => $payment['text'],
                'description'     => $value['description'],
                'status'          => (int) $value['status'] ? 1 : 0
            ];
            unset($availablePayment[$value['code']]);
        }
        foreach ($availablePayment as $code => $payment) {
            $status = 0;
            if (!$payment['status'] || !is_numeric($payment['status'])) {
                $var = explode(':',$payment['status']);
                if(($config[$var[0]]??false) != ($var[1]??true)) {
                    continue;
                }
                $status = (int) ($last_status[$var[0]] ?? 0);
            }
            if($request->show_all || $status) {
                $payments[] = [
                    'code'            => $code,
                    'payment_gateway' => $payment['payment_gateway'],
                    'payment_method'  => $payment['payment_method'],
                    'logo'            => $payment['logo'],
                    'text'            => $payment['text'],
                    'description'     => $payment['description'],
                    'status'          => $status
                ];
            }
        }
        return MyHelper::checkGet($payments);
    }
    /**
     * update available payment
     * @param
     * {
     *     payments: [
     *         {'code': 'xxx', status: 1}
     *     ]
     * }
     * @return [type]           [description]
     */
    public function availablePaymentUpdate(Request $request)
    {
        $availablePayment = config('payment_method');
        foreach ($request->payments as $key => $value) {
            $payment = $availablePayment[$value['code'] ?? ''] ?? false;
            if (!$payment || !($payment['status'] ?? false)) {
                continue;
            }
            $payments[] = [
                'code'     => $value['code'],
                'status'   => $value['status'] ?? 0,
                'position' => $key + 1,
            ];
        }
        $update = Setting::updateOrCreate(['key' => 'active_payment_methods'], ['value_text' => json_encode($payments)]);
        return MyHelper::checkUpdate($update);
    }

    public function mergeProducts($items)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'bonus' => isset($item['bonus'])?$item['bonus']:'0',
                'id_brand' => $item['id_brand'],
                'id_product' => $item['id_product'],
                'id_product_variant_group' => ($item['id_product_variant_group']??null) ?: null,
                'product_name' => $item['product_name'],
                'product_code' => $item['product_code'],
                'product_price' => 0,
                'product_price_total' => 0,
                'photo' => ''
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

    public function mergeBundlingProducts($items, $bundlinQty)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'id_brand' => $item['id_brand'],
                'id_product' => $item['id_product'],
                'id_product_variant_group' => ($item['id_product_variant_group']??null) ?: null,
                'id_bundling_product' => $item['id_bundling_product'],
                'product_name' => $item['product_name'],
                //'note' => $item['note'],
                //'extra_modifiers' => $item['extra_modifiers']??[],
                'variants' => array_map("unserialize", array_unique(array_map("serialize", array_map(function($i){
                    return [
                        'id_product_variant' => $i['id_product_variant'],
                        'product_variant_name' => $i['product_variant_name']
                    ];
                },$item['variants']??[])))),
//                'modifiers' => array_map(function($i){
//                    return [
//                        "id_product_modifier"=> $i['id_product_modifier'],
//                        "code"=> $i['code'],
//                        "text"=> $i['text'],
//                        "product_modifier_price"=> $i['product_modifier_price'] ,
//                        "modifier_type"=> $i['modifier_type'],
//                        'qty' => $i['qty']
//                    ];
//                },$item['modifiers']??[]),
            ];
            //usort($new_item['modifiers'],function($a, $b) { return $a['id_product_modifier'] <=> $b['id_product_modifier']; });
            $pos = array_search($new_item, $new_items);
            if($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['product_qty'];
                $id_custom[] = $item['id_custom']??0;
            } else {
                $item_qtys[$pos] += $item['product_qty'];
            }
        }
        // update qty
        foreach ($new_items as $key => &$value) {
            $value['product_qty'] = $item_qtys[$key];
            foreach ($value['modifiers'] as &$mod){
                $mod['product_modifier_price'] = $mod['product_modifier_price'] * $item_qtys[$key] * $bundlinQty;
            }
        }

        return $new_items;
    }

    public function mergeBundlingDetail($items)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'id_bundling' => $item['id_bundling'],
                'bundling_name' => $item['bundling_name'],
                'bundling_price_no_discount' => $item['bundling_price_no_discount'],
                'bundling_subtotal' => $item['bundling_subtotal'],
                'bundling_sub_item' => $item['bundling_sub_item'],
                'bundling_sub_item_raw' => $item['bundling_sub_item_raw'],
                'bundling_sub_price_no_discount' => $item['bundling_sub_price_no_discount'],
                'products' => array_map(function($i){
                    return [
                        "id_brand"=> $i['id_brand'],
                        "id_product"=> $i['id_product'],
                        "id_product_variant_group"=> $i['id_product_variant_group'],
                        "id_bundling_product"=> $i['id_bundling_product'] ,
                        "product_name"=> $i['product_name'],
                        'product_code' =>  $i['product_code']??"",
                        //'note' => $i['note'],
                        'variants' => $i['variants'],
                        //'modifiers' => $i['modifiers'],
                        'product_qty' => $i['product_qty'],
                        //'extra_modifiers' => $i['extra_modifiers']??[]
                    ];
                },$item['products']??[]),
            ];
            usort($new_item['products'],function($a, $b) { return $a['id_product'] <=> $b['id_product']; });
            $pos = array_search($new_item, $new_items);
            if($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['bundling_qty'];
                $id_custom[] = $item['id_custom']??0;
            } else {
                $item_qtys[$pos] += $item['bundling_qty'];
            }
        }

        // update qty
        foreach ($new_items as $key => &$value) {
            $value['bundling_qty'] = $item_qtys[$key];
            $value['id_custom'] = $id_custom[$key];
            $value['bundling_price_no_discount'] = $value['bundling_sub_price_no_discount'] * $item_qtys[$key];
            $value['bundling_subtotal'] = $value['bundling_sub_item_raw'] * $item_qtys[$key];
        }

        return $new_items;
    }

    public function mergeBundling($items)
    {
        $new_items = [];
        $item_qtys = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                'id_bundling' => $item['id_bundling'],
                'bundling_name' => $item['bundling_name'],
                'bundling_code' => $item['bundling_code'],
                'bundling_base_price' => $item['bundling_base_price'],
                'bundling_price_total' => $item['bundling_price_total'],
                'products' => array_map(function($i){
                    return [
                        "id_brand"=> $i['id_brand'],
                        "id_product"=> $i['id_product'],
                        "id_product_variant_group"=> $i['id_product_variant_group'],
                        "id_bundling_product"=> $i['id_bundling_product'] ,
                        "product_name"=> $i['product_name'],
                        "product_code" => $i['product_code'],
                        //'note' => $i['note'],
                        'variants' => $i['variants'],
                        //'modifiers' => $i['modifiers'],
                        //'extra_modifiers' => $i['extra_modifiers']??[]
                    ];
                },$item['products']??[]),
            ];
            usort($new_item['products'],function($a, $b) { return $a['id_product'] <=> $b['id_product']; });
            $pos = array_search($new_item, $new_items);
            if($pos === false) {
                $new_items[] = $new_item;
                $item_qtys[] = $item['bundling_qty'];
                $id_custom[] = $item['id_custom']??0;
            } else {
                $item_qtys[$pos] += $item['bundling_qty'];
            }
        }

        // update qty
        foreach ($new_items as $key => &$value) {
            $value['bundling_qty'] = $item_qtys[$key];
            $value['id_custom'] = $id_custom[$key];
            $value['bundling_price_total'] = $value['bundling_price_total'] * $item_qtys[$key];
        }

        return $new_items;
    }

    public function mergeService($items){
        $new_items = [];
        $id_custom = [];

        // create unique array
        foreach ($items as $item) {
            $new_item = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $item['id_brand'],
                "id_product" => $item['id_product'],
                "product_code" => $item['product_code'],
                "product_name" => $item['product_name'],
                "product_price" => $item['product_price'],
                "id_user_hair_stylist" => $item['id_user_hair_stylist'],
                "user_hair_stylist_name" => $item['user_hair_stylist_name'],
                "booking_date" => $item['booking_date'],
                "booking_date_display" => $item['booking_date_display'],
                "booking_time" => $item['booking_time'],
                "error_msg" => $item['error_msg']??""
            ];
            $pos = array_search($new_item, $new_items);
            if($pos === false) {
                $new_items[] = $new_item;
                $id_custom[] = $item['id_custom']??0;
            }
        }

        return $new_items;
    }

    public function getPlasticInfo($plastic, $outlet_plastic_used_status){
        if((isset($plastic['status']) && $plastic['status'] == 'success') && (isset($outlet_plastic_used_status) && $outlet_plastic_used_status == 'Active')){
            $result['plastic'] = $plastic['result'];
            $result['plastic']['status'] = $outlet_plastic_used_status;
            $result['plastic']['item'] = array_values(
                array_filter($result['plastic']['item'], function($item){
                    return $item['total_used'] > 0;
                })
            );
        }else{
            $result['plastic'] = ['item' => [], 'plastic_price_total' => 0];
            $result['plastic']['status'] = $outlet_plastic_used_status;
        }

        return $result['plastic'];
    }

    public function triggerReversal(Request $request)
    {
        // cari transaksi yang pakai balance, atau split balance, sudah cancelled tapi balance nya tidak balik, & user nya ada
        $trxs = Transaction::select('transactions.id_transaction','transactions.id_user', 'transaction_receipt_number', 'transaction_grandtotal', 'log_bayar.balance as bayar', 'log_reversal.balance as reversal')
            ->join('transaction_multiple_payments', function($join) {
                $join->on('transaction_multiple_payments.id_transaction', 'transactions.id_transaction')
                    ->where('transaction_multiple_payments.type', 'Balance');
            })
            ->join('log_balances as log_bayar', function($join) {
                $join->on('log_bayar.id_reference', 'transactions.id_transaction')
                    ->whereIn('log_bayar.source', ['Transaction', 'Online Transaction'])
                    ->where('log_bayar.balance', '<', 0);
            })
            ->leftJoin('log_balances as log_reversal', function($join) {
                $join->on('log_reversal.id_reference', 'transactions.id_transaction')
                    ->whereIn('log_reversal.source', ['Transaction Failed', 'Reversal'])
                    ->where('log_reversal.balance', '>', 0);
            })
            ->join('users', 'users.id', '=', 'transactions.id_user')
            ->where([
                'transaction_payment_status' => 'Cancelled'
            ]);
        $summary = [
            'all_with_point' => 0,
            'already_reversal' => 0,
            'new_reversal' => 0
        ];
        $reversal = [];
        foreach ($trxs->cursor() as $trx) {
            $summary['all_with_point']++;
            if ($trx->reversal) {
                $summary['already_reversal']++;
            } else {
                if (strtolower($request->request_type) == 'reversal') {
                    app($this->balance)->addLogBalance( $trx->id_user, abs($trx->bayar), $trx->id_transaction, 'Reversal', $trx->transaction_grandtotal);
                }
                $summary['new_reversal']++;
                $reversal[] = [
                    'id_transaction' => $trx->id_transaction,
                    'receipt_number' => $trx->transaction_receipt_number,
                    'balance_nominal' => abs($trx->bayar),
                    'grandtotal' => $trx->transaction_grandtotal,
                ];
            }
        }
        return [
            'status' => 'success',
            'results' => [
                'type' => strtolower($request->request_type) == 'reversal' ? 'DO REVERSAL' : 'SHOW REVERSAL',
                'summary' => $summary,
                'new_reversal_detail' => $reversal
            ]
        ];
    }

    function insertBundlingProduct($data, $trx, $outlet, $post, &$productMidtrans, &$userTrxProduct){
        $type = $post['type'];
        $totalWeight = 0;
        foreach ($data as $itemBundling){
            $dataItemBundling = [
                'id_transaction' => $trx['id_transaction'],
                'id_bundling' => $itemBundling['id_bundling'],
                'id_outlet' => $trx['id_outlet'],
                'transaction_bundling_product_base_price' => $itemBundling['transaction_bundling_product_base_price'],
                'transaction_bundling_product_subtotal' => $itemBundling['transaction_bundling_product_subtotal'],
                'transaction_bundling_product_qty' => $itemBundling['bundling_qty'],
                'transaction_bundling_product_total_discount' => $itemBundling['transaction_bundling_product_total_discount']
            ];

            $createTransactionBundling = TransactionBundlingProduct::create($dataItemBundling);

            if(!$createTransactionBundling){
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'messages'  => ['Insert Bundling Product Failed']
                ];
            }

            foreach ($itemBundling['products'] as $itemProduct){
                $checkProduct = Product::where('id_product', $itemProduct['id_product'])->first();
                if (empty($checkProduct)) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Product Not Found '.$itemProduct['product_name']]
                    ];
                }

                $checkDetailProduct = ProductDetail::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $trx['id_outlet']])->first();
                if (!empty($checkDetailProduct) && $checkDetailProduct['product_detail_stock_status'] == 'Sold Out') {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].' tidak tersedia dan akan terhapus dari cart.']
                    ];
                }

                if(!isset($itemProduct['note'])){
                    $itemProduct['note'] = null;
                }

                $productPrice = 0;

                $product = BundlingProduct::join('products', 'products.id_product', 'bundling_product.id_product')
                    ->leftJoin('product_global_price as pgp', 'pgp.id_product', '=', 'products.id_product')
                    ->join('bundling', 'bundling.id_bundling', 'bundling_product.id_bundling')
                    ->where('bundling_product.id_bundling_product', $itemProduct['id_bundling_product'])
                    ->select('products.product_visibility', 'pgp.product_global_price',  'products.product_variant_status',
                        'bundling_product.*', 'bundling.bundling_name', 'bundling.bundling_code', 'products.*')
                    ->first();
                $getProductDetail = ProductDetail::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first();
                $product['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;
                $id_product_variant_group = $product['id_product_variant_group']??null;

                if($product['visibility_outlet'] == 'Hidden' || (empty($product['visibility_outlet']) && $product['product_visibility'] == 'Hidden')){
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'product_sold_out_status' => true,
                        'messages'  => ['Product '.$checkProduct['product_name'].'pada '.$product['bundling_name'].' tidak tersedia']
                    ];
                }

                if($product['product_variant_status'] && !empty($product['id_product_variant_group'])){
                    $checkAvailable = ProductVariantGroup::where('id_product_variant_group', $product['id_product_variant_group'])->first();
                    if($checkAvailable['product_variant_group_visibility'] == 'Hidden'){
                        DB::rollback();
                        return [
                            'status'    => 'fail',
                            'product_sold_out_status' => true,
                            'messages'  => ['Product '.$checkProduct['product_name'].'pada '.$product['bundling_name'].' tidak tersedia']
                        ];
                    }else{
                        if($outlet['outlet_different_price'] == 1){
                            $price = ProductVariantGroupSpecialPrice::where('id_product_variant_group', $product['id_product_variant_group'])->where('id_outlet', $post['id_outlet'])->first()['product_variant_group_price']??0;
                        }else{
                            $price = $checkAvailable['product_variant_group_price']??0;
                        }
                    }
                }elseif(!empty($product['id_product'])){
                    if($outlet['outlet_different_price'] == 1){
                        $price = ProductSpecialPrice::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price']??0;
                    }else{
                        $price = $product['product_global_price'];
                    }
                }

                $price = (float)$price??0;
                //calculate discount produk
                if(strtolower($product['bundling_product_discount_type']) == 'nominal'){
                    $calculate = ($price - $product['bundling_product_discount']);
                }else{
                    $discount = $price*($product['bundling_product_discount']/100);
                    $discount = ($discount > $product['bundling_product_maximum_discount'] &&  $product['bundling_product_maximum_discount'] > 0? $product['bundling_product_maximum_discount']:$discount);
                    $calculate = ($price - $discount);
                }

                $dataProduct = [
                    'id_transaction'               => $trx['id_transaction'],
                    'id_product'                   => $checkProduct['id_product'],
                    'type'                         => $checkProduct['product_type'],
                    'id_product_variant_group'     => $itemProduct['id_product_variant_group']??null,
                    'id_brand'                     => $itemProduct['id_brand'],
                    'id_outlet'                    => $trx['id_outlet'],
                    'id_user'                      => $trx['id_user'],
                    'transaction_product_qty'      => $itemProduct['product_qty']*$itemBundling['bundling_qty'],
                    'transaction_product_bundling_qty' => $itemProduct['product_qty'],
                    'transaction_product_price'    => $itemProduct['transaction_product_price'],
                    'transaction_product_bundling_price' => $calculate,
                    'transaction_product_price_base' => NULL,
                    'transaction_product_price_tax'  => NULL,
                    'transaction_product_discount'   => 0,
                    'transaction_product_discount_all'   => $itemProduct['transaction_product_discount_all'],
                    'transaction_product_bundling_price'   => $itemProduct['transaction_product_bundling_price'],
                    'transaction_product_base_discount' => 0,
                    'transaction_product_qty_discount'  => 0,
                    'transaction_product_subtotal' => $itemProduct['transaction_product_subtotal'],
                    'transaction_product_net' => $itemProduct['transaction_product_net'],
                    'transaction_variant_subtotal' => $itemProduct['transaction_variant_subtotal'],
                    'transaction_product_note'     => $itemProduct['note'],
                    'id_transaction_bundling_product' => $createTransactionBundling['id_transaction_bundling_product'],
                    'id_bundling_product' => $itemProduct['id_bundling_product'],
                    'transaction_product_bundling_discount' => $itemProduct['transaction_product_bundling_discount'],
                    'transaction_product_bundling_charged_outlet' => $itemProduct['transaction_product_bundling_charged_outlet'],
                    'transaction_product_bundling_charged_central' => $itemProduct['transaction_product_bundling_charged_central'],
                    'created_at'                   => date('Y-m-d', strtotime($trx['transaction_date'])).' '.date('H:i:s'),
                    'updated_at'                   => date('Y-m-d H:i:s')
                ];

                $trx_product = TransactionProduct::create($dataProduct);
                if (!$trx_product) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Insert Product Transaction Failed']
                    ];
                }
                if(strtotime($trx['transaction_date'])){
                    $trx_product->created_at = strtotime($trx['transaction_date']);
                }
                $insert_modifier = [];
                $mod_subtotal = 0;
                $more_mid_text = '';
                $selectExtraModifier = ProductModifier::whereIn('id_product_modifier', $itemProduct['extra_modifiers']??[])->get()->toArray();
                $mergetExtranAndModifier = array_merge($selectExtraModifier, $itemProduct['modifiers']??[]);
                if(isset($mergetExtranAndModifier)){
                    foreach ($mergetExtranAndModifier as $modifier) {
                        $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
                        $qty_product_modifier = 1;
                        if(isset($modifier['qty'])){
                            $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
                        }

                        $mod = ProductModifier::select('product_modifiers.id_product_modifier','code',
                            DB::raw('(CASE
                        WHEN product_modifiers.text_detail_trx IS NOT NULL 
                        THEN product_modifiers.text_detail_trx
                        ELSE product_modifiers.text
                    END) as text'),
                            'product_modifier_stock_status',\DB::raw('coalesce(product_modifier_price, 0) as product_modifier_price'), 'id_product_modifier_group', 'modifier_type')
                            // product visible
                            ->leftJoin('product_modifier_details', function($join) use ($post) {
                                $join->on('product_modifier_details.id_product_modifier','=','product_modifiers.id_product_modifier')
                                    ->where('product_modifier_details.id_outlet',$post['id_outlet']);
                            })
                            ->where(function($query){
                                $query->where('product_modifier_details.product_modifier_visibility','=','Visible')
                                    ->orWhere(function($q){
                                        $q->whereNull('product_modifier_details.product_modifier_visibility')
                                            ->where('product_modifiers.product_modifier_visibility', 'Visible');
                                    });
                            })
                            ->where(function($q) {
                                $q->where(function($q){
                                    $q->where('product_modifier_stock_status','Available')->orWhereNull('product_modifier_stock_status');
                                })->orWhere('product_modifiers.modifier_type', '=', 'Modifier Group');
                            })
                            ->where(function($q){
                                $q->where('product_modifier_status','Active')->orWhereNull('product_modifier_status');
                            })
                            ->groupBy('product_modifiers.id_product_modifier');
                        if($outlet['outlet_different_price']){
                            $mod->leftJoin('product_modifier_prices',function($join) use ($post){
                                $join->on('product_modifier_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                                $join->where('product_modifier_prices.id_outlet',$post['id_outlet']);
                            });
                        }else{
                            $mod->leftJoin('product_modifier_global_prices',function($join) use ($post){
                                $join->on('product_modifier_global_prices.id_product_modifier','=','product_modifiers.id_product_modifier');
                            });
                        }
                        $mod = $mod->find($id_product_modifier);
                        if(!$mod){
                            return [
                                'status' => 'fail',
                                'messages' => ['Modifier not found']
                            ];
                        }
                        $mod = $mod->toArray();
                        $insert_modifier[] = [
                            'id_transaction_product'=>$trx_product['id_transaction_product'],
                            'id_transaction'=>$trx['id_transaction'],
                            'id_product'=>$checkProduct['id_product'],
                            'id_product_modifier'=>$id_product_modifier,
                            'id_product_modifier_group'=>$mod['modifier_type'] == 'Modifier Group' ? $mod['id_product_modifier_group'] : null,
                            'id_outlet'=>$trx['id_outlet'],
                            'id_user'=>$trx['id_user'],
                            'type'=>$mod['type']??'',
                            'code'=>$mod['code']??'',
                            'text'=>$mod['text']??'',
                            'qty'=>$qty_product_modifier,
                            'transaction_product_modifier_price'=>$mod['product_modifier_price']*$qty_product_modifier,
                            'datetime'=>$trx['transaction_date']??date(),
                            'trx_type'=>$type,
                            'created_at'                   => date('Y-m-d H:i:s'),
                            'updated_at'                   => date('Y-m-d H:i:s')
                        ];
                        $mod_subtotal += $mod['product_modifier_price']*$qty_product_modifier;
                        if($qty_product_modifier>1){
                            $more_mid_text .= ','.$qty_product_modifier.'x '.$mod['text'];
                        }else{
                            $more_mid_text .= ','.$mod['text'];
                        }
                    }

                }

                $trx_modifier = TransactionProductModifier::insert($insert_modifier);
                if (!$trx_modifier) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Insert Product Modifier Transaction Failed']
                    ];
                }
                $insert_variants = [];
                foreach ($itemProduct['trx_variants'] as $id_product_variant => $product_variant_price) {
                    $insert_variants[] = [
                        'id_transaction_product' => $trx_product['id_transaction_product'],
                        'id_product_variant' => $id_product_variant,
                        'transaction_product_variant_price' => $product_variant_price,
                        'created_at'                   => date('Y-m-d H:i:s'),
                        'updated_at'                   => date('Y-m-d H:i:s')
                    ];
                }

                $trx_variants = TransactionProductVariant::insert($insert_variants);
                $trx_product->transaction_modifier_subtotal = $mod_subtotal;
                $trx_product->save();
                $dataProductMidtrans = [
                    'id'       => $checkProduct['id_product'],
                    'price'    => $calculate + $mod_subtotal,
                    'name'     => $checkProduct['product_name'],
                    'quantity' => $itemBundling['bundling_qty'],
                ];
                array_push($productMidtrans, $dataProductMidtrans);
                $totalWeight += $checkProduct['product_weight'] * 1;

                $dataUserTrxProduct = [
                    'id_user'       => $trx['id_user'],
                    'id_product'    => $checkProduct['id_product'],
                    'product_qty'   => 1,
                    'last_trx_date' => $trx['transaction_date']
                ];
                array_push($userTrxProduct, $dataUserTrxProduct);
            }
        }

        return [
            'status'    => 'success'
        ];
    }

    function insertServiceProduct($data, $trx, $outlet, $post, &$productMidtrans, &$userTrxProduct){
        foreach ($data as $itemProduct){
            $product = Product::leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                            ->where('products.id_product', $itemProduct['id_product'])
                            ->where('product_type', 'service')
                            ->with(['product_service_use_detail'])
                            ->select('products.*', 'brand_product.id_brand')->first();

            if (empty($product)) {
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'messages'  => ['Product Service Not Found '.$itemProduct['product_name']]
                ];
            }

            foreach ($product['product_service_use_detail'] as $stock){
                if($stock['quantity_use'] > $stock['product_detail_stock_service']){
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Product use in service '.$itemProduct['product_name']. ' not available']
                    ];
                }
            }

            $getProductDetail = ProductDetail::where('id_product', $itemProduct['id_product'])->where('id_outlet', $post['id_outlet'])->first();
            $product['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

            if($product['visibility_outlet'] == 'Hidden' || (empty($product['visibility_outlet']) && $product['product_visibility'] == 'Hidden')){
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'product_sold_out_status' => true,
                    'messages'  => ['Product '.$itemProduct['product_name'].' tidak tersedia']
                ];
            }

            if($outlet['outlet_different_price'] == 1){
                $price = ProductSpecialPrice::where('id_product', $product['id_product'])->where('id_outlet', $post['id_outlet'])->first()['product_special_price']??0;
            }else{
                $price = ProductGlobalPrice::where('id_product', $product['id_product'])->first()['product_global_price']??0;
            }

            $price = (int)$price??0;

            if(empty($price)){
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'product_sold_out_status' => true,
                    'messages'  => ['Invalid price for product '.$itemProduct['product_name']]
                ];
            }

            $dataProduct = [
                'id_transaction'               => $trx['id_transaction'],
                'id_product'                   => $product['id_product'],
                'type'                         => 'Service',
                'id_brand'                     => $product['id_brand'],
                'id_outlet'                    => $trx['id_outlet'],
                'id_user'                      => $trx['id_user'],
                'transaction_product_qty'      => 1,
                'transaction_product_price'    => $price,
                'transaction_product_discount'   => 0,
                'transaction_product_discount_all'   => 0,
                'transaction_product_base_discount' => 0,
                'transaction_product_qty_discount'  => 0,
                'transaction_product_subtotal' => $price,
                'transaction_product_net' => $price,
                'transaction_product_note'     => null,
                'created_at'                   => date('Y-m-d', strtotime($trx['transaction_date'])).' '.date('H:i:s'),
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

            //insert to transaction product service
            $order_id = 'IXBX-'.MyHelper::createrandom(5, 'Angka');
            $product_service = TransactionProductService::create([
                'order_id' => $order_id,
                'id_transaction' => $trx['id_transaction'],
                'id_transaction_product' => $trx_product['id_transaction_product'],
                'id_user_hair_stylist' => $itemProduct['id_user_hair_stylist'],
                'schedule_date' => date('Y-m-d', strtotime($itemProduct['booking_date'])),
                'schedule_time' => date('H:i:s', strtotime($itemProduct['booking_time']))
            ]);
            if (!$product_service) {
                DB::rollback();
                return [
                    'status'    => 'fail',
                    'messages'  => ['Insert Data Service Transaction Failed']
                ];
            }

            $insertProductUse = [];
            foreach ($product['product_service_use_detail'] as $stock){
                $insertProductUse[] = [
                    'id_transaction' => $trx['id_transaction'],
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

            $dataProductMidtrans = [
                'id'       => $product['id_product'],
                'price'    => $price,
                'name'     => $product['product_name'],
                'quantity' => 1,
            ];
            array_push($productMidtrans, $dataProductMidtrans);

            $dataUserTrxProduct = [
                'id_user'       => $trx['id_user'],
                'id_product'    => $product['id_product'],
                'product_qty'   => 1,
                'last_trx_date' => $trx['transaction_date']
            ];
            array_push($userTrxProduct, $dataUserTrxProduct);
        }

        return [
            'status'    => 'success'
        ];
    }

    public function syncDataSubtotal(Request $request){
        $post = $request->json()->all();
        $dateStart = date('Y-m-d', strtotime($post['date_start']));
        $dateEnd = date('Y-m-d', strtotime($post['date_end']));

        $data = Transaction::whereDate('transaction_date', '>=', $dateStart)
            ->whereDate('transaction_date', '<=', $dateEnd)
            ->get()->toArray();

        foreach ($data as $dt){
            $trxDiscount = $dt['transaction_discount'];
            $discountBill = 0;
            $totalDicountItem = [];
            $subtotalFinal = [];
            $prods = TransactionProduct::where('id_transaction', $dt['id_transaction'])->get()->toArray();

            foreach ($prods as $prod){
                if(is_null($prod['id_transaction_bundling_product'])){
                    $dtUpdateTrxProd = [
                        'transaction_product_net' => $prod['transaction_product_subtotal']-$prod['transaction_product_discount'],
                        'transaction_product_discount_all' => $prod['transaction_product_discount']
                    ];
                    TransactionProduct::where('id_transaction_product', $prod['id_transaction_product'])->update($dtUpdateTrxProd);
                    array_push($totalDicountItem, $prod['transaction_product_discount']);
                    array_push($subtotalFinal, $prod['transaction_product_subtotal']);
                }else{
                    $bundlingQty = $prod['transaction_product_bundling_qty'];
                    if($bundlingQty == 0){
                        $bundlingQty = $prod['transaction_product_qty'];
                    }
                    $perItem = $prod['transaction_product_subtotal']/$bundlingQty;
                    $productSubtotalFinal = $perItem * $prod['transaction_product_qty'];
                    $productSubtotalFinalNoDiscount = ($perItem + $prod['transaction_product_bundling_discount']) * $prod['transaction_product_qty'];
                    $discount = $prod['transaction_product_bundling_discount'] * $prod['transaction_product_qty'];
                    $dtUpdateTrxProd = [
                        'transaction_product_net' => $productSubtotalFinal,
                        'transaction_product_discount_all' => $discount
                    ];
                    array_push($totalDicountItem, $discount);
                    array_push($subtotalFinal, $productSubtotalFinalNoDiscount);
                    TransactionProduct::where('id_transaction_product', $prod['id_transaction_product'])->update($dtUpdateTrxProd);
                }
            }

            if(empty($totalDicountItem)){
                $discountBill = $trxDiscount;
            }
            $dtUpdateTrx = [
                'transaction_gross' => array_sum($subtotalFinal),
                'transaction_discount_item' => array_sum($totalDicountItem),
                'transaction_discount_bill' => $discountBill
            ];
            Transaction::where('id_transaction', $dt['id_transaction'])->update($dtUpdateTrx);
        }

        return 'success';
    }

    public function listAvailableDelivery(Request $request)
    {
        $post = $request->json()->all();
        $setting  = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
        $setting_default = Setting::where('key', 'default_delivery')->first()->value??null;
        $delivery = [];

        foreach ($setting as $value) {
            if(!empty($post['all'])){
                if(!empty($value['logo'])){
                    $value['logo'] = config('url.storage_url_api').$value['logo'].'?='.time();
                }elseif(!empty($setting_default)){
                    $value['logo'] = config('url.storage_url_api').$setting_default.'?='.time();
                }
                $delivery[] = $value;
            }elseif($value['show_status'] == 1){
                if(!empty($value['logo'])){
                    $value['logo'] = config('url.storage_url_api').$value['logo'].'?='.time();
                }elseif(!empty($setting_default)){
                    $value['logo'] = config('url.storage_url_api').$setting_default.'?='.time();
                }
                $delivery[] = $value;
            }
        }

        usort($delivery, function($a, $b) {
            return $a['position'] - $b['position'];
        });

        $result = [
            'default_delivery' => $setting_default,
            'delivery' => $delivery
        ];
        return MyHelper::checkGet($result);
    }

    public function availableDeliveryUpdate(Request $request)
    {
        $post = $request->json()->all();
        $availableDelivery  = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
        $dtDelivery = $post['delivery']??[];
        foreach ($availableDelivery as $key => $value) {
            $check = array_search($value['code'], array_column($dtDelivery, 'code'));
            if($check !== false){
                $availableDelivery[$key]['show_status'] = $dtDelivery[$check]['show_status'];
                $availableDelivery[$key]['available_status'] = $dtDelivery[$check]['available_status'];
                $availableDelivery[$key]['position'] = $check;
                $availableDelivery[$key]['description'] = $dtDelivery[$check]['description'];
            }
        }

        $update = Setting::where('key', 'available_delivery')->update(['value_text' => json_encode($availableDelivery)]);
        if($update){
            $update = Setting::updateOrCreate(['key' => 'default_delivery'], ['value' => $post['default_delivery']]);
        }
        return MyHelper::checkUpdate($update);
    }

    public function mergeNewDelivery($data=[]){
        $jsonDecode = json_decode($data);
        if(isset($jsonDecode->data->partners) && !empty($jsonDecode->data->partners)){
            $availableDelivery  = json_decode(MyHelper::setting('available_delivery', 'value_text', '[]'), true) ?? [];
            $dataDelivery = (array)$jsonDecode->data->partners;
            foreach ($dataDelivery as $val){

            	if (empty($val)) {
            		continue;
            	}

                $check = array_search('wehelpyou_'.$val->courier, array_column($availableDelivery, 'code'));
                if($check === false){
                    $availableDelivery[] = [
                        "code" => 'wehelpyou_'.$val->courier,
                        "delivery_name" => ucfirst($val->courier),
                        "delivery_method" => "wehelpyou",
                        "show_status" => 1,
                        "available_status" => 1,
                        "logo" => "",
                        "position" => count($availableDelivery)+1
                    ];
                }
            }
            $update = Setting::where('key', 'available_delivery')->update(['value_text' => json_encode($availableDelivery)]);
        }
        return true;
    }

    public function setGrandtotalListDelivery($listDelivery, $grandtotal)
    {
    	foreach ($listDelivery as $key => $delivery) {
    		$listDelivery[$key]['total_payment'] = $grandtotal + $delivery['price'];
    	}
    	return $listDelivery;
    }

    public function getActiveCourier($listDelivery, $courier)
    {
    	foreach ($listDelivery as $delivery) {
    		if ((empty($courier) && $delivery['disable'] == 0)
    			|| $delivery['courier'] == $courier
    		) {
    			return $delivery;
    			break;
    		}
    	}

    	return null;
    }

    public function getCourierName(string $courier)
    {
    	foreach ($this->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] as $delivery) {
    		if (strpos($delivery['code'], $courier) !== false) {
				$courier = $delivery['delivery_name'];
				break;
			}
    	}
    	return $courier;
    }

    public function countTranscationPoint($post, $user)
    {
    	$post['point'] = app($this->setting_trx)->countTransaction('point', $post);
    	$post['cashback'] = app($this->setting_trx)->countTransaction('cashback', $post);

        $countUserTrx = Transaction::where('id_user', $user['id'])->where('transaction_payment_status', 'Completed')->count();

        $countSettingCashback = TransactionSetting::get();

        if ($countUserTrx < count($countSettingCashback)) {

            $post['cashback'] = $post['cashback'] * $countSettingCashback[$countUserTrx]['cashback_percent'] / 100;

            if ($post['cashback'] > $countSettingCashback[$countUserTrx]['cashback_maximum']) {
                $post['cashback'] = $countSettingCashback[$countUserTrx]['cashback_maximum'];
            }
        } else {

            $maxCash = Setting::where('key', 'cashback_maximum')->first();

            if (count($user['memberships']) > 0) {
                $post['point'] = $post['point'] * ($user['memberships'][0]['benefit_point_multiplier']) / 100;
                $post['cashback'] = $post['cashback'] * ($user['memberships'][0]['benefit_cashback_multiplier']) / 100;

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
                if ($totalCashMax < $post['cashback']) {
                    $post['cashback'] = $totalCashMax;
                }
            } else {
                $post['cashback'] = $post['cashback'];
            }
        }
    	return [
    		'point' => $post['point'] ?? 0,
    		'cashback' => $post['cashback'] ?? 0
    	];
    }

    public function showListDelivery($showDelivery, $listDelivery)
    {
    	if (empty($listDelivery) || $showDelivery != 1) {
    		return $showDelivery;
    	}

    	$showList = 0;
    	foreach ($listDelivery as $val) {
    		if ($val['disable']) {
    			continue;
    		}

    		$showList = 1;
    		break;
    	}
    	
    	return $showList;
    }

    public function showListDeliveryPickup($showDelivery, $id_outlet)
    {
    	if ($showDelivery != 1) {
    		return $showDelivery;
    	}

    	$listDelivery = $this->listAvailableDelivery(WeHelpYou::listDeliveryRequest())['result']['delivery'] ?? [];
    	$delivery_outlet = DeliveryOutlet::where('id_outlet', $id_outlet)->get();
		$outletSetting = [];
		foreach ($delivery_outlet as $val) {
			$outletSetting[$val['code']] = $val;
		}

    	$showList = 0;
    	foreach ($listDelivery as $val) {
    		if ($val['show_status'] != 1
    			|| $val['available_status'] != 1
    			|| empty($outletSetting[$val['code']])
    			|| (isset($outletSetting[$val['code']]) && ($outletSetting[$val['code']]['available_status'] != 1 || $outletSetting[$val['code']]['show_status'] != 1))
    		) {
    			continue;
    		}

    		$showList = 1;
    		break;
    	}

    	return $showList;
    }

    public function cartTransaction(Request $request){
        $post = $request->json()->all();
        $bearerToken = $request->bearerToken();
        $tokenId = (new Parser())->parse($bearerToken)->getHeader('jti');
        $getOauth = OauthAccessToken::find($tokenId);
        $scopeUser = str_replace(str_split('[]""'),"",$getOauth['scopes']);

        if(empty($post['item']) && empty($post['item_service'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item/Item bundling/Item Service can not be empty']
            ]);
        }
        $post['item'] = $this->mergeProducts($post['item']??[]);
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

        //Check Outlet
        if(!empty($post['outlet_code'])){
            $outlet = Outlet::where('outlet_code', $post['outlet_code'])->with('today')->first();
            $post['id_outlet'] = $outlet['id_outlet']??null;
        }else{
            $id_outlet = $post['id_outlet'];
            $outlet = Outlet::where('id_outlet', $id_outlet)->with('today')->first();
        }

        if (empty($outlet)) {
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet Not Found']
            ]);
        }

        if($scopeUser == 'apps'){
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
        }

        $subTotalItem = 0;
        $continueCheckOut = true;
        foreach ($post['item'] as &$item) {
            // get detail product
            $err = [];
            $product = Product::select([
                'products.id_product','products.product_name','products.product_code',
                DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = '.$post['id_outlet'].' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' )
                            ELSE product_global_price.product_global_price
                        END) as product_price'),
                DB::raw('(select product_detail.product_detail_stock_item from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1) as product_stock_status'),
                'brand_product.id_product_category','brand_product.id_brand', 'products.product_variant_status'
            ])
                ->join('brand_product','brand_product.id_product','=','products.id_product')
                ->leftJoin('product_global_price','product_global_price.id_product','=','products.id_product')
                ->where('brand_outlet.id_outlet','=',$post['id_outlet'])
                ->join('brand_outlet','brand_outlet.id_brand','=','brand_product.id_brand')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility IS NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                    END)')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = '.$post['id_outlet'].' )
                    END)')
                ->where(function ($query) use ($post){
                    $query->orWhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' ) is NOT NULL');
                    $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product) is NOT NULL');
                })
                ->with([
                    'photos' => function($query){
                        $query->select('id_product','product_photo');
                    }
                ])
                ->having('product_price','>',0)
                ->groupBy('products.id_product')
                ->orderBy('products.position')
                ->find($item['id_product']);

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

            $item = $product;
            if(!empty($err)){
                $continueCheckOut = false;
            }
        }
        $result['items'] = $post['item'];

        $result['item_service'] = [];
        $subTotalItemService = 0;
        if(!empty($post['item_service'])){
            $itemServices = $this->cartServiceProduct($post, $outlet);
            $result['item_service'] = $itemServices['item_service']??[];
            $continueCheckOut = $itemServices['continue_checkout'];
            $subTotalItemService = $itemServices['subtotal_service']??0;
        }

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
        $result['continue_checkout'] = $continueCheckOut;
        if($scopeUser == 'apps'){
            $result['complete_profile'] = (empty($user->complete_profile) ?false:true);
        }

        return MyHelper::checkGet($result);
    }

    public function cartServiceProduct($post, $outlet){
        $subTotalService = 0;
        $itemService = [];
        $currentDate = date('Y-m-d H:i');
        $continueCheckOut = true;
        $idOutletSchedule = $outlet['today']['id_outlet_schedule']??null;

        foreach ($post['item_service']??[] as $key=>$item){
            $err = [];
            $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                ->leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                ->where('products.id_product', $item['id_product'])
                ->select('products.*', 'product_global_price as product_price', 'brand_product.id_brand')
                ->with(['product_service_use_detail'])
                ->first();

            if(empty($service)){
                $err[] = 'Service tidak tersedia';
            }

            foreach ($service['product_service_use_detail'] as $stock){
                if($stock['quantity_use'] > $stock['product_detail_stock_service']){
                    $err[] = 'Service tidak tersedia';
                    break 2;
                }
            }

            $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $post['id_outlet'])->first();
            $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

            if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                $err[] = 'Service tidak tersedia';
            }

            if($outlet['outlet_special_status'] == 1){
                $service['product_price'] = ProductSpecialPrice::where('id_product', $item['id_product'])
                        ->where('id_outlet', $outlet['id_outlet'])->first()['product_special_price']??0;
            }

            if(empty($service['product_price'])){
                $err[] = 'Service tidak tersedia';
            }

            if($service['product_detail_visibility'] == 'Hidden' || (empty($service['product_detail_visibility']) && $service['product_visibility'] == 'Hidden')){
                $err[] = 'Service tidak tersedia';
            }

            $bookTime = date('Y-m-d H:i', strtotime(date('Y-m-d', strtotime($item['booking_date'])).' '.date('H:i', strtotime($item['booking_time']))));
            if(strtotime($currentDate) > strtotime($bookTime)){
                $err[] = "Waktu pemesanan Anda tidak valid";
            }

            //check available hs
            $hs = UserHairStylist::where('id_user_hair_stylist', $item['id_user_hair_stylist'])->where('user_hair_stylist_status', 'Active')->first();
            if(empty($hs)){
                $err[] = "Hair stylist tidak tersedia untuk ".MyHelper::dateFormatInd($bookTime);
            }

            //get hs schedule
            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                    ->whereNotNull('approve_at')->where('id_user_hair_stylist', $item['id_user_hair_stylist'])
                    ->whereDate('date', date('Y-m-d', strtotime($item['booking_date'])))
                    ->first()['shift']??'';
            if(empty($shift)){
                $err[] = "Hair stylist tidak tersedia untuk ".MyHelper::dateFormatInd($bookTime);
            }

            $getTimeShift = app($this->product)->getTimeShift(strtolower($shift), $post['id_outlet'], $idOutletSchedule);
            if(empty($getTimeShift['start']) && empty($getTimeShift['end'])){
                $err[] = "Hair stylist tidak tersedia untuk ".MyHelper::dateFormatInd($bookTime);
            }else{
                $shiftTimeStart = date('H:i:s', strtotime($getTimeShift['start']));
                $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
                $time = date('H:i', strtotime($item['booking_time']));
                if((strtotime($time) >= strtotime($shiftTimeStart) && strtotime($time) < strtotime($shiftTimeEnd)) === false){
                    $err[] = "Hair stylist tidak tersedia untuk ".MyHelper::dateFormatInd($bookTime);
                }
            }

            $hsNotAvailable = HairstylistNotAvailable::where('id_outlet', $post['id_outlet'])
                ->where('booking_date', date('Y-m-d', strtotime($item['booking_date'])))
                ->where('booking_time', date('H:i:s', strtotime($item['booking_time'])))
                ->where('id_user_hair_stylist', $item['id_user_hair_stylist'])
                ->first();

            if(!empty($hsNotAvailable)){
                $err[] = "Hair stylist tidak tersedia untuk ".MyHelper::dateFormatInd($bookTime);
            }

            $itemService[$key] = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $service['id_brand'],
                "id_product" => $service['id_product'],
                "product_code" => $service['product_code'],
                "product_name" => $service['product_name'],
                "product_price" => (int)$service['product_price'],
                "id_user_hair_stylist" => $hs['id_user_hair_stylist'],
                "user_hair_stylist_name" => $hs['fullname'],
                "booking_date" => $item['booking_date'],
                "booking_date_display" => MyHelper::dateFormatInd($item['booking_date'], true, false),
                "booking_time" => $item['booking_time'],
                "error_msg" => (empty($err)? null:implode(".", array_unique($err)))
            ];
            $subTotalService = $subTotalService + $service['product_price'];
            if(!empty($err)){
                $continueCheckOut = false;
            }
        }

        $mergeService = $this->mergeService($itemService);

        return [
            'subtotal_service' => $subTotalService,
            'item_service' => $mergeService,
            'continue_checkout' => $continueCheckOut
        ];
    }

    function bookHS($id_transaction){
        $trx = Transaction::where('id_transaction', $id_transaction)->first();

        if($trx['transaction_from'] == 'home-service'){
            $trxHomeService = TransactionHomeService::where('id_transaction', $id_transaction)->first();

            if(!empty($trxHomeService['id_user_hair_stylist'])){
                $save = HairstylistNotAvailable::create([
                    'id_outlet' => $trx['id_outlet'],
                    'id_user_hair_stylist' => $trxHomeService['id_user_hair_stylist'],
                    'id_transaction' => $trx['id_transaction'],
                    'booking_date' => date('Y-m-d', strtotime($trxHomeService['schedule_date'])),
                    'booking_time' => date('H:i:s', strtotime($trxHomeService['schedule_time']))
                ]);
            }
        }elseif($trx['transaction_from'] == 'outlet-service'){
            $data = TransactionProductService::where('transactions.id_transaction', $id_transaction)
                ->join('transactions', 'transactions.id_transaction', 'transaction_product_services.id_transaction')
                ->select('transaction_product_services.*', 'transactions.id_outlet')
                ->get()->toArray();

            $insert = [];
            foreach ($data as $dt){
                $insert[] = [
                    'id_outlet' => $dt['id_outlet'],
                    'id_user_hair_stylist' => $dt['id_user_hair_stylist'],
                    'id_transaction_product_service' => $dt['id_transaction_product_service'],
                    'booking_date' => date('Y-m-d', strtotime($dt['schedule_date'])),
                    'booking_time' => date('H:i:s', strtotime($dt['schedule_time'])),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            if(!empty($insert)){
                $save = HairstylistNotAvailable::insert($insert);
            }
        }

        return $save??true;
    }

    function bookProductStock($id_transaction){
        $data = TransactionProduct::where('transactions.id_transaction', $id_transaction)
            ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->select('transaction_products.*', 'transactions.id_outlet')
            ->where('type', 'Product')
            ->get()->toArray();

        foreach ($data as $dt){
            if(!empty($dt['id_product_variant_group'])){
                $stock = ProductVariantGroupDetail::where(['id_product' => $dt['id_product'], 'id_outlet' => $dt['id_outlet']])->first();

                ProductStockLog::create([
                    'id_product' => $dt['id_product'],
                    'id_transaction' => $dt['id_transaction'],
                    'stock_item' => -$dt['transaction_product_qty'],
                    'stock_item_before' => (empty($stock['product_variant_group_detail_stock_item']) ? 0 :$stock['product_variant_group_detail_stock_item']),
                    'stock_service_before' => $stock['product_variant_group_detail_stock_service'],
                    'stock_item_after' => $stock['product_variant_group_detail_stock_item'] - $dt['transaction_product_qty'],
                    'stock_service_after' => (empty($stock['product_variant_group_detail_stock_service']) ? 0 :$stock['product_variant_group_detail_stock_service'])
                ]);

                $stock->product_variant_group_detail_stock_item = $stock['product_variant_group_detail_stock_item'] - $dt['qty'];
                $stock->save();
            }else{
                $stock = ProductDetail::where(['id_product' => $dt['id_product'], 'id_outlet' => $dt['id_outlet']])->first();
                ProductStockLog::create([
                    'id_product' => $dt['id_product'],
                    'id_transaction' => $dt['id_transaction'],
                    'stock_item' => -$dt['transaction_product_qty'],
                    'stock_item_before' => $stock['product_detail_stock_item'],
                    'stock_service_before' => (empty($stock['product_detail_stock_service']) ? 0 :$stock['product_detail_stock_service']),
                    'stock_item_after' => $stock['product_detail_stock_item'] - $dt['transaction_product_qty'],
                    'stock_service_after' => (empty($stock['product_detail_stock_service']) ? 0 :$stock['product_detail_stock_service'])
                ]);

                $stock->product_detail_stock_item = $stock['product_detail_stock_item'] - $dt['transaction_product_qty'];
                $stock->save();
            }
        }

        return $update??true;
    }

    function bookProductServiceStock($trx,$id_transaction_product_service){
        $getProduct = TransactionProductServiceUse::where('id_transaction_product_service', $id_transaction_product_service)->get()->toArray();
        foreach ($getProduct as $p){
            $productStock = ProductDetail::where(['id_product' => $p['id_product'], 'id_outlet' => $trx['id_outlet']])->first();
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
                'id_transaction' => $trx['id_transaction'],
                'stock_service' => -$p['quantity_use'],
                'stock_item_before' => $currentStock,
                'stock_service_before' => $currentStockService,
                'stock_item_after' => $currentStock,
                'stock_service_after' => $currentStockService - $p['quantity_use']
            ]);
        }

        return $updateDetail??true;
    }
}
