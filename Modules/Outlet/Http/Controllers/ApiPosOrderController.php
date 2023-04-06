<?php

namespace Modules\Outlet\Http\Controllers;

use App\Jobs\SyncronPlasticTypeOutlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\DailyTransactions;
use Lcobucci\JWT\Parser;
use App\Jobs\FraudJob;

use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
use App\Http\Models\UserOutletApp;
use App\Http\Models\Holiday;
use App\Http\Models\DateHoliday;
use App\Http\Models\OutletPhoto;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\Configs;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Modules\Outlet\Entities\DeliveryOutlet;
use Modules\Outlet\Entities\OutletBox;
use Modules\POS\Http\Requests\reqMember;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Franchise\Entities\UserFranchise;
use Modules\Franchise\Entities\UserFranchiseOultet;
use App\Http\Models\LogBalance;
use Modules\Transaction\Entities\TransactionPaymentCash;
use App\Http\Models\TransactionMultiplePayment;
use Modules\Outlet\Entities\OutletScheduleUpdate;
use Modules\Transaction\Entities\TransactionOutletService;
use App\Http\Models\Province;

use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceLog;
use App\Http\Models\TransactionSetting;
use App\Http\Models\TransactionProductModifier;
use Modules\Transaction\Entities\TransactionBundlingProduct;
use App\Http\Models\TransactionPaymentMidtran;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Modules\ShopeePay\Entities\TransactionPaymentShopeePay;
use App\Http\Models\TransactionPaymentBalance;
use App\Http\Models\ManualPaymentMethod;

use App\Imports\ExcelImport;
use App\Imports\FirstSheetOnlyImport;
use Modules\UserRating\Entities\UserRatingLog;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;
use App\Lib\Midtrans;

use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\Brand;

use Modules\Outlet\Http\Requests\Outlet\Upload;
use Modules\Outlet\Http\Requests\Outlet\Update;
use Modules\Outlet\Http\Requests\Outlet\UpdateStatus;
use Modules\Outlet\Http\Requests\Outlet\UpdatePhoto;
use Modules\Outlet\Http\Requests\Outlet\UploadPhoto;
use Modules\Outlet\Http\Requests\Outlet\Create;
use Modules\Outlet\Http\Requests\Outlet\Delete;
use Modules\Outlet\Http\Requests\Outlet\DeletePhoto;
use Modules\Outlet\Http\Requests\Outlet\Nearme;
use Modules\Outlet\Http\Requests\Outlet\Filter;
use Modules\Outlet\Http\Requests\Outlet\OutletList;
use Modules\Balance\Http\Controllers\NewTopupController;
use Modules\Outlet\Http\Requests\Outlet\OutletListOrderNow;

use Modules\Outlet\Http\Requests\UserOutlet\Create as CreateUserOutlet;
use Modules\Outlet\Http\Requests\UserOutlet\Update as UpdateUserOutlet;

use Modules\Outlet\Http\Requests\Holiday\HolidayStore;
use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use Modules\Outlet\Http\Requests\Holiday\HolidayUpdate;
use Modules\Outlet\Http\Requests\Holiday\HolidayDelete;

use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;

class ApiPosOrderController extends Controller
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
        $this->online_trx = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->confirm = "Modules\Transaction\Http\Controllers\ApiConfirm";
        $this->shopeepay      = "Modules\ShopeePay\Http\Controllers\ShopeePayController";
        $this->trx_outlet_service = "Modules\Transaction\Http\Controllers\ApiTransactionOutletService";
    }

    public function home(Request $request){
        
        $post = $request->json()->all();
        $outlet = $this->getOutlet($post['outlet_code']??null);
        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
        ->where('id_outlet', $outlet['id_outlet'])->first();

        if(!$outlet){
            return [
    			'status' => 'fail',
    			'title' => 'Outlet Code Salah',
    			'messages' => ['Tidak dapat mendapat data outlet.']
    		];
        } 
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $outlet = [
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name']
        ];

        
        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        $productServie = Product::select([
                'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status',
                DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                            ELSE product_global_price.product_global_price
                        END) as product_price')
            ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->where('brand_outlet.id_outlet', '=', $outlet['id_outlet'])
            ->where('brand_product.id_brand', '=', $brand['id_brand'])
            ->where('product_type', 'service')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                    END)')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                    END)')
            ->where(function ($query) use ($outlet) {
                $query->WhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_special_price desc limit 1) is NOT NULL');
                $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product order by id_product_global_price desc limit 1) is NOT NULL');
            })
            ->with(['photos', 'product_service_use'])
            ->having('product_price', '>', 0)
            ->groupBy('products.id_product')
            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get()->toArray();

        $resProdService = [];
        foreach ($productServie as $val){
            $stockStatus = 'Available';
            $getProductDetail = ProductDetail::where('id_product', $val['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();

            if(!is_null($getProductDetail['product_detail_stock_item']) && $getProductDetail['product_detail_stock_item'] <= 0){
                $stockStatus = 'Sold Out';
            }elseif (is_null($getProductDetail['product_detail_stock_item']) && ($getProductDetail['product_detail_stock_status'] == 'Sold Out' || $getProductDetail['product_detail_status'] == 'Inactive')){
                $stockStatus = 'Sold Out';
            }elseif(empty($getProductDetail)){
                $stockStatus = 'Sold Out';
            }

            $resProdService[] = [
                'id_product' => $val['id_product'],
                'id_brand' => $brand['id_brand'],
                'product_type' => 'service',
                'product_code' => $val['product_code'],
                'product_name' => $val['product_name'],
                'product_description' => $val['product_description'],
                'product_price' => (int)$val['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$val['product_price'],0,",","."),
                'product_stock_status' => $stockStatus,
                'photo' => (empty($val['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$val['photos'][0]['product_photo'])
            ];
        }

        $products = Product::select([
            'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status',
            DB::raw('(CASE
                        WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                        THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                        ELSE product_global_price.product_global_price
                    END) as product_price'),
            DB::raw('(select product_detail.product_detail_stock_item from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1) as product_stock_status')
        ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->join('brand_outlet', 'brand_outlet.id_brand', '=', 'brand_product.id_brand')
            ->where('brand_outlet.id_outlet', '=', $outlet['id_outlet'])
            ->where('brand_product.id_brand', '=', $brand['id_brand'])
            ->where('product_type', 'product')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                    END)')
            ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                    END)')
            ->where(function ($query) use ($outlet) {
                $query->WhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . '  order by id_product_special_price desc limit 1) is NOT NULL');
                $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product order by id_product_global_price desc limit 1) is NOT NULL');
            })
            ->with(['photos'])
            ->having('product_price', '>', 0)
            ->groupBy('products.id_product')
            ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get()->toArray();

        $resProducts = [];
        foreach ($products as $val){
            if ($val['product_variant_status'] && $val['product_stock_status'] == 'Available') {
                $variantTree = Product::getVariantTree($val['id_product'], ['id_outlet' => $outlet['id_outlet'], 'outlet_different_price' => $outlet['outlet_different_price']]);
                $val['product_price'] = ($variantTree['base_price']??false)?:$val['product_price'];
            }

            $stock = 'Available';
            if($val['product_stock_status'] <= 0){
                $stock = 'Sold Out';
            }

            $resProducts[] = [
                'id_product' => $val['id_product'],
                'id_brand' => $brand['id_brand'],
                'product_type' => 'product',
                'product_code' => $val['product_code'],
                'product_name' => $val['product_name'],
                'product_description' => $val['product_description'],
                'product_price' => (int)$val['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$val['product_price'],0,",","."),
                'product_stock_status' => $stock,
                'qty_stock' => (int)$val['product_stock_status'],
                'photo' => (empty($val['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$val['photos'][0]['product_photo'])
            ];
        }

        $listHs = UserHairStylist::where('id_outlet', $outlet['id_outlet'])
            ->where('user_hair_stylist_status', 'Active')->get()->toArray();
        $bookTime = date('H:i:s');
        $bookTimeOrigin = date('H:i:s');
        $bookDate = date('Y-m-d');

        $res = [];
        foreach ($listHs as $val){
            $availableStatus = false;
            $current_service = null;
            //check schedule hs
            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                ->whereDate('date', date('Y-m-d'))
                ->first();

            if(empty($shift)){
                continue;
            }

            $clockInOut = HairstylistAttendance::where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                ->where('id_hairstylist_schedule_date', $shift['id_hairstylist_schedule_date'])->orderBy('updated_at', 'desc')->first();
                    
            if(!empty($clockInOut) && !empty($clockInOut['clock_in']) && strtotime($bookTime) >= strtotime($clockInOut['clock_in'])){
                $availableStatus = true;
                $lastAction = HairstylistAttendanceLog::where('id_hairstylist_attendance', $clockInOut['id_hairstylist_attendance'])->orderBy('datetime', 'desc')->first();
                if(!empty($clockInOut['clock_out']) && $lastAction['type'] == 'clock_out' && strtotime($bookTime) > strtotime($clockInOut['clock_out'])){
                    $availableStatus = false;
                }
            }

            $bookTimeOrigin = date('H:i:s', strtotime($bookTimeOrigin . "+ 1 minutes"));
            $notAvailable = HairstylistNotAvailable::where('id_outlet', $outlet['id_outlet'])
                ->whereRaw('"'.$bookDate.' '.$bookTimeOrigin. '" BETWEEN booking_start AND booking_end')
                ->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                ->first();

            $currentService = TransactionProductService::where('service_status', 'In Progress')
			->whereDate('schedule_date', $bookDate)
            ->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
            ->first();

            if(!empty($currentService)){
                if($currentService['queue']<10){
                    $current_service = '00'.$currentService['queue'];
                }elseif($currentService['queue']<100){
                    $current_service = '0'.$currentService['queue'];
                }else{
                    $current_service = ''.$currentService['queue'];
                }
            }
            
            $until = null;
            $now = strtotime($bookTime);
            $shift_end = strtotime($shift['time_end']);
            $diff = $shift_end - $now;
            $hour = floor($diff / (60*60));
            if($hour<1){
                $minute = floor(($diff - ($hour*60*60))/(60));
                $until = 'Shift end in '.$minute.'mnt';
            }

            if(!empty($notAvailable)){
                $availableStatus = false;
            }

            $res[] = [
                'id_user_hair_stylist' => $val['id_user_hair_stylist'],
                'name' => "$val[fullname] ($val[nickname])",
                'nickname' => $val['nickname'],
                'shift_time' => MyHelper::adjustTimezone($shift['time_start'], $timeZone, 'H:i', true).' - '.MyHelper::adjustTimezone($shift['time_end'], $timeZone, 'H:i', true),
                'photo' => (empty($val['user_hair_stylist_photo']) ? config('url.storage_url_api').'img/product/item/default.png':$val['user_hair_stylist_photo']),
                'available_status' => $availableStatus,
                'current_service' => $current_service,
                'end_shift' => $until,
                'order' => ($availableStatus ? $val['id_user_hair_stylist']:1000)
            ];

            
            
        }
        $data = [
            'outlet' => $outlet,
            'product_services' => $resProdService,
            'products' => $resProducts,
            'available_hs' => $res
        ];
        return response()->json(['status' => 'success', 'result' => $data]);
    }

    public function getOutlet($outlet_code = null){

        if(!$outlet_code){
            return false;
        }

        $outlet = Outlet::where('outlet_code', $outlet_code)->with('today')->where('outlet_status', 'Active')->where('outlets.outlet_service_status', 1)
        ->join('cities', 'cities.id_city', 'outlets.id_city')
        ->join('provinces', 'provinces.id_province', 'cities.id_province')
        ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')
        ->first();
        return $outlet;
    }

    public function listQueue(Request $request){
        $post = $request->json()->all();
        $outlet = $this->getOutlet($post['outlet_code']??null);

        if(!$outlet){
            return [
    			'status' => 'fail',
    			'title' => 'Outlet Code Salah',
    			'messages' => ['Tidak dapat mendapat data outlet.']
    		];
        } 

        $queue = [];
        $current = [];
        $service_outlets = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
            ->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
            ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->join('products', 'transaction_products.id_product', 'products.id_product')
            ->where(function($q) {
                $q->whereNull('service_status');
                $q->orWhere('service_status','In Progress');
            })
            ->where(function($q){
                $q->whereNull('transaction_product_services.id_user_hair_stylist');
                $q->orWhereNotNull('transaction_product_services.id_user_hair_stylist');
            })
            ->where(function($q) {
                $q->where('trasaction_payment_type', 'Cash')
                ->orWhere('transaction_payment_status', 'Completed');
            })
            ->where('transactions.id_outlet',$outlet['id_outlet'])
            ->whereNotNull('transaction_product_services.queue')
            ->whereNotNull('transaction_product_services.queue_code')
            ->whereDate('schedule_date',date('Y-m-d'))
            ->where('transaction_payment_status', '!=', 'Cancelled')
            ->wherenull('transaction_products.reject_at')
            ->orderBy('queue', 'asc')
            ->select('transactions.id_transaction','transaction_product_services.id_transaction_product_service','transaction_product_services.queue_code','service_status','transaction_product_services.id_user_hair_stylist')
            ->get()->toArray();

        foreach($service_outlets ?? [] as $key => $service_outlet){
            if(!isset($service_outlet['service_status']) && !isset($service_outlet['id_user_hair_stylist'])){
                $queue[] = $service_outlet;
            }elseif(isset($service_outlet['service_status']) && $service_outlet['service_status'] == 'In Progress' && isset($service_outlet['id_user_hair_stylist'])){
                $current[] = $service_outlet;
            }
        }

        $data = [
            'current_cust' => $current,
            'waiting' => $queue
        ];

        return response()->json(['status' => 'success', 'result' => $data]);

    }

    public function checkTransaction(Request $request){
      
        $post = $request->json()->all();

        if(empty($post['outlet_code'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Code outlet can not be empty']
            ]);
        }

        $outlet = $this->getOutlet($post['outlet_code']??null);

        if(!$outlet){
            return [
    			'status' => 'fail',
    			'title' => 'Outlet Code Salah',
    			'messages' => ['Tidak dapat mendapat data outlet.']
    		];
        } 
        unset($post['outlet_code']);
        $post['id_outlet'] = $outlet['id_outlet']??null;

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

        $shippingGoSend = 0;
        $error_msg=[];

        if(empty($post['type'])){
            $post['type'] = null;
        }

        if (!isset($post['subtotal'])) {
            $post['subtotal'] = 0;
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

        $post['transaction_from'] = 'outlet-service';
        $result['item_service'] = [];
        $totalItem = 0;
        $totalDisProduct = 0;
        if(!empty($post['item_service'])){
            $itemServices = app($this->online_trx)->checkServiceProductV2($post, $outlet);
            $result['item_service'] = $itemServices['item_service']??[];
            $post['item_service'] = $itemServices['item_service']??[];
            $totalItem = $totalItem + $itemServices['total_item_service']??0;
            if(!isset($post['from_new']) || (isset($post['from_new']) && $post['from_new'] === false)){
                $error_msg = array_merge($error_msg, $itemServices['error_message']??[]);
            }
        }
        $post['discount'] = -$post['discount'];
        $subtotal = 0;
        $items = [];
        $post['item'] = app($this->online_trx)->mergeProducts($post['item']);
        $grandTotal = app($this->setting_trx)->grandTotal();
        
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
            // get detail product
            $product = Product::select([
                    'products.id_product','products.product_name','products.product_code','products.product_description',
                    DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = '.$post['id_outlet'].' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = '.$post['id_outlet'].' )
                            ELSE product_global_price.product_global_price
                        END) as product_price'),
                    DB::raw('(select product_detail.product_detail_stock_item from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $outlet['id_outlet'] . ' order by id_product_detail desc limit 1) as product_stock_status'),
                    'brand_product.id_brand', 'products.product_variant_status'
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
                    },
                    'product_promo_categories' => function($query){
                        $query->select('product_promo_categories.id_product_promo_category','product_promo_category_name as product_category_name','product_promo_category_order as product_category_order');
                    },
                ])
            ->having('product_price','>',0)
            ->groupBy('products.id_product')
            ->orderBy('products.position')
            ->find($item['id_product']);
            $product->append('photo');
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

            $product['product_price_total'] = $item['transaction_product_subtotal'];
            $product['product_price_raw'] = (int) $product['product_price'];
            $product['product_price_raw_total'] = (int) $product['product_price'];
            $product['qty_stock'] = (int)$product['product_stock_status'];
            $product['product_price'] = (int) $product['product_price'];
            $subtotalProduct = $subtotalProduct + $item['transaction_product_subtotal'];

            //calculate total item
            $totalItem += $product['qty'];
            if(!empty($product['product_stock_status'])){
                $product['product_stock_status'] = 'Available';
            }else{
                $product['product_stock_status'] = 'Sold Out';
            }
            $items[] = $product;
        }

        $result['outlet'] = [
            'id_outlet' => $outlet['id_outlet'],
            'outlet_code' => $outlet['outlet_code'],
            'outlet_name' => $outlet['outlet_name'],
            'outlet_address' => $outlet['outlet_address'],
            'delivery_order' => $outlet['delivery_order'],
            'today' => $outlet['today']
        ];
        
        if(!empty($post['phone']) || isset($post['phone'])){
            $user = User::with('memberships')->where('phone',$post['phone'])->first();
            $result['customer'] = [
                "name" => $user['name']??"",
                "phone" => $user['phone']??"",
            ];
            
            $cashBack = app($this->setting_trx)->countTransaction('cashback', $post);
            $countUserTrx = Transaction::where('id_user', $user['id'])->where('transaction_payment_status', 'Completed')->count();
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
            
            $balance = app($this->balance)->balanceNow($user['id']);
        }else{
            $user = User::where('phone',$outlet['outlet_code'])->where('is_anon',1)->first();
            if(!$user){
                $user = User::create([
                    'name' => 'Anonymous '.$outlet['outlet_code'],
                    'phone' => $outlet['outlet_code'],
                    'id_membership' => NULL,
                    'email' => $outlet['outlet_code'],
                    'password' => '$2y$10$4CmCne./LBVkIkI1RQghxOOZWuzk7bAW2kVtJ66uSUzmTM/wbyury',
                    'id_city' => $outlet['id_city'],
                    'gender' => 'male',
                    'provider' => NULL,
                    'birthday' => NULL,
                    'phone_verified' => '1',
                    'email_verified' => '1',
                    'level' => 'Customer',
                    'points' => 0,
                    'android_device' => NULL,
                    'ios_device' => NULL,
                    'is_suspended' => '0',
                    'remember_token' => NULL,   
                    'is_anon' => 1
                ]);
            }
            $result['customer'] = [
                "name" => $user['name']??"",
                "phone" => $user['phone']??"",
            ];
        }

        $result['item'] = $items;
        $result['subtotal_product_service'] = $itemServices['subtotal_service']??0;
        $result['subtotal_product'] = $subtotalProduct;
        $post['subtotal'] = $result['subtotal_product_service'] + $result['subtotal_product'];

        $result['subtotal'] = $result['subtotal_product_service'] + $result['subtotal_product'];
        $result['shipping'] = $post['shipping']+$shippingGoSend;
        $result['discount'] = $post['discount'];
        $result['discount_delivery'] = $post['discount_delivery'];
        $result['cashback'] = $cashBack??0;
        $result['service'] = $post['service'];
        $result['grandtotal'] = (int)$result['subtotal'] + (int)(-$post['discount']) + (int)$post['service'];
        $result['tax'] = (int) ($result['grandtotal'] * ($outlet['is_tax'] ?? 0) / (100 + ($outlet['is_tax'] ?? 0)));
        $result['subscription'] = 0;
        $result['used_point'] = 0;
        
        $result['total_payment'] = $result['grandtotal'] - $result['used_point'];
        $result['discount'] = (int) $result['discount'];
        $result['continue_checkout'] = true;
        $result['currency'] = 'Rp';
        $result['complete_profile'] = true;
        $result['point_earned'] = null;
        $result['payment_detail'] = [];
        $fake_request = new Request(['show_all' => 1,'pos_order'=> 1]);
        $result['available_payment'] = app($this->online_trx)->availablePayment($fake_request)['result'] ?? [];
        
        if($post['phone']){
            $result['points'] = (int) $balance??0;
        }else{
            $result['points'] = 0;
        }
        $result = app($this->promo_trx)->applyPromoCheckoutV2($result,$post, $user??[]);

        if ($result['cashback']) {
            $result['point_earned'] = [
                'value' => MyHelper::requestNumber($result['cashback'], '_CURRENCY'),
                'text' => MyHelper::setting('cashback_earned_text', 'value', 'Point yang akan didapatkan')
            ];
        }

        $result['payment_detail'][] = [
            'name'          => 'Total:',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($result['subtotal'],'_CURRENCY')
        ];

        if (!empty($result['tax'])) {
            $result['payment_detail'][] = [
                'name'          => 'Base Price:',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber((int) ($result['subtotal'] - $result['tax']),'_CURRENCY')
            ];
            $result['payment_detail'][] = [
                'name'          => 'Tax:',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber(round($result['tax']),'_CURRENCY')
            ];
        }
        $paymentDetailPromo = app($this->promo_trx)->paymentDetailPromo($result);
        $result['payment_detail'] = array_merge($result['payment_detail'], $paymentDetailPromo);
        
        if (count($error_msg) > 1 && (!empty($post['item_service']))) {
            $error_msg = ['Produk atau Service yang anda pilih tidak tersedia. Silakan cek kembali pesanan anda'];
        }

        $result['messages_all'] = null;
        $result['messages_all_title'] = null;
        if(!empty($error_msg)){
            $result['continue_checkout'] = false;
            $result['messages_all_title'] = 'TRANSAKSI TIDAK DAPAT DILANJUTKAN';
            $result['messages_all'] = implode('.', $error_msg);
        }
        if($result['promo_deals']){
            if($result['promo_deals']['is_error']){
                $result['continue_checkout'] = false;
                $result['messages_all_title'] = 'VOUCHER ANDA TIDAK DAPAT DIGUNAKAN';
                $result['messages_all'] = 'Silahkan gunakan voucher yang berlaku atau tidak menggunakan voucher sama sekali.';
            }
        }
        if($result['promo_code']){
            if($result['promo_code']['is_error']){
                $result['continue_checkout'] = false;
                $result['messages_all_title'] = 'PROMO ANDA TIDAK DAPAT DIGUNAKAN';
                $result['messages_all'] = 'Silahkan gunakan promo yang berlaku atau tidak menggunakan promo sama sekali.';
            }
        }
        $result['tax'] = (int) $result['tax'];
        return MyHelper::checkGet($result);
    }

    public function newTransaction(Request $request){
        
        $post = $request->json()->all();
        
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
        }else{
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Code outlet can not be empty']
            ]);
        }
        
        unset($post['outlet_code']);

        if(empty($post['item_service']) && empty($post['item'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item and Item Service can not be empty']
            ]);
        }

        $post['item'] = app($this->online_trx)->mergeProducts($post['item']??[]);

        $totalPrice = 0;
        $totalWeight = 0;
        $totalDiscount = 0;
        $grandTotal = app($this->setting_trx)->grandTotal();
        $order_id = null;
        $id_pickup_go_send = null;
        $promo_code_ref = null;

        $dataInsertProduct = [];
        $productMidtrans = [];
        $dataDetailProduct = [];
        $userTrxProduct = [];

        if (isset($post['transaction_date'])) {
            $post['transaction_date'] = date('Y-m-d H:i:s', strtotime($post['transaction_date']));
        } else {
            $post['transaction_date'] = date('Y-m-d H:i:s');
        }

        if(isset($outlet['outlet_status']) && $outlet['outlet_status'] == 'Inactive'){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet tutup']
            ]);
        }

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
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Outlet tutup']
                        ]);
                    }
                }
            }

            if($outlet['today']['is_closed'] == '1'){
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Outlet tutup']
                ]);
            }

            if($outlet['today']['close'] && $outlet['today']['open']){

                $settingTime = Setting::where('key', 'processing_time')->first();
                if($settingTime && $settingTime->value){
                    if($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close']))){
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Outlet tutup']
                        ]);
                    }
                }

                //cek outlet open - close hour
                if(($outlet['today']['open'] && date('H:i') < date('H:i', strtotime($outlet['today']['open']))) || ($outlet['today']['close'] && date('H:i') > date('H:i', strtotime($outlet['today']['close'])))){
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
        $totalDisProduct = 0;

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

                $post['subtotal'] = array_sum($post['sub']);
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

        $post['discount'] = 0;
        $post['point'] = 0;
        $post['cashback'] = 0;

        if(!empty($post['phone']) || isset($post['phone'])){
            $post['point'] = app($this->setting_trx)->countTransaction('point', $post);
            $post['cashback'] = app($this->setting_trx)->countTransaction('cashback', $post);
            $user = User::leftJoin('cities', 'cities.id_city', 'users.id_city')
            ->select('users.*', 'cities.city_name')->with('memberships')->where('phone',$post['phone'])->first();
            if (empty($user)) {
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['User Not Found']
                ]);
            }
            $post['customer'] = [
                "name" => $user['name']??"",
                "phone" => $user['phone']??"",
            ];
            if (count($user['memberships']) > 0) {
                $post['membership_level']    = $user['memberships'][0]['membership_name'];
                $post['membership_promo_id'] = $user['memberships'][0]['benefit_promo_id'];
            } else {
                $post['membership_level']    = null;
                $post['membership_promo_id'] = null;
            }

            //count some trx user
            $countUserTrx = Transaction::where('id_user', $user['id'])->where('transaction_payment_status', 'Completed')->count();

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
        }else{
            $user = User::where('phone',$outlet['outlet_code'])->where('is_anon',1)->first();
            if(!$user){
                $user = User::create([
                    'name' => 'Anonymous '.$outlet['outlet_code'],
                    'phone' => $outlet['outlet_code'],
                    'id_membership' => NULL,
                    'email' => $outlet['outlet_code'],
                    'password' => '$2y$10$4CmCne./LBVkIkI1RQghxOOZWuzk7bAW2kVtJ66uSUzmTM/wbyury',
                    'id_city' => $outlet['id_city'],
                    'gender' => 'male',
                    'provider' => NULL,
                    'birthday' => NULL,
                    'phone_verified' => '1',
                    'email_verified' => '1',
                    'level' => 'Customer',
                    'points' => 0,
                    'android_device' => NULL,
                    'ios_device' => NULL,
                    'is_suspended' => '0',
                    'remember_token' => NULL,   
                    'is_anon' => 1
                ]);
            }
            $post['membership_level']    = null;
            $post['membership_promo_id'] = null;
            $post['customer'] = [];
        }

        if (isset($post['pin']) && strtolower($post['payment_type']) == 'balance') {
            if (!password_verify($post['pin'], $user['password'])) {
                return [
                    'status' => 'fail',
                    'messages' => ['Incorrect PIN']
                ];
            }
        }

        $config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->first()->is_active;

        $error_msg=[];
        //check product service
        if(!empty($post['item_service'])){
            $productService = app($this->online_trx)->checkServiceProductV2($post, $outlet);
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

        if (!isset($post['payment_type'])) {
            $post['payment_type'] = null;
        }

        if ($post['payment_type'] && $post['payment_type'] != 'Balance') {
            $available_payment = app($this->online_trx)->availablePayment(new Request(['show_all' => 1,'pos_order'=> 1]))['result'] ?? [];
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
            'id_user'                     => $user['id']??null,
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
            'membership_level'            => $post['membership_level']??null,
            'membership_promo_id'         => $post['membership_promo_id']??null,
            'latitude'                    => $post['latitude']??null,
            'longitude'                   => $post['longitude']??null,
            'void_date'                   => null,
            'transaction_from'            => 'outlet-service',
            'scope'                       => 'pos-order',
            'customer_name'               => $user['name']??null,
            'customer_email'              => $user['email']??null,
            'customer_domicile'           => $user['domicile']??null,
            'customer_birtdate'           => $user['birthdate']??null,
            'customer_gender'             => $user['gender'??null]
        ];

        $newTopupController = new NewTopupController();

        if(isset($post['phone']) && $user['complete_profile'] == 1){
            $transaction['calculate_achievement'] = 'not yet';
            $checkHashBefore = $newTopupController->checkHash('log_balances', $user['id']);
            if (!$checkHashBefore) {
                DB::rollback();
                return response()->json([
                    'status'    => 'fail',
                    'messages'  => ['Your previous transaction data is invalid']
                ]);
            }
        }else{
            $transaction['calculate_achievement'] = 'no';
        }

        if($transaction['transaction_grandtotal'] == 0){
            $transaction['transaction_payment_status'] = 'Completed';
        }

        if($transaction['transaction_grandtotal'] == 0){
            $transaction['transaction_payment_status'] = 'Completed';
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

        $createOutletService = TransactionOutletService::create([
            'id_transaction' => $insertTransaction['id_transaction'],
            'customer_name' => $user['name']??null,
            'customer_email' => $user['email']??null,
            'customer_domicile' => $user['domicile']??null,
            'customer_birtdate' => $user['birthdate']??null,
            'customer_gender' => $user['gender']??null
        ]);
        if (!$createOutletService) {
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Insert Transaction Outlet Service Failed']
            ]);
        }

        $lastReceipt = Transaction::where('id_outlet', $insertTransaction['id_outlet'])->orderBy('transaction_receipt_number', 'desc')->first()['transaction_receipt_number']??'';
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

        if($user){
            MyHelper::updateFlagTransactionOnline($insertTransaction, 'pending', $user);
        }
        
        $insertTransaction['transaction_receipt_number'] = $receipt;
        //process add product service
        if(!empty($post['item_service'])){
            $insertService = app($this->online_trx)->insertServiceProductV2($post['item_service']??[], $insertTransaction, $outlet, $post, $productMidtrans, $userTrxProduct, $post['payment_type']??null);
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
        
        $applyPromo = app($this->promo_trx)->applyPromoNewTrxV2($insertTransaction, $user);
        if ($applyPromo['status'] == 'fail') {
            DB::rollback();
            return $applyPromo;
        }

        $insertTransaction = $applyPromo['result'] ?? $insertTransaction;
        
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

        if ($user && $user['is_anon'] == 0) {
            $sumBalance = LogBalance::where('id_user', $user['id'])->sum('balance');
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
            //sum balance
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

            $dataRedirect = app($this->online_trx)->dataRedirect($insertTransaction['transaction_receipt_number'], 'trx', '1');

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

        if (isset($post['id_deals_user'])) {
            $voucherUsage = TransactionPromo::where('id_deals_user', $post['id_deals_user'])->count();
            if (($voucherUsage ?? false) > 1) {
                DB::rollBack();
                return [
                    'status' => 'fail',
                    'messages' => ['Voucher sudah pernah digunakan']
                ];
            }
        }
        DB::commit();

        if(!empty($insertTransaction['id_transaction']) && $insertTransaction['transaction_grandtotal'] == 0){
            $trx = Transaction::where('id_transaction', $insertTransaction['id_transaction'])->first();
            app($this->online_trx)->bookProductStock($trx['id_transaction']);
            optional($trx)->recalculateTaxandMDR();
            $trx->triggerPaymentCompleted();
        }

        $insertTransaction['cancel_message'] = 'Are you sure you want to cancel this transaction?';
        $insertTransaction['timer_shopeepay'] = (int) MyHelper::setting('shopeepay_validity_period','value', 300);
        $insertTransaction['message_timeout_shopeepay'] = "Sorry, your payment has expired";
        return response()->json([
            'status'   => 'success',
            'redirect' => true,
            'result'   => $insertTransaction
        ]);
    }

    public function confirmTransaction(Request $request){

        DB::beginTransaction();
        $post = $request->json()->all();
        
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
        }else{
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Code outlet can not be empty']
            ]);
        }

        if(isset($post['phone']) && !empty($post['phone'])){
            $user = User::where('phone', $post['phone'])->first();
        }else{
            $user = User::where('phone',$outlet['outlet_code'])->where('is_anon',1)->first();
            if(!$user){
                $user = User::create([
                    'name' => 'Anonymous '.$outlet['outlet_code'],
                    'phone' => $outlet['outlet_code'],
                    'id_membership' => NULL,
                    'email' => $outlet['outlet_code'],
                    'password' => '$2y$10$4CmCne./LBVkIkI1RQghxOOZWuzk7bAW2kVtJ66uSUzmTM/wbyury',
                    'id_city' => $outlet['id_city'],
                    'gender' => 'male',
                    'provider' => NULL,
                    'birthday' => NULL,
                    'phone_verified' => '1',
                    'email_verified' => '1',
                    'level' => 'Customer',
                    'points' => 0,
                    'android_device' => NULL,
                    'ios_device' => NULL,
                    'is_suspended' => '0',
                    'remember_token' => NULL,   
                    'is_anon' => 1
                ]);
            }
        }

        if ($post['payment_type'] && $post['payment_type'] != 'Balance') {
            $available_payment = app($this->online_trx)->availablePayment(new Request(['show_all' => 1,'pos_order'=> 1]))['result'] ?? [];
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
            DB::rollback();
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Not Found'],
            ]);
        }

        if ($check['transaction_payment_status'] != 'Pending') {
            DB::rollback();
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Invalid'],
            ]);
        }

        if ($check['id_user'] != $user['id']) {
            DB::rollback();
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
                'email'           => $user['is_anon'] == 0 ? $user['email'] : null,
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
                'email'           => $user['is_anon'] == 0 ? (!empty($checkOutletService['customer_email']) ? $checkOutletService['customer_email'] : $user['email']) : null,
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
                $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $countGrandTotal, $dataUser, $dataShipping, $dataDetailProduct, 'trx', $check['id_transaction'], $post['payment_detail'], 'pos-order', $outletCode, $check['transaction_from']);
            } else {
                $dataMidtrans = array(
                    'transaction_details' => $transaction_details,
                    'customer_details'    => $dataUser,
                );
                $connectMidtrans = Midtrans::token($check['transaction_receipt_number'], $countGrandTotal, $dataUser, $ship=null, $dataDetailProduct, 'trx', $check['id_transaction'], $post['payment_detail'], 'pos-order', $outletCode, $check['transaction_from']);
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
                    'redirect_url'     => $connectMidtrans['redirect_url'].(config('app.env') == 'staging' ? '-qris' : '-qris'),
                    'transaction_data' => $dataMidtrans,
                    'url'              => env('VIEW_URL') . '/transaction/web/view/detail?data=' . $base,
                ]

            ];
            \Cache::put('midtrans_confirm_'.$check['id_transaction'], $response, now()->addMinutes(10));

            //book item and hs
            if($check['transaction_from'] == 'outlet-service' || $check['transaction_from'] == 'shop'){
                app($this->online_trx)->bookProductStock($check['id_transaction']);
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

            $pay = app($this->confirm)->paymentOvo($check, $countGrandTotal, $phone, env('OVO_ENV') ?: 'staging');

            return $pay;
        } elseif ($post['payment_type'] == 'Ipay88') {

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
            $payment_id = $post['payment_id'] ?? $post['payment_detail'];
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
                    app($this->online_trx)->bookProductStock($check['id_transaction']);
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
                app($this->online_trx)->bookProductStock($check['id_transaction']);
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

    public function doneTransaction(Request $request){

        $post = $request->json()->all();

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
        }else{
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Code outlet can not be empty']
            ]);
        }

        if(!empty($post['phone']) || isset($post['phone'])){
            $user = User::leftJoin('cities', 'cities.id_city', 'users.id_city')
            ->select('users.*', 'cities.city_name')->with('memberships')->where('phone',$post['phone'])->first();
        }else{
            $user = User::where('phone',$outlet['outlet_code'])->where('is_anon',1)->first();
            if(!$user){
                $user = User::create([
                    'name' => 'Anonymous '.$outlet['outlet_code'],
                    'phone' => $outlet['outlet_code'],
                    'id_membership' => NULL,
                    'email' => $outlet['outlet_code'],
                    'password' => '$2y$10$4CmCne./LBVkIkI1RQghxOOZWuzk7bAW2kVtJ66uSUzmTM/wbyury',
                    'id_city' => $outlet['id_city'],
                    'gender' => 'male',
                    'provider' => NULL,
                    'birthday' => NULL,
                    'phone_verified' => '1',
                    'email_verified' => '1',
                    'level' => 'Customer',
                    'points' => 0,
                    'android_device' => NULL,
                    'ios_device' => NULL,
                    'is_suspended' => '0',
                    'remember_token' => NULL,   
                    'is_anon' => 1
                ]);
            }
        }

        $check = Transaction::with('transaction_products.transaction_product_service')->where('id_transaction', $post['id_transaction'])->first();

        if (empty($check)) {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Not Found'],
            ]);
        }

        if ($check['transaction_payment_status'] != 'Completed') {
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Invalid'],
            ]);
        }

        if ($check['id_user'] != $user['id']) {
            DB::rollback();
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Invalid'],
            ]);
        }

        if ($check['id_outlet'] != $outlet['id_outlet']) {
            DB::rollback();
            return response()->json([
                'status'   => 'fail',
                'messages' => ['Transaction Invalid'],
            ]);
        }

        $queue_code = null;
        $queue = $check['transaction_products'][0]['transaction_product_service']['queue']??null;
        if($queue){
            if($queue<10){
                $queue_code = '00'.$queue;
            }elseif($queue<100){
                $queue_code = '0'.$queue;
            }else{
                $queue_code = $queue;
            }
        }
        $detail_code = $check['transaction_receipt_number'].'/'.$outlet['outlet_code'].'/'.$user['id'];
        $data = [
            'queue' => $queue,
            'qrcode_transaction_detail' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $detail_code) . '&margin=0&size=250',
            'transaction_receipt_number' => $check['transaction_receipt_number'],
            'qrcode_service' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $check['transaction_receipt_number']) . '&margin=0&size=300',
        ];
    	return response()->json(MyHelper::checkGet($data));
    }

    public function detailTransaction(Request $request){

        $post = $request->json()->all();

        if ($post['transaction_receipt_number'] !== null) {
            $trx = Transaction::where(['transaction_receipt_number' => $post['transaction_receipt_number']])->first();
            if($trx) {
                $id_transaction = $trx->id_transaction;
            } else {
                return MyHelper::checkGet([]);
            }
        } else {
            $id_transaction = $post['id_transaction'];
        }

        $detail = Transaction::where('transaction_from', 'outlet-service')
            ->join('transaction_outlet_services','transactions.id_transaction', 'transaction_outlet_services.id_transaction')
            ->where('transactions.id_transaction', $id_transaction)
            ->orderBy('transaction_date', 'desc')
            ->select('transactions.*', 'transaction_outlet_services.*', 'transactions.reject_at')
            ->with(
                'outlet.brands', 
                'transaction_outlet_service', 
                'transaction_products.transaction_product_service.user_hair_stylist',
                'transaction_products.product.photos',
                'user_feedbacks'
            )
            ->first();
        
        $outletZone = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
            ->join('provinces', 'provinces.id_province', 'cities.id_province')
            ->where('id_outlet', $detail['id_outlet'])
            ->select('outlets.*', 'cities.city_name', 'provinces.time_zone_utc as province_time_zone_utc')->first();
            
            
        if (!$detail) {
            return [
                'status' => 'fail',
                'messages' => ['Transaction not found']
            ];
        }
        $user = User::where('id',$detail['id_user'])->first();
            
        $trxPromo = app($this->transaction)->transactionPromo($detail);

        $outlet = [
            'id_outlet' => $detail['outlet']['id_outlet'],
            'outlet_code' => $detail['outlet']['outlet_code'],
            'outlet_name' => $detail['outlet']['outlet_name'],
            'outlet_address' => $detail['outlet']['outlet_address'],
            'outlet_latitude' => $detail['outlet']['outlet_latitude'],
            'outlet_longitude' => $detail['outlet']['outlet_longitude']
        ];

        $brand = [
            'id_brand' => $detail['outlet']['brands'][0]['id_brand'],
            'brand_code' => $detail['outlet']['brands'][0]['code_brand'],
            'brand_name' => $detail['outlet']['brands'][0]['name_brand'],
            'brand_logo' => $detail['outlet']['brands'][0]['logo_brand'],
            'brand_logo_landscape' => $detail['outlet']['brands'][0]['logo_landscape_brand']
        ];

        $products = [];
        $prod_services = [];
        $services = [];
        $queue = null;
        $subtotalProduct = 0;
        $subtotalService = 0;
        foreach ($detail['transaction_products'] as $product) {
            $show_rate_popup = 0;
            if ($product['type'] == 'Service') {
                if(isset($prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']])){
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['qty'] += 1;
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['total_all_service'] += $product['transaction_product_subtotal'];
                    $queue = $product['transaction_product_service']['queue'];

                }else{
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']] = $product;
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['qty'] = 1;
                    $prod_services[$product['id_product'].'_'.$product['transaction_product_service']['schedule_date']]['total_all_service'] = $product['transaction_product_subtotal'];
                    $queue = $product['transaction_product_service']['queue'];

                    
                }

            } else {
                $productPhoto = config('url.storage_url_api') . ($product['product']['photos'][0]['product_photo'] ?? 'img/product/item/default.png');
                $products[] = [
                    'product_name' => $product['product']['product_name'],
                    'transaction_product_qty' => $product['transaction_product_qty'],
                    'transaction_product_price' => $product['transaction_product_price'],
                    'transaction_product_subtotal' => $product['transaction_product_subtotal'],
                    'photo' => $productPhoto
                ];
                $subtotalProduct += abs($product['transaction_product_subtotal']);
            }
        }
        
        foreach($prod_services ?? [] as $prod_ser){
            if ($prod_ser['transaction_product_service']['completed_at']) {
                    $logRating = UserRatingLog::where([
                        'id_user' => $user->id,
                        'id_transaction' => $detail['id_transaction'],
                        'id_user_hair_stylist' => $prod_ser['transaction_product_service']['id_user_hair_stylist']
                    ])->first();

                    if ($logRating) {
                        $show_rate_popup = 1;
                    }
                }

                $timeZone = $outletZone['province_time_zone_utc'] - 7;
                $time = date('H:i', strtotime('+'.$timeZone.' hours', strtotime($prod_ser['transaction_product_service']['schedule_time'])));

                $services[] = [
                    'schedule_date' => MyHelper::dateFormatInd($prod_ser['transaction_product_service']['schedule_date'], true, false),
                    'qty' => $prod_ser['qty'],
                    'product_name' => $prod_ser['product']['product_name'],
                    'subtotal' => $prod_ser['transaction_product_subtotal'],
                    'total_all_service' => $prod_ser['total_all_service'],
                    'show_rate_popup' => $show_rate_popup
                ];

                $subtotalService += abs($product['transaction_product_subtotal']);
        }

        $cancelReason = null;
        if ($detail['transaction_payment_status'] == 'Pending') {
            $status = 'unpaid';
        } elseif ($detail['transaction_payment_status'] == 'Cancelled') {
            $status = 'cancelled';
            $cancelReason = 'Pembayaran gagal';
        } elseif (empty($detail['completed_at']) && $detail['transaction_payment_status'] == 'Completed') {
            $status = 'ongoing';
        } else {
            $status = 'completed';
        }

        if ($detail['reject_at']) {
            $status = 'cancelled';
            $cancelReason = $detail['reject_reason'];
        }

        $paymentDetail = [];
        
        $paymentDetail[] = [
            'name'          => 'Total',
            "is_discount"   => 0,
            'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'],'_CURRENCY')
        ];

        if (!empty($detail['transaction_tax'])) {
            $paymentDetail[] = [
                'name'          => 'Base Price',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber($detail['transaction_subtotal'] - $detail['transaction_tax'],'_CURRENCY')
            ];
            $paymentDetail[] = [
                'name'          => 'Tax',
                "is_discount"   => 0,
                'amount'        => MyHelper::requestNumber(round($detail['transaction_tax']),'_CURRENCY')
            ];
        }

        if($paymentDetail && isset($trxPromo)){
            $lastKey = array_key_last($paymentDetail);
            for($i = 0; $i < count($trxPromo); $i++){
                $KeyPosition = 1 + $i;
                $paymentDetail[$lastKey+$KeyPosition] = $trxPromo[$i];
            }
        }

        $show_rate_popup = 0;
        $logRating = UserRatingLog::where([
            'id_user' => $user->id,
            'id_transaction' => $detail['id_transaction']
        ])->first();

        if ($logRating) {
            $show_rate_popup = 1;
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

        $paymentCashCode = null;
        if($queue){
            if($queue<10){
                $queue_code = '00'.$queue;
            }elseif($queue<100){
                $queue_code = '0'.$queue;
            }else{
                $queue_code = $queue;
            }
        }else{
            $queue_code = null;
        }
        
        $currents = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                ->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
                ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                ->join('products', 'transaction_products.id_product', 'products.id_product')
                ->where(function($q) {
                    $q->where('service_status','In Progress');
                })
                ->where(function($q){
                    $q->whereNotNull('transaction_product_services.id_user_hair_stylist');
                })
                ->where(function($q) {
                    $q->where('trasaction_payment_type', 'Cash')
                    ->orWhere('transaction_payment_status', 'Completed');
                })
                ->where('transactions.id_outlet',$outlet['id_outlet'])
                ->whereNotNull('transaction_product_services.queue')
                ->whereNotNull('transaction_product_services.queue_code')
                ->whereDate('schedule_date',date('Y-m-d'))
                ->where('transaction_payment_status', '!=', 'Cancelled')
                ->wherenull('transaction_products.reject_at')
                ->where('transactions.id_transaction', '<>', $detail['id_transaction'])
                ->orderBy('queue', 'asc')
                ->select('transactions.id_transaction','transaction_product_services.id_transaction_product_service','transaction_product_services.queue_code', 'transaction_product_services.queue')
                ->get()->toArray();
        
        $res_cs = [];
        if($currents){
            foreach($currents ?? [] as $key => $current){
                if($current['queue']<10){
                    $res_cs[] = '00'.$current['queue'];
                }elseif($current['queue']<100){
                    $res_cs[] = '0'.$current['queue'];
                }else{
                    $res_cs[] = $current['queue'];
                }
            }
        }else{
            $res_cs = [];
        }
        $res = [
            'id_transaction' => $detail['id_transaction'],
            'transaction_receipt_number' => $detail['transaction_receipt_number'],
            'queue' => $queue_code,
            'current_service' => $res_cs,
            'qrcode' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $detail['transaction_receipt_number']) . '&margin=0&size=250',
            'transaction_date' => $detail['transaction_date'],
            'transaction_date_indo' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($detail['transaction_date'])), 'j F Y'),
            'transaction_subtotal' => $detail['transaction_subtotal'],
            'transaction_grandtotal' => $detail['transaction_grandtotal'],
            'transaction_tax' => $detail['transaction_tax'],
            'transaction_product_subtotal' => $subtotalProduct,
            'transaction_service_subtotal' => $subtotalService,
            'customer_name' => $user['is_anon'] == 0 ? (isset($user['name']) ? $detail['transaction_outlet_service']['customer_name'] : ('Customer '.$queue_code)) : ('Customer '.$queue_code),
            'color' => $detail['outlet']['brands'][0]['color_brand'],
            'status' => $status,
            'cancel_reason' => $cancelReason,
            'transaction_payment_status' => $detail['transaction_payment_status'],
            'payment_method' => $paymentMethod,
            'payment_cash_code' => $paymentCashCode,
            'show_rate_popup' => $show_rate_popup,
            'outlet' => $outlet,
            'brand' => $brand,
            'service' => $services,
            'product' => $products,
            'payment_detail' => $paymentDetail,
            'payment_method' => $paymentMethodDetail
        ];
        
        return MyHelper::checkGet($res);
    }

    public function listTransaction(Request $request){

        $post = $request->json()->all();
        $outlet = $this->getOutlet($post['outlet_code']??null);
        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
        ->where('id_outlet', $outlet['id_outlet'])->first();

        if(!$outlet){
            return [
    			'status' => 'fail',
    			'title' => 'Outlet Code Salah',
    			'messages' => ['Tidak dapat mendapat data outlet.']
    		];
        } 

        $services = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
            ->join('transaction_outlet_services', 'transaction_product_services.id_transaction','transaction_outlet_services.id_transaction')
            ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->join('products', 'transaction_products.id_product', 'products.id_product')
            ->join('users', 'transactions.id_user', 'users.id')
            ->where(function($q) {
                $q->whereNull('service_status');
                $q->orWhere('service_status','In Progress');
            })
            ->where(function($q){
                $q->whereNull('transaction_product_services.id_user_hair_stylist');
                $q->orWhereNotNull('transaction_product_services.id_user_hair_stylist');
            })
            ->where(function($q) {
                $q->where('trasaction_payment_type', 'Cash')
                ->orWhere('transaction_payment_status', 'Completed');
            })
            ->where('transactions.id_outlet',$outlet['id_outlet'])
            ->whereNotNull('transaction_product_services.queue')
            ->whereNotNull('transaction_product_services.queue_code')
            ->whereDate('schedule_date',date('Y-m-d'))
            ->where('transaction_payment_status', '!=', 'Cancelled')
            ->wherenull('transaction_products.reject_at')
            ->orderBy('queue', 'asc')
            ->select('transactions.id_transaction','transactions.transaction_receipt_number','transaction_product_services.id_transaction_product_service','transaction_product_services.schedule_date','transaction_product_services.queue','transaction_product_services.queue_code','transaction_product_services.service_status','products.product_name','users.name','users.is_anon')
            ->paginate(10)->toArray();
        
        $data = [];
        foreach($services['data'] ?? [] as $val){

            $queue = null;
            if(isset($val['queue'])){
                if($val['queue']<10){
                    $queue = '00'.$val['queue'];
                }elseif($val['queue']<100){
                    $queue = '0'.$val['queue'];
                }else{
                    $queue = $val['queue'];
                }
            }
            
            $data[] = [
                'id_transaction' => $val['id_transaction'],
                'id_transaction_product_service' => $val['id_transaction_product_service'],
                'transaction_receipt_number' => $val['transaction_receipt_number'],
                'qrcode' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $val['transaction_receipt_number']) . '&margin=0&size=250',
                'queue' => $queue,
                'queue_code' => $val['queue_code'],
                'transaction_date' => $val['schedule_date'],
                'status' => isset($val['service_status']) ? 'Sedang Berlangsung' : 'Menunggu',
                'transaction_date_indo' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($val['schedule_date'])), 'j F Y'),
                'product' => $val['product_name'],
                'customer_name' => $val['is_anon'] == 0 ? ($val['name'] ?? ('Customer '.$queue)) : ('Customer '.$queue)
            ];

        }

        $services['data'] = $data;
        return MyHelper::checkGet($services);
    }

    public function availablePayment(Request $request)
    {
        $post = $request->json()->all();

        $availablePayment = config('payment_method');
        
        $setting  = json_decode(MyHelper::setting('active_payment_methods', 'value_text', '[]'), true) ?? [];
        $payments = [];
        
        $config = [
            'credit_card_payment_gateway' => MyHelper::setting('credit_card_payment_gateway', 'value', 'Ipay88'),
            'platform' => 'webapps'
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

            if (!($payment['status'] ?? false) || (!$post['show_all'] && !($value['status'] ?? false))) {
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

            if((int) $value['status'] == 0){
                continue;
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

            if($status == 0){
                continue;
            }
            
            if($post['show_all'] || $status) {
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

    public function listTransactionV2(Request $request){

        $post = $request->json()->all();
        $outlet = $this->getOutlet($post['outlet_code']??null);
        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
        ->where('id_outlet', $outlet['id_outlet'])->first();

        if(!$outlet){
            return [
    			'status' => 'fail',
    			'title' => 'Outlet Code Salah',
    			'messages' => ['Tidak dapat mendapat data outlet.']
    		];
        } 

        $services = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
            ->join('transaction_outlet_services', 'transaction_product_services.id_transaction','transaction_outlet_services.id_transaction')
            ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
            ->join('products', 'transaction_products.id_product', 'products.id_product')
            ->join('users', 'transactions.id_user', 'users.id')
            ->where(function($q) {
                $q->whereNull('service_status');
                $q->orWhere('service_status','In Progress');
            })
            ->where(function($q){
                $q->whereNull('transaction_product_services.id_user_hair_stylist');
                $q->orWhereNotNull('transaction_product_services.id_user_hair_stylist');
            })
            ->where(function($q) {
                $q->where('trasaction_payment_type', 'Cash')
                ->orWhere('transaction_payment_status', 'Completed');
            })
            ->where('transactions.id_outlet',$outlet['id_outlet'])
            ->whereNotNull('transaction_product_services.queue')
            ->whereNotNull('transaction_product_services.queue_code')
            ->whereDate('schedule_date',date('Y-m-d'))
            ->where('transaction_payment_status', '!=', 'Cancelled')
            ->wherenull('transaction_products.reject_at')
            ->orderBy('queue', 'asc')
            ->select('transactions.id_transaction','transactions.transaction_receipt_number','transaction_product_services.id_transaction_product_service','transaction_product_services.schedule_date','transaction_product_services.queue','transaction_product_services.queue_code','transaction_product_services.service_status','products.product_name','users.name','users.is_anon')
            ->get()->toArray();
        
        $data = [];
        foreach($services ?? [] as $val){

            $queue = null;
            if(isset($val['queue'])){
                if($val['queue']<10){
                    $queue = '00'.$val['queue'];
                }elseif($val['queue']<100){
                    $queue = '0'.$val['queue'];
                }else{
                    $queue = $val['queue'];
                }
            }
            
            $data[] = [
                'id_transaction' => $val['id_transaction'],
                'id_transaction_product_service' => $val['id_transaction_product_service'],
                'transaction_receipt_number' => $val['transaction_receipt_number'],
                'qrcode' => 'https://quickchart.io/qr?text=' . str_replace('#', '', $val['transaction_receipt_number']) . '&margin=0&size=250',
                'queue' => $queue,
                'queue_code' => $val['queue_code'],
                'transaction_date' => $val['schedule_date'],
                'status' => isset($val['service_status']) ? 'Sedang Berlangsung' : 'Menunggu',
                'transaction_date_indo' => MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($val['schedule_date'])), 'j F Y'),
                'product' => $val['product_name'],
                'customer_name' => $val['is_anon'] == 0 ? ($val['name'] ?? ('Customer '.$queue)) : ('Customer '.$queue)
            ];

        }

        return response()->json([
            'status' => 'success',
            'result' => $data,
        ]);
    }
}
