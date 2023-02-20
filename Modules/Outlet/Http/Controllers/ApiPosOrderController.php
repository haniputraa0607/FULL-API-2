<?php

namespace Modules\Outlet\Http\Controllers;

use App\Jobs\SyncronPlasticTypeOutlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

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
use Modules\Outlet\Entities\OutletScheduleUpdate;

use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistAttendance;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionProductService;
use Modules\Transaction\Entities\TransactionProductServiceLog;
use App\Http\Models\TransactionSetting;

use App\Imports\ExcelImport;
use App\Imports\FirstSheetOnlyImport;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;

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

class ApiPosOrderController extends Controller
{

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
            ->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
            ->first();

            if(!empty($currentService)){
                $availableStatus = false;
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
                'shift_time' => date('H:i', strtotime($shift['time_start'])).' - '.date('H:i', strtotime($shift['time_end'])),
                'photo' => (empty($val['user_hair_stylist_photo']) ? config('url.storage_url_api').'img/product/item/default.png':$val['user_hair_stylist_photo']),
                'available_status' => $availableStatus,
                'current_service' => $current_service,
                'end_shift' => $until,
                'order' => ($availableStatus ? $val['id_user_hair_stylist']:1000)
            ];

            $queue = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
                ->join('transaction_outlet_services', 'transaction_product_services.id_transaction', 'transaction_outlet_services.id_transaction')
                ->join('transaction_products', 'transaction_product_services.id_transaction_product', 'transaction_products.id_transaction_product')
                ->join('products', 'transaction_products.id_product', 'products.id_product')
                ->where(function($q) {
                    $q->whereNull('service_status');
                })
                ->where(function($q){
                    $q->whereNull('transaction_product_services.id_user_hair_stylist');
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
                ->select('transactions.id_transaction','transaction_product_services.id_transaction_product_service','transaction_product_services.queue_code')
                ->get()->toArray();

            $current = TransactionProductService::join('transactions', 'transaction_product_services.id_transaction', 'transactions.id_transaction')
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
                ->orderBy('queue', 'asc')
                ->select('transactions.id_transaction','transaction_product_services.id_transaction_product_service','transaction_product_services.queue_code')
                ->get()->toArray();
            
            $data = [
                'outlet' => $outlet,
                'product_services' => $resProdService,
                'products' => $resProducts,
                'available_hs' => $res,
                'current_cust' => $current,
                'waiting' => $queue
            ];
        }
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

        if(empty($post['item_service'])){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Item/Item Service can not be empty']
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
            $result['customer'] = [];
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
        
        if($result['customer']){
            $result['points'] = (int) $balance??0;
            $result = app($this->promo_trx)->applyPromoCheckoutV2($result,$post, $user??[]);
        }else{
            $result['points'] = 0;
            $result['promo_deals'] = [
                'is_error' 			=> false,
                'can_use_deal'   	=> 1,
                'use_deal_message'	=> null,
            ];
            $result['promo_code'] = [
                'is_error' 			=> false,
                'can_use_promo'   	=> 1,
                'use_promo_message'	=> null,
            ];
        }

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
}
