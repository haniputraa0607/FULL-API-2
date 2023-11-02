<?php

namespace Modules\Transaction\Http\Controllers;

use App\Http\Models\DailyTransactions;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\OutletSchedule;
use App\Jobs\DisburseJob;
use App\Jobs\FraudJob;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Auth;
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
use Modules\Product\Entities\ProductIcount;
use Modules\Product\Entities\ProductIcountOutletStockLog;
use Modules\Product\Entities\ProductProductIcount;
use Modules\Product\Entities\ProductStockLog;
use Modules\ProductBundling\Entities\BundlingOutlet;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductService\Entities\ProductHairstylistCategory;
use Modules\ProductService\Entities\ProductServiceUse;
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
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;
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
use Modules\PromoCampaign\Entities\TransactionPromo;
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
use App\Lib\TemporaryDataManager;

use Modules\Transaction\Http\Requests\Transaction\NewTransactionV2 ;
use Modules\Transaction\Http\Requests\Transaction\ConfirmPayment;
use Modules\Transaction\Http\Requests\CheckTransaction;
use Modules\ProductVariant\Entities\ProductVariant;
use App\Http\Models\TransactionMultiplePayment;
use App\Jobs\QueueService;
use Modules\ProductBundling\Entities\Bundling;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Xendit\Entities\TransactionPaymentXendit;

class ApiTransactionV2 extends Controller
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
        $this->trx_home_service  = "Modules\Transaction\Http\Controllers\ApiTransactionHomeService";
        $this->trx_academy = "Modules\Transaction\Http\Controllers\ApiTransactionAcademy";
        $this->trx_shop = "Modules\Transaction\Http\Controllers\ApiTransactionShop";
        $this->promo_trx = "Modules\Transaction\Http\Controllers\ApiPromoTransaction";
        $this->trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
    }
    public function newTransaction(NewTransactionV2 $request) {
        $post = $request->json()->all();
        if(!Auth::user()->custom_name){
            if(!empty($post['customer_name'])){
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet Account is not custom name type']
                ]);
            }
        }
        if(empty($post['transaction_from'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Parameter transaction_from can not be empty']
            ]);
        }

        if($post['transaction_from'] == 'home-service'){
            $homeService = app($this->trx_home_service)->newTransactionHomeService($request);
            return $homeService;
        }elseif($post['transaction_from'] == 'academy'){
            $academy = app($this->trx_academy)->newTransactionAcademy($request);
            return $academy;
        }elseif($post['transaction_from'] == 'shop'){
            $shop = app($this->trx_shop)->newTransactionShop($request);
            return $shop;
        }

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
            $outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->where('outlet_code', $post['outlet_code'])
                ->with('today')->where('outlet_status', 'Active')
                ->where('outlets.outlet_service_status', 1)
                ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')->first();
            $post['id_outlet'] = $outlet['id_outlet']??null;
            if (empty($outlet)) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet Not Found']
                ]);
            }
        }elseif(isset($post['id_outlet'])){
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])->with('today')->where('outlet_status', 'Active')
                ->join('cities', 'cities.id_city', 'outlets.id_city')
                ->join('provinces', 'provinces.id_province', 'cities.id_province')
                ->where('outlets.outlet_service_status', 1)
                ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')->first();
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

        if (isset($post['transaction_date'])) {
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
        if(empty($post['item_service'])){
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
                "name" => $post['customer_name']??$user['name'],
                "email" => $post['customer_name']??$user['email'],
                "domicile" => $post['customer_name']??$user['city_name'],
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
        }

        $error_msg=[];
        //check product service
        if(!empty($post['item_service'])){
            $productService = $this->checkServiceProductV2($post, $outlet);
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
            }else {
                $post[$valueTotal] = app($this->setting_trx)->countTransaction($valueTotal, $post);
            }
        }

        $post['discount'] = ($scopeUser == 'apps'? $post['discount'] + $promo_discount:0);
        $post['point'] = app($this->setting_trx)->countTransaction('point', $post);
        $post['cashback'] = app($this->setting_trx)->countTransaction('cashback', $post);

        //count some trx user
        $countUserTrx = Transaction::where('id_user', $id)->where('transaction_payment_status', 'Completed')->count();

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

        $detailPayment = [
            'subtotal' => $post['subtotal'],
            'shipping' => $post['shipping'],
            'tax'      => $post['tax'],
            'service'  => $post['service'],
            'discount' => $post['discount'],
        ];

        $post['grandTotal'] = (int)$post['subtotal'] + (int)$post['discount'] + (int)$post['service'] + (int)$post['shipping'] + (int)$post['discount_delivery'];

        if ($post['grandTotal'] < 0 || $post['subtotal'] < 0) {
            return [
                'status' => 'fail',
                'messages' => ['Invalid transaction']
            ];
        }

        DB::beginTransaction();
        $transaction = [
            'id_outlet'                   => $post['id_outlet'],
            'id_user'                     => $id,
            'id_promo_campaign_promo_code'=> $post['id_promo_campaign_promo_code']??null,
            'transaction_date'            => $post['transaction_date'],
            'shipment_method'             => $shipment_method ?? null,
            'shipment_courier'            => $shipment_courier ?? null,
            'transaction_notes'           => $post['notes']??null,
            'transaction_subtotal'        => $post['subtotal'],
            'transaction_gross'           => $post['subtotal_final'],
            'transaction_shipment'        => $post['shipping'],
            'transaction_service'         => $post['service'],
            'transaction_discount'        => $post['discount'],
            'transaction_discount_delivery' => $post['discount_delivery'],
            'transaction_discount_item'     => $promo_discount_item??0,
            'transaction_discount_bill'     => $promo_discount_bill??0,
            'transaction_tax'             => $post['tax'],
            'transaction_grandtotal'      => $post['grandTotal'],
            'transaction_point_earned'    => $post['point'],
            'transaction_cashback_earned' => $post['cashback'],
            'trasaction_payment_type'     => $post['payment_type'],
            'transaction_payment_status'  => $post['transaction_payment_status'],
            'membership_level'            => $post['membership_level'],
            'membership_promo_id'         => $post['membership_promo_id'],
            'latitude'                    => $post['latitude']??null,
            'longitude'                   => $post['longitude']??null,
            'void_date'                   => null,
            'transaction_from'            => $post['transaction_from'],
            'scope'                       => $scopeUser??null,
            'customer_name'               => $post['customer_name']??$user['name'],
                'customer_email' => $post['customer']['email']??$user['email'],
                'customer_domicile' => $post['customer']['domicile']??$user['domicile'],
                'customer_birtdate' => $post['customer']['birthdate']??$user['birthdate'],
                'customer_gender' => $post['customer']['gender']??$user['gender']
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

        if (!empty($post['payment_type']) && $post['payment_type'] == 'Cash') {
            $transaction['transaction_payment_status'] = 'Completed';
            $transaction['completed_at'] = date('Y-m-d H:i:s');
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
                'customer_name' => $post['customer_name']??$user['name'],
                'customer_email' => $post['customer']['email']??$user['email'],
                'customer_domicile' => $post['customer']['domicile']??$user['domicile'],
                'customer_birtdate' => $post['customer']['birthdate']??$user['birthdate'],
                'customer_gender' => $post['customer']['gender']??$user['gender']
            ]);
            if (!$createOutletService) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Insert Transaction Outlet Service Failed']
                ]);
            }
        }

        //update receipt
        $lastReceipt = Transaction::where('id_outlet', $insertTransaction['id_outlet'])->latest('transaction_receipt_number')->first()['transaction_receipt_number']??'';
        $lastReceipt = substr($lastReceipt, -5);
        $lastReceipt = (int)$lastReceipt;
        $countReciptNumber = $lastReceipt+1;
        $receipt = 'TRX'.substr($outlet['outlet_code'], -4).'-'.sprintf("%05d", $countReciptNumber);
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
            $insertService = $this->insertServiceProductV2($post['item_service']??[], $insertTransaction, $outlet, $post, $productMidtrans, $userTrxProduct, $post['payment_type']??null);
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
                'transaction_product_price_base' => $valueProduct['transaction_product_price'] - $valueProduct['product_tax'],
                'transaction_product_price_tax'  => $valueProduct['product_tax'],
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

            $dataProductMidtrans = [
                'id'       => $checkProduct['id_product'],
                'price'    => $productPrice - ($trx_product['transaction_product_discount']/$trx_product['transaction_product_qty']),
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

        if ($scopeUser == 'apps') {
            $applyPromo = app($this->promo_trx)->applyPromoNewTrx($insertTransaction);
            if ($applyPromo['status'] == 'fail') {
                DB::rollback();
                return $applyPromo;
            }

            $insertTransaction = $applyPromo['result'] ?? $insertTransaction;
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

        if (!empty($post['payment_type']) && $post['payment_type'] == 'Cash') {
            
            $datacreateTrxPyemntCash = [
                'id_transaction' => $insertTransaction['id_transaction'],
                'payment_code' => MyHelper::createrandom(4, null, strtotime(date('Y-m-d H:i:s'))),
                'cash_nominal' => $insertTransaction['transaction_grandtotal']
            ];
            if(!empty($post['item_service'])){
                $datacreateTrxPyemntCash['cash_received_by'] = $post['item_service'][0]['id_user_hair_stylist'] ?? null;
            }   

            $createTrxPyemntCash = TransactionPaymentCash::create($datacreateTrxPyemntCash);
            
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

            if(!empty($post['item_service'])){
                $trxProductService = TransactionProductService::with(['transaction_product.product'])->where('id_transaction', $insertTransaction['id_transaction'])->get();
                foreach($trxProductService ?? [] as $trxproserv){
                    $send = [
                        'trx' => $insertTransaction,
                        'service' => $trxproserv,
                        'product' => $trxproserv['transaction_product']['product'],
                    ];
				    $refresh = QueueService::dispatch($send)->onConnection('queueservicequeue');
                }
            }

            $trx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->first();
            app($this->online_trx)->bookProductStock($trx['id_transaction']);
            optional($trx)->recalculateTaxandMDR();
            $trx->triggerPaymentCompleted();
            
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
        if ($scopeUser == 'apps') {
            /* Fraud Referral*/
            if ($promo_code_ref) {
                //======= Start Check Fraud Referral User =======//
                $data = [
                    'id_user' => $insertTransaction['id_user'],
                    'referral_code' => $promo_code_ref,
                    'referral_code_use_date' => $insertTransaction['transaction_date'],
                    'id_transaction' => $insertTransaction['id_transaction']
                ];
                if ($config_fraud_use_queue == 1) {
                    FraudJob::dispatch($user, $data, 'referral user')->onConnection('fraudqueue');
                    FraudJob::dispatch($user, $data, 'referral')->onConnection('fraudqueue');
                } else {
                    app($this->setting_fraud)->fraudCheckReferralUser($data);
                    app($this->setting_fraud)->fraudCheckReferral($data);
                }
                //======= End Check Fraud Referral User =======//
            }

            if ($request->id_deals_user) {
                $voucherUsage = TransactionPromo::where('id_deals_user', $request->id_deals_user)->count();
                if (($voucherUsage ?? false) > 1) {
                    DB::rollBack();
                    return [
                        'status' => 'fail',
                        'messages' => ['Voucher sudah pernah digunakan']
                    ];
                }
            }
        }

        DB::commit();

        if(!empty($insertTransaction['id_transaction']) && $insertTransaction['transaction_grandtotal'] == 0){
            $trx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->first();
            $this->bookProductStock($trx['id_transaction']);
            optional($trx)->recalculateTaxandMDR();
            $trx->triggerPaymentCompleted();
        }

        $insertTransaction['cancel_message'] = 'Are you sure you want to cancel this transaction?';
        $insertTransaction['timer_shopeepay'] = (int) MyHelper::setting('shopeepay_validity_period','value', 300);
        $insertTransaction['message_timeout_shopeepay'] = "Sorry, your payment has expired";
         $req = array(
            'id'=> $insertTransaction['id_transaction'],
            "payment_detail"=> "Dana",
            "payment_type"=> "Xendit",
            "id_user"=>$id
        );
        $confirm = $this->confirmTransactionV2($req);
        if($confirm['status']=='fail'){
            DB::rollBack();
            return $confirm;
        }else{
            DB::commit();
        }
         return $confirm;
//        return response()->json([
//            'status'   => 'success',
//            'redirect' => true,
//            'result'   => $insertTransaction
//        ]);

    }
     public function confirmTransactionV2($post)
    {
        $user = User::where('id', $post['id_user'])->first();

        if ($post['payment_type'] && $post['payment_type'] != 'Balance') {
            $available_payment = app($this->trx)->availablePayment(new Request())['result'] ?? [];
            if (!in_array($post['payment_type'], array_column($available_payment, 'payment_gateway'))) {
                return [
                    'status' => 'fail',
                    'messages' => 'Metode pembayaran yang dipilih tidak tersedia untuk saat ini'
                ];
            }
        }

        $productMidtrans   = [];
        $dataDetailProduct = [];

        // refresh tax and mdr
        $trx = Transaction::find($post['id']);
        optional($trx)->recalculateTaxandMDR();
        
        $check = Transaction::with('transaction_shipments', 'productTransaction.product', 'productTransaction.product_variant_group','outlet_name', 'transaction_payment_subscription')->where('id_transaction', $post['id'])->first();

        if (empty($check)) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Not Found'],
            ]);
        }

        if ($check['transaction_payment_status'] != 'Pending') {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Invalid'],
            ]);
        }
        
        $outletCode = Outlet::where('id_outlet', $check['id_outlet'])->first()['outlet_code']??null;

        if(!isset($post['payment_detail'])){
            $post['payment_detail'] = null;
        }

        $checkPayment = TransactionMultiplePayment::where('id_transaction', $check['id_transaction'])->first();
        $countGrandTotal = $check['transaction_grandtotal'];
        $totalPriceProduct = 0;

        if (isset($check['productTransaction'])) {
            foreach ($check['productTransaction'] as $key => $value) {
                // get modifiers name
                $mods           = TransactionProductModifier::select('qty', 'text')->where('id_transaction_product', $value['id_transaction_product'])->get()->toArray();
                $more_name_text = '';
                foreach ($mods as $mod) {
                    if ($mod['qty'] > 1) {
                        $more_name_text .= ',' . $mod['qty'] . 'x ' . $mod['text'];
                    } else {
                        $more_name_text .= ',' . $mod['text'];
                    }
                }
                $dataProductMidtrans = [
                    'id'       => $value['product_variant_group']['product_variant_group_code'] ?? $value['product']['product_code'],
                    // 'price'    => abs($value['transaction_product_price']+$value['transaction_variant_subtotal']+$value['transaction_modifier_subtotal']-($value['transaction_product_discount']/$value['transaction_product_qty'])),
                    'price'    => abs($value['transaction_product_price']+$value['transaction_variant_subtotal']+$value['transaction_modifier_subtotal']),
                    // 'name'     => $value['product']['product_name'].($more_name_text?'('.trim($more_name_text,',').')':''), // name + modifier too long
                    'name'     => $value['product']['product_name'],
                    'quantity' => $value['transaction_product_qty'],
                ];

                $totalPriceProduct+= ($dataProductMidtrans['quantity'] * $dataProductMidtrans['price']);

                array_push($productMidtrans, $dataProductMidtrans);
                array_push($dataDetailProduct, $dataProductMidtrans);
            }
        }

        $checkItemBundling = TransactionBundlingProduct::where('id_transaction', $check['id_transaction'])
            ->join('bundling', 'bundling.id_bundling', 'transaction_bundling_products.id_bundling')
            ->select('transaction_bundling_products.*', 'bundling.bundling_name', 'bundling.bundling_code')
            ->get()->toArray();

        if (!empty($checkItemBundling)) {
            foreach ($checkItemBundling as $key => $value) {
                $dataProductMidtrans = [
                    'id'       => $value['bundling_code'],
                    'price'    => abs((int)$value['transaction_bundling_product_subtotal']/$value['transaction_bundling_product_qty']),
                    'name'     => $value['bundling_name'],
                    'quantity' => $value['transaction_bundling_product_qty'],
                ];

                $totalPriceProduct+= ($dataProductMidtrans['quantity'] * $dataProductMidtrans['price']);

                array_push($productMidtrans, $dataProductMidtrans);
                array_push($dataDetailProduct, $dataProductMidtrans);
            }
        }

        $checkProductPlastic = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                                ->where('id_transaction', $check['id_transaction'])->where('type', 'Plastic')->get()->toArray();
        if (!empty($checkProductPlastic)) {
            foreach ($checkProductPlastic as $key => $value) {
                $dataProductMidtrans = [
                    'id'       => $value['product_code'],
                    'price'    => abs($value['transaction_product_price']),
                    'name'     => $value['product_name'],
                    'quantity' => $value['transaction_product_qty'],
                ];

                $totalPriceProduct+= ($dataProductMidtrans['quantity'] * $dataProductMidtrans['price']);

                array_push($productMidtrans, $dataProductMidtrans);
                array_push($dataDetailProduct, $dataProductMidtrans);
            }
        }

        $checkProductService = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
            ->where('id_transaction', $check['id_transaction'])->where('type', 'Service')->get()->toArray();
        if (!empty($checkProductService)) {
            foreach ($checkProductService as $key => $value) {
                $dataProductMidtrans = [
                    'id'       => $value['product_code'],
                    'price'    => abs($value['transaction_product_price']),
                    'name'     => $value['product_name'],
                    'quantity' => $value['transaction_product_qty'],
                ];

                $totalPriceProduct+= ($dataProductMidtrans['quantity'] * $dataProductMidtrans['price']);

                array_push($productMidtrans, $dataProductMidtrans);
                array_push($dataDetailProduct, $dataProductMidtrans);
            }
        }

        $checkProductAcademy = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
            ->where('id_transaction', $check['id_transaction'])->where('type', 'Academy')->get()->toArray();
        if (!empty($checkProductAcademy)) {
            foreach ($checkProductAcademy as $key => $value) {
                $dataProductMidtrans = [
                    'id'       => $value['product_code'],
                    'price'    => abs($value['transaction_product_price']),
                    'name'     => $value['product_name'],
                    'quantity' => $value['transaction_product_qty'],
                ];

                $totalPriceProduct+= ($dataProductMidtrans['quantity'] * $dataProductMidtrans['price']);

                array_push($productMidtrans, $dataProductMidtrans);
                array_push($dataDetailProduct, $dataProductMidtrans);
            }
        }

        if ($check['transaction_shipment'] > 0) {
            $dataShip = [
                'id'       => null,
                'price'    => abs($check['transaction_shipment']),
                'name'     => 'Shipping',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataShip);
        }

        if ($check['transaction_shipment_go_send'] > 0) {
            $dataShip = [
                'id'       => null,
                'price'    => abs($check['transaction_shipment_go_send']),
                'name'     => 'Shipping',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataShip);
        }

        if ($check['transaction_service'] > 0) {
            $dataService = [
                'id'       => null,
                'price'    => abs($check['transaction_service']),
                'name'     => 'Service',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataService);
        }

        // if ($check['transaction_tax'] > 0) {
        //     $dataTax = [
        //         'id'       => null,
        //         'price'    => abs($check['transaction_tax']),
        //         'name'     => 'Tax',
        //         'quantity' => 1,
        //     ];
        //     array_push($dataDetailProduct, $dataTax);
        // }

        if ($check['transaction_payment_subscription']) {
            $countGrandTotal -= $check['transaction_payment_subscription']['subscription_nominal'];
            $dataDis = [
                'id'       => null,
                'price'    => -abs($check['transaction_payment_subscription']['subscription_nominal']),
                'name'     => 'Subscription',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataDis);
        }

        $detailPayment = [
            'subtotal' => $check['transaction_subtotal'],
            'shipping' => $check['transaction_shipment'],
            'tax'      => $check['transaction_tax'],
            'service'  => $check['transaction_service'],
            'discount' => -$check['transaction_discount'],
        ];

        $payment_balance = 0;
        if (!empty($checkPayment)) {
            if ($checkPayment['type'] == 'Balance') {
                $checkPaymentBalance = TransactionPaymentBalance::where('id_transaction', $check['id_transaction'])->first();
                if (empty($checkPaymentBalance)) {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Transaction is invalid'],
                    ]);
                }

                $countGrandTotal = $countGrandTotal - $checkPaymentBalance['balance_nominal'];
                $payment_balance = $checkPaymentBalance['balance_nominal'];
                $dataBalance     = [
                    'id'       => null,
                    'price'    => -abs($checkPaymentBalance['balance_nominal']),
                    'name'     => 'Balance',
                    'quantity' => 1,
                ];

                array_push($dataDetailProduct, $dataBalance);

                $detailPayment['balance'] = -$checkPaymentBalance['balance_nominal'];
            }
        }

        // if ($check['transaction_discount'] != 0 && (($countGrandTotal + $payment_balance) < $totalPriceProduct)) {
        if ($check['transaction_discount'] != 0) {
            $dataDis = [
                'id'       => null,
                'price'    => -abs($check['transaction_discount']),
                'name'     => 'Discount',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataDis);
        }

        if ($check['transaction_discount_delivery'] != 0) {
            $dataDis = [
                'id'       => null,
                'price'    => -abs($check['transaction_discount_delivery']),
                'name'     => 'Discount',
                'quantity' => 1,
            ];
            array_push($dataDetailProduct, $dataDis);
        }

        if ($check['trasaction_type'] == 'Delivery') {
            $dataUser = [
                'first_name'      => $user['name'],
                'email'           => $user['email'],
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name' => $check['transaction_shipments']['destination_name'],
                    'phone'      => $check['transaction_shipments']['destination_phone'],
                    'address'    => $check['transaction_shipments']['destination_address'],
                ],
            ];

            $dataShipping = [
                'first_name'  => $check['transaction_shipments']['name'],
                'phone'       => $check['transaction_shipments']['phone'],
                'address'     => $check['transaction_shipments']['address'],
                'postal_code' => $check['transaction_shipments']['postal_code'],
            ];
        } else {
            $checkOutletService = TransactionOutletService::where('id_transaction', $post['id'])->first();
            $dataUser = [
                'first_name'      => (!empty($checkOutletService['customer_name']) ? $checkOutletService['customer_name'] : $user['name']),
                'email'           => (!empty($checkOutletService['customer_email']) ? $checkOutletService['customer_email'] : $user['email']),
                'phone'           => $user['phone'],
                'billing_address' => [
                    'first_name' => (!empty($checkOutletService['customer_name']) ? $checkOutletService['customer_name'] : $user['name']),
                    'phone'      => $user['phone'],
                ],
            ];
        }
        
        if ($post['payment_type'] == 'Midtrans') {
            if (\Cache::has('midtrans_confirm_'.$check['id_transaction'])) {
                return response()->json(\Cache::get('midtrans_confirm_'.$check['id_transaction']));
            }
            $transaction_details = array(
                'order_id'     => $check['transaction_receipt_number'],
                'gross_amount' => $countGrandTotal,
            );

            if ($check['trasaction_type'] == 'Delivery') {
                $dataMidtrans = array(
                    'transaction_details' => $transaction_details,
                    'customer_details'    => $dataUser,
                    'shipping_address'    => $dataShipping,
                );
                $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $countGrandTotal, $dataUser, $dataShipping, $dataDetailProduct, 'trx', $check['id_transaction'], $post['payment_detail'], $scopeUser, $outletCode, $check['transaction_from']);
            } else {
                $dataMidtrans = array(
                    'transaction_details' => $transaction_details,
                    'customer_details'    => $dataUser,
                );
                $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $countGrandTotal, $dataUser, $ship=null, $dataDetailProduct, 'trx', $check['id_transaction'], $post['payment_detail'], $scopeUser, $outletCode, $check['transaction_from']);
            }

            if (empty($connectMidtrans['token'])) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => [
                        'Midtrans token is empty. Please try again.',
                    ],
                    'error'    => [$connectMidtrans],
                    'data'     => [
                        'trx'         => $transaction_details,
                        'grand_total' => $countGrandTotal,
                        'product'     => $dataDetailProduct,
                        'user'        => $dataUser,
                    ],
                ]);
            }

            $dataNotifMidtrans = [
                'id_transaction' => $check['id_transaction'],
                'gross_amount'   => $countGrandTotal,
                'order_id'       => $check['transaction_receipt_number'],
                'redirect_url' => $connectMidtrans['redirect_url']??NULL,
                'token' => $connectMidtrans['token']??NULL
            ];

            switch (strtolower($post['payment_detail']??'')) {
                case 'bank transfer':
                    $dataNotifMidtrans['payment_type'] = 'Bank Transfer';
                    break;

                case 'credit card':
                    $dataNotifMidtrans['payment_type'] = 'Credit Card';
                    break;

                case 'gopay':
                    $dataNotifMidtrans['payment_type'] = 'Gopay';
                    break;
                
                default:
                    $dataNotifMidtrans['payment_type'] = null;
                    break;
            }

            $insertNotifMidtrans = TransactionPaymentMidtran::create($dataNotifMidtrans);
            if (!$insertNotifMidtrans) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => [
                        'Payment Midtrans Failed.',
                    ],
                    'data'     => [$connectMidtrans],
                ]);
            }

            $dataMultiple = [
                'id_transaction' => $check['id_transaction'],
                'type'           => 'Midtrans',
                'id_payment'     => $insertNotifMidtrans['id_transaction_payment'],
                'payment_detail' => $dataNotifMidtrans['payment_type'],
            ];

            $saveMultiple = TransactionMultiplePayment::create($dataMultiple);
            if (!$saveMultiple) {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['fail to confirm transaction'],
                ]);
            }

            $dataMidtrans['items']            = $productMidtrans;
            $dataMidtrans['payment']          = $detailPayment;
            $dataMidtrans['midtrans_product'] = $dataDetailProduct;

            Transaction::where('id_transaction', $post['id'])->update(['trasaction_payment_type' => $post['payment_type']]);
            optional($trx)->recalculateTaxandMDR();
            DB::commit();

            $dataEncode = [
                'transaction_receipt_number' => $check['transaction_receipt_number'],
                'type'                       => 'trx',
                'trx_success'                => 1,
            ];
            $encode = json_encode($dataEncode);
            $base   = base64_encode($encode);
            $dataMidtrans['transaction_details']['id_transaction'] = $check['id_transaction'];
            $response = [
                'status'           => 'success',
                'result' => [
                    'snap_token'       => $connectMidtrans['token'],
                    'redirect_url'     => $connectMidtrans['redirect_url'],
                    'transaction_data' => $dataMidtrans,
                    'url'              => env('VIEW_URL') . '/transaction/web/view/detail?data=' . $base,
                ]

            ];
            \Cache::put('midtrans_confirm_'.$check['id_transaction'], $response, now()->addMinutes(10));

            //book item and hs
            if($check['transaction_from'] == 'outlet-service' || $check['transaction_from'] == 'shop'){
                 $this->bookProductStock($check['id_transaction']);
            }
            return response()->json($response);
        } elseif ($post['payment_type'] == 'Ovo') {

            //validasi phone
            $phone = preg_replace("/[^0-9]/", "", $post['phone']);

            if (substr($phone, 0, 2) == '62') {
                $phone = substr($phone, 2);
            } elseif (substr($phone, 0, 3) == '+62') {
                $phone = substr($phone, 3);
            }

            if (substr($phone, 0, 1) != '0') {
                $phone = '0' . $phone;
            }

            $pay = $this->paymentOvo($check, $countGrandTotal, $phone, env('OVO_ENV') ?: 'staging');

            return $pay;
        }
        elseif ($post['payment_type'] == 'Ipay88') {

            // save multiple payment
            $trx_ipay88 = \Modules\IPay88\Lib\IPay88::create()->insertNewTransaction($check, 'trx', $countGrandTotal, $post);
            if (!$trx_ipay88) {
                DB::rollBack();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed create transaction payment'],
                ]);
            }
            $dataMultiple = [
                'id_transaction' => $check['id_transaction'],
                'type'           => 'IPay88',
                'id_payment'     => $trx_ipay88->id_transaction_payment_ipay88,
                'payment_detail' => $post['payment_id'] ?? null,
            ];
            $saveMultiple = TransactionMultiplePayment::updateOrCreate([
                'id_transaction' => $check['id_transaction'],
                'type'           => 'IPay88',
            ], $dataMultiple);
            if (!$saveMultiple) {
                DB::rollBack();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed create multiple transaction'],
                ]);
            }
            DB::commit();
            return [
                'status'    => 'success',
                'result'    => [
                    'url'  => config('url.api_url').'api/ipay88/pay?'.http_build_query([
                        'type' => 'trx',
                        'id_reference' => $check['id_transaction'],
                        'payment_id'   => $post['payment_id'] ?: '',
                    ]),
                ],
            ];
        } elseif ($post['payment_type'] == 'Shopeepay') {
            $paymentShopeepay = TransactionPaymentShopeePay::where('id_transaction', $check['id_transaction'])->first();
            $trx_shopeepay    = null;
            if (!$paymentShopeepay) {
                $paymentShopeepay                 = new TransactionPaymentShopeePay;
                $paymentShopeepay->id_transaction = $check['id_transaction'];
                $paymentShopeepay->amount         = $countGrandTotal * 100;
                $paymentShopeepay->save();
                $trx_shopeepay = app($this->shopeepay)->order($paymentShopeepay, 'trx', $errors);
            } elseif (!($paymentShopeepay->redirect_url_app && $paymentShopeepay->redirect_url_http)) {
                $trx_shopeepay = app($this->shopeepay)->order($paymentShopeepay, 'trx', $errors);
            }

            if (!$trx_shopeepay || !(($trx_shopeepay['status_code'] ?? 0) == 200 && ($trx_shopeepay['response']['debug_msg'] ?? '') == 'success' && ($trx_shopeepay['response']['errcode'] ?? 0) == 0)) {
                if ($paymentShopeepay->redirect_url_app && $paymentShopeepay->redirect_url_http) {
                    // already confirmed
                    return [
                        'status' => 'success',
                        'result' => [
                            'redirect'                  => true,
                            'timer_shopeepay'           => (int) MyHelper::setting('shopeepay_validity_period', 'value', 300),
                            'message_timeout_shopeepay' => 'Sorry, your payment has expired',
                            'redirect_url_app'          => $paymentShopeepay->redirect_url_app,
                            'redirect_url_http'         => $paymentShopeepay->redirect_url_http,
                        ],
                    ];
                }
                $dataMultiple = [
                    'id_transaction' => $check['id_transaction'],
                    'type'           => 'Shopeepay',
                    'id_payment'     => $paymentShopeepay->id_transaction_payment_shopee_pay,
                    'payment_detail' => 'Shopeepay',
                ];
                // save multiple payment
                $saveMultiple = TransactionMultiplePayment::updateOrCreate([
                    'id_transaction' => $check['id_transaction'],
                    'type'           => 'Shopeepay',
                ], $dataMultiple);
                if (!$saveMultiple) {
                    DB::rollBack();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Failed create multiple transaction'],
                    ]);
                }
                $errcode = $trx_shopeepay['response']['errcode']??null;
                $paymentShopeepay->errcode = $errcode;
                $paymentShopeepay->err_reason = app($this->shopeepay)->errcode[$errcode]??null;
                $paymentShopeepay->save();
                $trx = $check;
                $update = $trx->update(['transaction_payment_status' => 'Cancelled', 'void_date' => date('Y-m-d H:i:s')]);
                if (!$update) {
                    DB::rollBack();
                    return [
                        'status'   => 'fail',
                        'messages' => ['Failed update transaction status']
                    ];
                }
                $trx->load('outlet_name');
                // $send = app($this->notif)->notificationDenied($mid, $trx);

                //return balance
                $payBalance = TransactionMultiplePayment::where('id_transaction', $trx->id_transaction)->where('type', 'Balance')->first();
                if (!empty($payBalance)) {
                    $checkBalance = TransactionPaymentBalance::where('id_transaction_payment_balance', $payBalance['id_payment'])->first();
                    if (!empty($checkBalance)) {
                        $insertDataLogCash = app("Modules\Balance\Http\Controllers\BalanceController")->addLogBalance($trx['id_user'], $checkBalance['balance_nominal'], $trx['id_transaction'], 'Transaction Failed', $trx['transaction_grandtotal']);
                        if (!$insertDataLogCash) {
                            DB::rollBack();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Insert Cashback Failed']
                            ]);
                        }
                        $usere= User::where('id',$trx['id_user'])->first();
                        $send = app($this->autocrm)->SendAutoCRM('Transaction Failed Point Refund', $usere->phone,
                            [
                                "outlet_name"       => $trx['outlet_name']['outlet_name']??'',
                                "transaction_date"  => $trx['transaction_date'],
                                'id_transaction'    => $trx['id_transaction'],
                                'receipt_number'    => $trx['transaction_receipt_number'],
                                'received_point'    => (string) $checkBalance['balance_nominal']
                            ]
                        );
                        if($send != true){
                            DB::rollBack();
                            return response()->json([
                                    'status' => 'fail',
                                    'messages' => ['Failed Send notification to customer']
                                ]);
                        }
                    }
                }

                // delete promo campaign report
                if ($trx->id_promo_campaign_promo_code) 
                {
                    $update_promo_report = app($this->promo_campaign)->deleteReport($trx->id_transaction, $trx->id_promo_campaign_promo_code);
                }

                // return voucher
                $update_voucher = app($this->voucher)->returnVoucher($trx->id_transaction);

                if(!$update){
                    DB::rollBack();
                    return [
                        'status'=>'fail',
                        'messages' => ['Failed update payment status']
                    ];
                }
                DB::commit();
                return [
                    'status' => 'fail',
                    'messages' => [$paymentShopeepay->err_reason]
                ];
            }
            $paymentShopeepay->redirect_url_app  = $trx_shopeepay['response']['redirect_url_app'];
            $paymentShopeepay->redirect_url_http = $trx_shopeepay['response']['redirect_url_http'];
            $paymentShopeepay->save();
            $dataMultiple = [
                'id_transaction' => $check['id_transaction'],
                'type'           => 'Shopeepay',
                'id_payment'     => $paymentShopeepay->id_transaction_payment_shopee_pay,
                'payment_detail' => 'Shopeepay',
            ];
            // save multiple payment
            $saveMultiple = TransactionMultiplePayment::updateOrCreate([
                'id_transaction' => $check['id_transaction'],
                'type'           => 'Shopeepay',
            ], $dataMultiple);
            if (!$saveMultiple) {
                DB::rollBack();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Failed create multiple transaction'],
                ]);
            }
            DB::commit();
            return [
                'status' => 'success',
                'result' => [
                    'redirect'                  => true,
                    'timer_shopeepay'           => (int) MyHelper::setting('shopeepay_validity_period', 'value', 300),
                    'message_timeout_shopeepay' => 'Sorry, your payment has expired',
                    'redirect_url_app'          => $paymentShopeepay->redirect_url_app ?: 'shopeeid://main',
                    'redirect_url_http'         => $paymentShopeepay->redirect_url_http ?: 'https://wsa.wallet.airpay.co.id/universal-link/wallet/pay',
                ],
            ];
        } elseif ($post['payment_type'] == 'Xendit') {
            $post['phone'] = $post['phone'] ?? $user['phone'];
            $payment_id = $post['payment_id'] ??$post['payment_detail'];
            $paymentXendit = TransactionPaymentXendit::where('id_transaction', $check['id_transaction'])->first();
            $transactionData = [
                'transaction_details' => [
                    'id_transaction' => $check['id_transaction'],
                    'order_id' => $check['transaction_receipt_number'],
                ],
            ];
            if(!$paymentXendit) {
                $paymentXendit = new TransactionPaymentXendit([
                    'id_transaction' => $check['id_transaction'],
                    'xendit_id' => null,
                    'external_id' => $check['transaction_receipt_number'],
                    'business_id' => null,
                    'phone' => $post['phone'],
                    'type' => $payment_id,
                    'amount' => $countGrandTotal,
                    'expiration_date' => null,
                    'failure_code' => null,
                    'status' => null,
                    'callback_authentication_token' => null,
                    'checkout_url' => null,
                ]);
            }

            $check->load('productTransaction.product');

            $dataDetailProduct = [];
            $checkPayment = TransactionMultiplePayment::where('id_transaction', $check['id_transaction'])->first();
            foreach ($check['productTransaction'] as $key => $value) {
                $dataProductMidtrans = [
                    'id'       => (string) $value['id_product'],
                    'price'    => abs($value['transaction_product_price']+$value['transaction_variant_subtotal']+$value['transaction_modifier_subtotal']-($value['transaction_product_discount']/$value['transaction_product_qty'])),
                    'name'     => $value['product']['product_name'],
                    'quantity' => $value['transaction_product_qty'],
                ];

                $dataDetailProduct[] = $dataProductMidtrans;
            }

            if ($check['transaction_shipment'] > 0) {
                $dataShip = [
                    'id'       => 'shipment',
                    'price'    => abs($check['transaction_shipment']),
                    'name'     => 'Shipping',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataShip);
            }

            if ($check['transaction_shipment_go_send'] > 0) {
                $dataShip = [
                    'id'       => 'shipment_go_send',
                    'price'    => abs($check['transaction_shipment_go_send']),
                    'name'     => 'Shipping',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataShip);
            }

            if ($check['transaction_shipment_grab'] > 0) {
                $dataShip = [
                    'id'       => 'shipment_grab',
                    'price'    => abs($check['transaction_shipment_grab']),
                    'name'     => 'Shipping',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataShip);
            }

            if ($check['transaction_service'] > 0) {
                $dataService = [
                    'id'       => 'transaction_service',
                    'price'    => abs($check['transaction_service']),
                    'name'     => 'Service',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataService);
            }

            if ($check['transaction_tax'] > 0) {
                $dataTax = [
                    'id'       => 'transaction_tax',
                    'price'    => abs($check['transaction_tax']),
                    'name'     => 'Tax',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataTax);
            }

            if ($check['transaction_discount'] > 0) {
                $dataDis = [
                    'id'       => 'transaction_discount',
                    'price'    => -abs($check['transaction_discount']),
                    'name'     => 'Discount',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataDis);
            }

            if ($check['transaction_payment_subscription']) {
                $dataDis = [
                    'id'       => 'transaction_payment_subscription',
                    'price'    => -abs($check['transaction_payment_subscription']['subscription_nominal']),
                    'name'     => 'Subscription',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataDis);
            }

            if ($check['transaction_discount_delivery'] != 0) {
                $dataDis = [
                    'id'       => 'transaction_discount_delivery',
                    'price'    => -abs($check['transaction_discount_delivery']),
                    'name'     => 'Discount',
                    'quantity' => 1,
                ];
                array_push($dataDetailProduct, $dataDis);
            }

            if (!empty($checkPayment)) {
                if ($checkPayment['type'] == 'Balance') {
                    if (empty($checkPaymentBalance)) {
                        DB::rollback();
                        return response()->json([
                            'status'   => 'fail',
                            'messages' => ['Transaction is invalid'],
                        ]);
                    }

                    $dataBalance     = [
                        'id'       => 'balance',
                        'price'    => -abs($checkPaymentBalance['balance_nominal']),
                        'name'     => 'Balance',
                        'quantity' => 1,
                    ];

                    array_push($dataDetailProduct, $dataBalance);

                    $detailPayment['balance'] = -$checkPaymentBalance['balance_nominal'];
                }
            }
            $paymentXendit->items = $dataDetailProduct;

            if ($paymentXendit->pay($errors)) {
                $dataMultiple = [
                    'id_transaction' => $paymentXendit->id_transaction,
                    'type'           => 'Xendit',
                    'id_payment'     => $paymentXendit->id_transaction_payment_xendit,
                ];
                // save multiple payment
                $saveMultiple = TransactionMultiplePayment::updateOrCreate([
                    'id_transaction' => $paymentXendit->id_transaction,
                    'type'           => 'Xendit',
                    'payment_detail' => $post['payment_detail']
                ], $dataMultiple);

                optional($trx)->recalculateTaxandMDR();

                $result = [
                    'redirect' => true,
                    'type' => $paymentXendit->type,
                ];
                if ($paymentXendit->type == 'OVO') {
                    $result['timer']  = (int) MyHelper::setting('setting_timer_ovo', 'value', 60);
                    $result['message_timeout'] = 'Sorry, your payment has expired';
                } else {
                    if (!$paymentXendit->checkout_url) {
                        DB::commit();
                        return [
                            'status' => 'fail',
                            'messages' => ['Empty checkout_url']
                        ];
                    }
                    $result['redirect_url'] = $paymentXendit->checkout_url;
                    $result['transaction_data'] = $transactionData;
                }

                DB::commit();
                if($check['transaction_from'] == 'outlet-service' || $check['transaction_from'] == 'shop'){
                   $this->bookProductStock($check['id_transaction']);
                }
                Transaction::where('id_transaction', $post['id'])->update(['trasaction_payment_type' => $post['payment_type']]);
                return [
                    'status' => 'success',
                    'result' => $result
                ];
            }

            $dataMultiple = [
                'id_transaction' => $paymentXendit->id_transaction,
                'type'           => 'Xendit',
                'id_payment'     => $paymentXendit->id_transaction_payment_xendit,
            ];
            // save multiple payment
            $saveMultiple = TransactionMultiplePayment::updateOrCreate([
                'id_transaction' => $paymentXendit->id_transaction,
                'type'           => 'Xendit',
            ], $dataMultiple);

            DB::commit();

            if($check['transaction_from'] == 'outlet-service' || $check['transaction_from'] == 'shop'){
                $this->bookProductStock($check['id_transaction']);
            }
            return [
                'status' => 'fail',
                'messages' => $errors ?: ['Something went wrong']
            ];
        } else {
            if (isset($post['id_manual_payment_method'])) {
                $checkPaymentMethod = ManualPaymentMethod::where('id_manual_payment_method', $post['id_manual_payment_method'])->first();
                if (empty($checkPaymentMethod)) {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Payment Method Not Found'],
                    ]);
                }
            }

            if (isset($post['payment_receipt_image'])) {
                if (!file_exists($this->saveImage)) {
                    mkdir($this->saveImage, 0777, true);
                }

                $save = MyHelper::uploadPhotoStrict($post['payment_receipt_image'], $this->saveImage, 300, 300);

                if (isset($save['status']) && $save['status'] == "success") {
                    $post['payment_receipt_image'] = $save['path'];
                }
                else {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['fail upload image'],
                    ]);
                }
            } else {
                $post['payment_receipt_image'] = null;
            }

            $dataManual = [
                'id_transaction'         => $check['id_transaction'],
                'payment_date'           => $post['payment_date'],
                'id_bank_method'         => $post['id_bank_method'],
                'id_bank'                => $post['id_bank'],
                'id_manual_payment'      => $post['id_manual_payment'],
                'payment_time'           => $post['payment_time'],
                'payment_bank'           => $post['payment_bank'],
                'payment_method'         => $post['payment_method'],
                'payment_account_number' => $post['payment_account_number'],
                'payment_account_name'   => $post['payment_account_name'],
                'payment_nominal'        => $check['transaction_grandtotal'],
                'payment_receipt_image'  => $post['payment_receipt_image'],
                'payment_note'           => $post['payment_note'],
            ];

            $insertPayment = MyHelper::manualPayment($dataManual, 'transaction');
            if (isset($insertPayment) && $insertPayment == 'success') {
                $update = Transaction::where('transaction_receipt_number', $post['id'])->update(['transaction_payment_status' => 'Paid', 'trasaction_payment_type' => $post['payment_type']]);

                if (!$update) {
                    DB::rollback();
                    return response()->json([
                        'status'   => 'fail',
                        'messages' => ['Transaction Failed'],
                    ]);
                }
            } elseif (isset($insertPayment) && $insertPayment == 'fail') {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Transaction Failed'],
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    'status'   => 'fail',
                    'messages' => ['Transaction Failed'],
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => 'success',
                'result' => $check,
            ]);

        }
    }
    public function checkServiceProductV2($post, $outlet){
        $error_msg = [];
        $subTotalService = 0;
        $itemService = [];
        $errorOutlet = [];
        $errorServiceName = [];
        $errorHsNotAvailable = [];
        $errorBookTime = [];
        $timeZone = (empty($outlet['province_time_zone_utc']) ? 7:$outlet['province_time_zone_utc']);
        $diffTimeZone = $timeZone - 7;
        $date = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d', strtotime("+".$diffTimeZone." hour", strtotime($date)));

        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];

        $tempStock = [];
        foreach ($post['item_service']??[] as $key=>$item){
            //check outlet
            $holiday = Holiday::join('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')->join('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                ->where('id_outlet', $outlet['id_outlet'])->whereDay('date_holidays.date', date('d', strtotime($item['booking_date'])))->whereMonth('date_holidays.date', date('m', strtotime($item['booking_date'])))->get();
            if(count($holiday) > 0){
                foreach($holiday as $i => $holi){
                    if($holi['yearly'] == '0'){
                        if($holi['date'] == date('Y-m-d')){
                            $errorOutlet[] = 'Outlet tutup pada '.MyHelper::dateFormatInd($item['booking_date']);
                            unset($post['item_service'][$key]);
                            continue;
                        }
                    }else{
                        $errorOutlet[] = 'Outlet tutup pada '.MyHelper::dateFormatInd($item['booking_date']);
                        unset($post['item_service'][$key]);
                        continue;
                    }
                }
            }

            $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                        ->leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                        ->where('products.id_product', $item['id_product'])
                        ->select('products.*', 'product_global_price as product_price', 'brand_product.id_brand')
                        ->where('product_type', 'service')
                        ->first();

            if(empty($service)){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }

            $getProductDetail = ProductDetail::where('id_product', $service['id_product'])->where('id_outlet', $post['id_outlet'])->first();
            $service['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

            if($service['visibility_outlet'] == 'Hidden' || (empty($service['visibility_outlet']) && $service['product_visibility'] == 'Hidden')){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }
            $allUse = ($tempStock[$service['id_product']]??0) + 1;
            $tempStock[$service['id_product']] = $allUse;
            if(!is_null($getProductDetail['product_detail_stock_item']) && $allUse > $getProductDetail['product_detail_stock_item']){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }
            if($outlet['outlet_different_price'] == 1){
                $service['product_price'] = ProductSpecialPrice::where('id_product', $item['id_product'])
                    ->where('id_outlet', $outlet['id_outlet'])->first()['product_special_price']??0;
            }

            if(empty($service['product_price'])){
                $errorServiceName[] = $item['product_name'];
                unset($post['item_service'][$key]);
                continue;
            }

            if ($outlet['is_tax']) {
                $service['product_tax'] = round($outlet['is_tax'] * $service['product_price'] / (100 + $outlet['is_tax']));
                // $service['product_price'] = $service['product_price'] - $service['product_tax'];
            }

            $bookTime = date('Y-m-d', strtotime(date('Y-m-d', strtotime($item['booking_date']))));
            
            // check available hs
            // $hs = UserHairStylist::where('id_user_hair_stylist', $item['id_user_hair_stylist'])->where('user_hair_stylist_status', 'Active')->first();
            // if(empty($hs)){
            //     $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
            //     unset($post['item_service'][$key]);
            //     continue;
            // }

            // $hsCat = ProductHairstylistCategory::where('id_product', $service['id_product'])->pluck('id_hairstylist_category')->toArray();
            // if(!empty($hsCat) && !in_array($hs['id_hairstylist_category'], $hsCat)){
            //     $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (kategori tidak sesuai)";
            //     unset($post['item_service'][$key]);
            //     continue;
            // }
            if(strtotime($currentDate) > strtotime($bookTime)){
                $errorBookTime[] = $item['product_name']." (".MyHelper::dateFormatInd($bookTime,1,0).')';
                unset($post['item_service'][$key]);
                continue;
            }

            // get hs schedule
            // $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
            //         ->whereNotNull('approve_at')->where('id_user_hair_stylist', $item['id_user_hair_stylist'])
            //         ->whereDate('date', date('Y-m-d', strtotime($item['booking_date'])))
            //         ->first()['shift']??'';
            // if(empty($shift)){
            //     $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
            //     unset($post['item_service'][$key]);
            //     continue;
            // }

            $item['time_zone'] = $outlet['province_time_zone_utc']??7;
            // $checkShift = $this->getCheckAvailableShift($item);
            // if($checkShift === false){
            //     $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
            //     unset($post['item_service'][$key]);
            //     continue;
            // }

            $processingTime = $service['processing_time_service'];
            // $bookTimeStart = date("Y-m-d H:i:s", strtotime($item['booking_date'].' '.$item['booking_time']));
            // $bookTimeEnd = date('Y-m-d H:i:s', strtotime("+".$processingTime." minutes", strtotime($bookTimeStart)));
            // $hsNotAvailable = HairstylistNotAvailable::where('id_outlet', $post['id_outlet'])
            //     ->whereRaw('((booking_start >= "'.$bookTimeStart.'" AND booking_start < "'.$bookTimeEnd.'") 
            //                 OR (booking_end > "'.$bookTimeStart.'" AND booking_end < "'.$bookTimeEnd.'"))')
            //     ->where('id_user_hair_stylist', $item['id_user_hair_stylist'])
            //     ->first();

            // if(!empty($hsNotAvailable)){
            //     $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
            //     unset($post['item_service'][$key]);
            //     continue;
            // }

            //checking same time
            // foreach ($itemService as $s){
            //     if($item['id_user_hair_stylist'] == $s['id_user_hair_stylist'] &&
            //         strtotime($bookTimeStart) >= strtotime($s['booking_start']) && strtotime($bookTimeStart) < strtotime($s['booking_end'])){

            //         $errorHsNotAvailable[] = $item['user_hair_stylist_name']." (".MyHelper::dateFormatInd($bookTime).')';
            //         unset($post['item_service'][$key]);
            //         continue 2;
            //     }
            // }

            $itemService[] = [
                "id_custom" => $item['id_custom'],
                "id_brand" => $service['id_brand'],
                "id_product" => $service['id_product'],
                "product_code" => $service['product_code'],
                "product_name" => $service['product_name'],
                "product_price" => (int) $service['product_price'],
                "product_price_total" => (int)$service['product_price']*(int)$item['qty'],
                "product_tax" => (int) $service['product_tax'] ?? 0,
                "booking_date" => $item['booking_date'],
                "booking_date_display" => MyHelper::dateFormatInd($item['booking_date'], true, false),
                "qty" => $item['qty'],

            ];
            $subTotalService = $subTotalService + ($service['product_price']*$item['qty'] );
        }

        $mergeService = $this->mergeServiceV2($itemService);
        if(!empty($errorOutlet)){
            $error_msg[] = implode(',', array_unique($errorOutlet));
        }

        if(!empty($errorServiceName)){
            $error_msg[] = 'Service '.implode(',', array_unique($errorServiceName)). ' tidak tersedia dan akan terhapus dari cart.';
        }

        // if(!empty($errorHsNotAvailable)){
        //     $error_msg[] = 'Hair stylist '.implode(',', array_unique($errorHsNotAvailable)). ' tidak tersedia dan akan terhapus dari cart.';
        // }

        if(!empty($errorBookTime)){
            $error_msg[] = 'Waktu pemesanan untuk '.implode(',', array_unique($errorBookTime)). ' telah kadaluarsa.';
        }

        return [
            'total_item_service' => count($mergeService),
            'subtotal_service' => $subTotalService,
            'item_service' => $mergeService,
            'error_message' => $error_msg
        ];
    }

    public function availablePayment(Request $request)
    {
        $availablePayment = config('payment_method');
        
        $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
        $payments = [];
        
        if(isset($request->pos_order) && !empty($request->pos_order) && $request->pos_order == 1){
            $config = [
                'credit_card_payment_gateway' => MyHelper::setting('credit_card_payment_gateway', 'value', 'Ipay88'),
                'platform' => 'webapps'
            ];
        }else{
            $config = [
                'credit_card_payment_gateway' => MyHelper::setting('credit_card_payment_gateway', 'value', 'Ipay88'),
                'platform' => request()->user()->tokenCan('apps') ? 'native' : 'webapps',
            ];
        }

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
                'code'                          => $value['code'] ?? '',
                'payment_gateway'               => $payment['payment_gateway'] ?? '',
                'payment_method'                => $payment['payment_method'] ?? '',
                'logo'                          => $payment['logo'] ?? '',
                'text'                          => $payment['text'] ?? '',
                'text_2'                        => $payment['text_2'] ?? '',
                'id_chart_of_account'           => $value['id_chart_of_account'] ?? '',
                'description'                   => $value['description'] ?? '',
                'status'                        => (int) $value['status'] ? 1 : 0
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
                    'payment_gateway' => $payment['payment_gateway'] ?? '',
                    'payment_method'  => $payment['payment_method'] ?? '',
                    'logo'            => $payment['logo'] ?? '',
                    'text'            => $payment['text'] ?? '',
                    'id_chart_of_account'            => $payment['id_chart_of_account'] ?? '',
                    'description'     => $payment['description'] ?? '',
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

    public function mergeServiceV2($items){
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
                "product_price_total" => $item['product_price_total']??null,
                // "id_user_hair_stylist" => $item['id_user_hair_stylist'] ?? null,
                // "user_hair_stylist_name" => $item['user_hair_stylist_name'] ?? null,
                "booking_date" => $item['booking_date'],
                "booking_date_display" => $item['booking_date_display'],
                // "booking_time" => $item['booking_time'] ?? null,
                "qty" => $item['qty'],
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

   
    function insertServiceProductV2($data, $trx, $outlet, $post, &$productMidtrans, &$userTrxProduct, $payment_type = null){

        $tempStock = [];
        foreach ($data as $itemProduct){
            for($i = 0; $i < $itemProduct['qty']; $i++){
                $product = Product::leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                                ->where('products.id_product', $itemProduct['id_product'])
                                ->where('product_type', 'service')
                                ->select('products.*', 'brand_product.id_brand')->first();
    
                if (empty($product)) {
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Product Service Not Found '.$itemProduct['product_name']]
                    ];
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
    
                $allUse = ($tempStock[$product['id_product']]??0) + 1;
                $tempStock[$product['id_product']] = $allUse;
                if(!is_null($getProductDetail['product_detail_stock_item']) && $allUse > $getProductDetail['product_detail_stock_item']){
                    DB::rollback();
                    return [
                        'status'    => 'fail',
                        'messages'  => ['Product use in service '.$itemProduct['product_name']. ' not available']
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
                    'transaction_product_price_base'    => $price - ($itemProduct['product_tax'] ?? 0),
                    'transaction_product_price_tax'    => ($itemProduct['product_tax'] ?? 0),
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
                $order_id = 'IXBX-'.substr(time(), 3).MyHelper::createrandom(2, 'Angka');
                // $timeZone = $outlet['province_time_zone_utc'] - 7;
                // $bookTime = date('H:i:s', strtotime('-'.$timeZone.' hours', strtotime($itemProduct['booking_time'])));
    
                // if(isset($payment_type) && $payment_type == 'Cash'){
                //     $queue = TransactionProductService::join('transactions','transactions.id_transaction','transaction_product_services.id_transaction')->whereDate('schedule_date', date('Y-m-d', strtotime($itemProduct['booking_date'])))->where('id_outlet',$trx['id_outlet'])->where('transaction_product_services.id_transaction', '<>', $trx['id_transaction'])->max('queue') + 1;
                //     if($queue<10){
                //         $queue_code = '[00'.$queue.'] - '.$product['product_name'];
                //     }elseif($queue<100){
                //         $queue_code = '[0'.$queue.'] - '.$product['product_name'];
                //     }else{
                //         $queue_code = '['.$queue.'] - '.$product['product_name'];
                //     }
                // }else{
                //     $queue = null;
                //     $queue_code = null;
                // }

                $product_service = TransactionProductService::create([
                    'order_id' => $order_id,
                    'id_transaction' => $trx['id_transaction'],
                    'id_transaction_product' => $trx_product['id_transaction_product'],
                    'schedule_date' => date('Y-m-d', strtotime($itemProduct['booking_date'])),
                    // 'queue' => $queue,
                    // 'queue_code' => $queue_code,
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
        }

        return [
            'status'    => 'success'
        ];
    }


    function bookProductStock($id_transaction){
        $data = TransactionProduct::where('transactions.id_transaction', $id_transaction)
            ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
            ->select('transaction_products.*', 'transactions.id_outlet')
            ->get()->toArray();

        foreach ($data as $dt){
            $outletType = Outlet::join('locations', 'locations.id_location', 'outlets.id_location')->where('id_outlet', $dt['id_outlet'])
                        ->first()['company_type']??null;
            $outletType = strtolower(str_replace('PT ', '', $outletType));
            $getProductUse = ProductProductIcount::join('product_detail', 'product_detail.id_product', 'product_product_icounts.id_product')
                ->where('product_product_icounts.id_product', $dt['id_product'])
                ->where('company_type', $outletType)
                ->where('product_detail.id_outlet', $dt['id_outlet'])->get()->toArray();

            foreach ($getProductUse as $productUse){
                $product_icount = new ProductIcount();
                $update = $product_icount->find($productUse['id_product_icount'])->addLogStockProductIcount(-($productUse['qty']*$dt['transaction_product_qty']), $productUse['unit'], 'Book Product', $dt['id_transaction'], null, $dt['id_outlet']);
            }
        }

        return $update??true;
    }
}
