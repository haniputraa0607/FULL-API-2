<?php

namespace Modules\Academy\Http\Controllers;

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

use App\Imports\ExcelImport;
use App\Imports\FirstSheetOnlyImport;

use App\Lib\MyHelper;
use Modules\Transaction\Entities\TransactionAcademy;
use Modules\Transaction\Entities\TransactionAcademyInstallment;
use Modules\Transaction\Entities\TransactionAcademySchedule;
use Modules\Transaction\Entities\TransactionAcademyScheduleDayOff;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;

use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\Brand;

use Modules\Outlet\Http\Requests\Outlet\Upload;

use Modules\Outlet\Http\Requests\Holiday\HolidayStore;
use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use Modules\Outlet\Http\Requests\Holiday\HolidayUpdate;
use Modules\Outlet\Http\Requests\Holiday\HolidayDelete;

use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Http\Models\Transaction;

use App\Jobs\SendOutletJob;
use function Clue\StreamFilter\fun;

class ApiAcademyController extends Controller
{
    public $saveImage = "img/outlet/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function settingInstallment(){
        $data = (array)json_decode(Setting::where('key', 'setting_academy_installment')->first()['value_text']??'');
        return response()->json(MyHelper::checkGet($data));
    }

    public function settingInstallmentSave(Request $request){
        $post = $request->json()->all();
        $save = Setting::updateOrCreate(['key' => 'setting_academy_installment'], ['value_text' => json_encode(array_values($post['data']))]);
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function settingBanner(){
        $banner = Setting::where('key', 'setting_academy_banner')->first();
        if(!empty($banner['value'])){
            $banner['value'] = config('url.storage_url_api').$banner['value'];
        }
        return response()->json(MyHelper::checkGet($banner));
    }

    public function settingBannerSave(Request $request){
        $post = $request->json()->all();
        if(!empty($post['value'])){
            $upload = MyHelper::uploadPhotoStrict($post['value'], 'img/academy', 720, 360);

            if (isset($upload['status']) && $upload['status'] == "success") {
                $post['value'] = $upload['path'];
            }else {
                return response()->json(['status'   => 'fail', 'messages' => ['Failed to upload image']]);
            }
        }
        $save = Setting::updateOrCreate(['key' => 'setting_academy_banner'], ['value' => $post['value']]);
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function getListNearOutlet(Request $request){
        $post = $request->json()->all();
        if(empty($post['latitude']) && empty($post['longitude'])){
            return response()->json(['status' => 'fail', 'messages' => ['Latitude and Longitude can not be empty']]);
        }
        $totalListOutlet = Setting::where('key', 'total_list_nearby_outlet')->first()['value']??5;
        $outletHomeService = Setting::where('key', 'default_outlet_home_service')->first()['value']??null;

        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];

        $outlet = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
            ->selectRaw('cities.city_name, outlets.id_outlet, outlets.outlet_name, outlets.outlet_code,
                    outlets.outlet_latitude, outlets.outlet_longitude, outlets.outlet_address, 
                    outlets.outlet_description, outlets.outlet_image,
                    (111.111 * DEGREES(ACOS(LEAST(1.0, COS(RADIANS(outlets.outlet_latitude))
                         * COS(RADIANS('.$post['latitude'].'))
                         * COS(RADIANS(outlets.outlet_longitude - '.$post['longitude'].'))
                         + SIN(RADIANS(outlets.outlet_latitude))
                         * SIN(RADIANS('.$post['latitude'].')))))) AS distance_in_km' )
            ->where('outlets.outlet_academy_status', 1)
            ->where('outlets.outlet_status', 'Active')
            ->whereNotNull('outlets.outlet_latitude')
            ->whereNotNull('outlets.outlet_longitude')
            ->whereHas('brands',function($query){
                $query->where('brands.brand_active',1)->where('brands.brand_visibility',1);
            })
            ->with(['brands', 'holidays.date_holidays', 'today'])
            ->orderBy('distance_in_km', 'asc')
            ->limit($totalListOutlet);

        if(!empty($outletHomeService)){
            $outlet = $outlet->whereNotIn('id_outlet', [$outletHomeService]);
        }

        $outlet = $outlet->get()->toArray();
        $currentDate = date('Y-m-d');
        $currentHour = date('H:i:s');
        $res = [];
        foreach ($outlet as $val){
            $isClose = false;
            $open = date('H:i:s', strtotime($val['today']['open']));
            $close = date('H:i:s', strtotime($val['today']['close']));
            foreach ($val['holidays'] as $holidays){
                $dates = array_column($holidays['date_holidays'], 'date');
                if(array_search($currentDate, $dates) !== false){
                    $isClose = true;
                    break;
                }
            }

            if(strtotime($currentHour) < strtotime($open) || strtotime($currentHour) > strtotime($close) || $val['today']['is_closed'] == 1){
                $isClose = true;
            }

            $brand = [];
            $colorBrand = "";
            if(!empty($val['brands'])){
                $brand = [
                    'id_brand' => $val['brands'][0]['id_brand'],
                    'brand_code' => $val['brands'][0]['code_brand'],
                    'brand_name' => $val['brands'][0]['name_brand'],
                    'brand_logo' => $val['brands'][0]['logo_brand'],
                    'brand_logo_landscape' => $val['brands'][0]['logo_landscape_brand']
                ];
                $colorBrand = $val['brands'][0]['color_brand'];
            }

            if($val['distance_in_km'] < 1){
                $distance = number_format($val['distance_in_km']*1000, 0, '.', '').' m';
            }else{
                $distance = number_format($val['distance_in_km'], 2, '.', '').' km';
            }
            $res[] = [
                'is_close' => $isClose,
                'id_outlet' => $val['id_outlet'],
                'outlet_code' => $val['outlet_code'],
                'outlet_name' => $val['outlet_name'],
                'outlet_latitude' => $val['outlet_latitude'],
                'outlet_longitude' => $val['outlet_longitude'],
                'outlet_description' => (empty($val['outlet_description']) ? '':$val['outlet_description']),
                'outlet_image' => $val['outlet_image'],
                'outlet_address' => $val['outlet_address'],
                'distance' => $distance,
                'color' => $colorBrand,
                'city_name' => $val['city_name'],
                'brand' => $brand
            ];
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function detailOutlet(Request $request){
        $post = $request->json()->all();
        if(empty($post['id_outlet'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID outlet can not be empty']]);
        }

        $detail = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
                    ->where('outlets.outlet_academy_status', 1)
                    ->where('outlets.outlet_status', 'Active')
                    ->where('id_outlet', $post['id_outlet'])
                    ->with(['outlet_schedules','brands'])
                    ->select('outlets.*', 'cities.city_name')
                    ->first();

        if(empty($detail)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
        }

        if(empty($detail['outlet_schedules'])){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet do not have schedules']]);
        }
        $detail = $detail->toArray();
        //schedule
        $allDay = array_column($detail['outlet_schedules'], 'day');
        $allTimeOpen = array_unique(array_column($detail['outlet_schedules'], 'open'));
        $allTimeClose = array_unique(array_column($detail['outlet_schedules'], 'close'));

        $arrSchedule = [];
        if(count($allDay) == 7 && count($allTimeOpen) == 1 && count($allTimeClose) == 1){
            $arrSchedule[] = [
                'day' => 'Buka Setiap Hari',
                'time' => date('H:i', strtotime($allTimeOpen[0])).' - '.date('H:i', strtotime($allTimeClose[0]))
            ];
        }else{
            foreach ($detail['outlet_schedules'] as $val){
                $arrSchedule[] = [
                    'day' => $val['day'],
                    'time' => date('H:i', strtotime($val['open'])).' - '.date('H:i', strtotime($val['close']))
                ];
            }
        }

        $res = [
            'id_outlet' => $detail['id_outlet'],
            'outlet_code' => $detail['outlet_code'],
            'outlet_name' => $detail['outlet_name'],
            'outlet_description' => (empty($detail['outlet_description']) ? "":$detail['outlet_description']),
            'outlet_image' => (empty($detail['outlet_image']) ? $detail['brands'][0]['image_brand']??'':$detail['outlet_image']),
            'outlet_address' => (empty($detail['outlet_address']) ? '':$detail['outlet_address']),
            'outlet_phone' => (empty($detail['outlet_phone']) ? '':$detail['outlet_phone']),
            'outlet_email' => (empty($detail['outlet_email']) ? '':$detail['outlet_email']),
            'city_name' => $detail['city_name'],
            'color' => (empty($detail['brands'][0]['color_brand']) ? '':$detail['brands'][0]['color_brand']),
            'brand_logo' =>  (empty($detail['brands'][0]['color_brand']) ? '':$detail['brands'][0]['logo_brand']),
            'brand_logo_landscape' => (empty($detail['brands'][0]['logo_landscape_brand']) ? '':$detail['brands'][0]['logo_landscape_brand']),
            'schedules' => $arrSchedule
        ];

        return response()->json(MyHelper::checkGet($res));
    }

    public function academyBanner(){
        $bannerImage = Setting::where('key', 'setting_academy_banner')->first()['value']??"";
        $res = [
            'banner_image' => (!empty($bannerImage) ? config('url.storage_url_api').$bannerImage:""),
            'title' => 'Cari skill barbership yang cocok untuk kamu'
        ];

        return response()->json(MyHelper::checkGet($res));
    }

    public function academyListProduct(Request $request){
        $post = $request->json()->all();
        $brand = [];
        $outlet = [];
        if(empty($post['id_outlet'])){
            $products = Product::join('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
                ->where('product_type', 'academy')
                ->where('product_visibility', 'Visible')
                ->where('product_global_price', '>', 0)
                ->with(['photos'])
                ->orderBy('products.position', 'asc')
                ->select('products.*', 'product_global_price as product_price')
                ->get()->toArray();
        }else{
            $getOutlet = Outlet::where('id_outlet', $post['id_outlet'])->first();;
            if (!$getOutlet) {
                return [
                    'status' => 'fail',
                    'messages' => ['Outlet not found']
                ];
            }

            $getBrand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                ->where('id_outlet', $getOutlet['id_outlet'])->first();

            if(empty($getBrand)){
                return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
            }

            $products = Product::select(['products.*',
                    DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $getOutlet['id_outlet'] . ' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $getOutlet['id_outlet'] . ' )
                            ELSE product_global_price.product_global_price
                        END) as product_price')
                ])
                ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
                ->where('product_type', 'academy')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $getOutlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NULL AND products.product_visibility = "Visible" THEN products.id_product
                        WHEN (select product_detail.id_product from product_detail  where (product_detail.product_detail_visibility = "" OR product_detail.product_detail_visibility is NULL) AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $getOutlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                        is NOT NULL AND products.product_visibility = "Visible" THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_visibility = "Visible" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $getOutlet['id_outlet'] . '  order by id_product_detail desc limit 1)
                    END)')
                ->whereRaw('products.id_product in (CASE
                        WHEN (select product_detail.id_product from product_detail  where product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $getOutlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                        is NULL THEN products.id_product
                        ELSE (select product_detail.id_product from product_detail  where product_detail.product_detail_status = "Active" AND product_detail.id_product = products.id_product AND product_detail.id_outlet = ' . $getOutlet['id_outlet'] . ' order by id_product_detail desc limit 1)
                    END)')
                ->where(function ($query) use ($getOutlet) {
                    $query->WhereRaw('(select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $getOutlet['id_outlet'] . '  order by id_product_special_price desc limit 1) is NOT NULL');
                    $query->orWhereRaw('(select product_global_price.product_global_price from product_global_price  where product_global_price.id_product = products.id_product order by id_product_global_price desc limit 1) is NOT NULL');
                })
                ->with(['photos'])
                ->having('product_price', '>', 0)
                ->groupBy('products.id_product')
                ->orderByRaw('CASE WHEN products.position = 0 THEN 1 ELSE 0 END')
                ->orderBy('products.position')
                ->orderBy('products.id_product')
                ->get()->toArray();

            if(!empty($post['latitude']) && !empty($post['longitude'])){
                $distance = (float)app('Modules\Outlet\Http\Controllers\ApiOutletController')->distance($post['latitude'], $post['longitude'], $getOutlet['outlet_latitude'], $getOutlet['outlet_longitude'], "K");
                if($distance < 1){
                    $distance = number_format($distance*1000, 0, '.', '').' m';
                }else{
                    $distance = number_format($distance, 2, '.', '').' km';
                }
            }

            $outlet = [
                'id_outlet' => $getOutlet['id_outlet'],
                'outlet_code' => $getOutlet['outlet_code'],
                'outlet_name' => $getOutlet['outlet_name'],
                'outlet_image' => $getOutlet['outlet_image'],
                'outlet_address' => $getOutlet['outlet_address'],
                'distance' => $distance??'',
                'color' => $getBrand['color_brand']??''
            ];

            $brand = [
                'id_brand' => $getBrand['id_brand'],
                'brand_code' => $getBrand['code_brand'],
                'brand_name' => $getBrand['name_brand'],
                'brand_logo' => $getBrand['logo_brand'],
                'brand_logo_landscape' => $getBrand['logo_landscape_brand']
            ];
        }

        $resProd = [];
        foreach ($products as $product){
            $resProd[] = [
                'id_product' => $product['id_product'],
                'id_brand' => $getBrand['id_brand']??null,
                'product_code' => $product['product_code'],
                'product_name' => $product['product_name'],
                'product_short_description' => $product['product_short_description'],
                'product_price' => (int)$product['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$product['product_price'],0,",","."),
                'duration' => 'Durasi '.$product['product_academy_duration'].' bulan',
                'total_meeting' => (!empty($product['product_academy_total_meeting'])? $product['product_academy_total_meeting'].' x Pertemuan @'.$product['product_academy_hours_meeting'].' jam':''),
                'photo' => (empty($product['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$product['photos'][0]['product_photo'])
            ];
        }

        $res = [
            'brand' => (empty($brand) ? null : $brand),
            'outlet' => (empty($outlet) ? null : $outlet),
            'products' => $resProd
        ];

        return response()->json(MyHelper::checkGet($res));
    }

    public function academyDetailProduct(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_product'])){
            $detail = Product::join('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
                ->where('products.id_product', $post['id_product'])
                ->where('product_type', 'academy')
                ->where('product_visibility', 'Visible')
                ->where('product_global_price', '>', 0)
                ->with(['photos'])
                ->select('products.*', 'product_global_price as product_price')
                ->first();

            if(empty($detail)){
                return response()->json(['status' => 'fail', 'messages' => ['Product not found']]);
            }

            $outlet = [];
            $brand = [];
            if(!empty($post['id_outlet'])){
                $getOutlet = Outlet::where('id_outlet', $post['id_outlet'])->first();;
                if (!$getOutlet) {
                    return response()->json(['status' => 'fail', 'messages' => ['Outlet not found']]);
                }

                $getBrand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                    ->where('id_outlet', $getOutlet['id_outlet'])->first();

                if(empty($getBrand)){
                    return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
                }

                if($getOutlet['outlet_different_price'] == 1){
                    $detail['product_price'] = ProductSpecialPrice::where('id_product', $detail['id_product'])->where('id_outlet', $getOutlet['id_outlet'])->first()['product_special_price']??0;
                }

                $outlet = [
                    'id_outlet' => $getOutlet['id_outlet'],
                    'outlet_code' => $getOutlet['outlet_code'],
                    'outlet_name' => $getOutlet['outlet_name'],
                    'outlet_address' => $getOutlet['outlet_address'],
                    'outlet_description' => $getOutlet['outlet_description'],
                    'outlet_email' => $getOutlet['outlet_email'],
                    'outlet_phone' => $getOutlet['outlet_phone'],
                    'color' => $getBrand['color_brand']??''
                ];

                $brand = [
                    'id_brand' => $getBrand['id_brand'],
                    'brand_code' => $getBrand['code_brand'],
                    'brand_name' => $getBrand['name_brand'],
                    'brand_logo' => $getBrand['logo_brand'],
                    'brand_logo_landscape' => $getBrand['logo_landscape_brand']
                ];
            }

            $resDetail = [
                'complete_profile' => (empty($request->user()->complete_profile) ?false:true),
                'brand' => (empty($brand) ? null : $brand),
                'outlet' => (empty($outlet) ? null : $outlet),
                'detail' => [
                    'id_product' => $detail['id_product'],
                    'product_code' => $detail['product_code'],
                    'product_name' => $detail['product_name'],
                    'product_short_description' => $detail['product_short_description'],
                    'product_long_description' => $detail['product_description'],
                    'product_price' => (int)$detail['product_price'],
                    'string_product_price' => 'Rp '.number_format((int)$detail['product_price'],0,",","."),
                    'duration' => 'Durasi'.$detail['product_academy_duration'].' bulan',
                    'total_meeting' => $detail['product_academy_total_meeting'].' x Pertemuan @'.$detail['product_academy_hours_meeting'].' jam',
                    'photo' => (empty($detail['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$detail['photos'][0]['product_photo']),
                    'photo_detail' => (empty($detail['product_photo_detail']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$detail['product_photo_detail'])
                ]
            ];

            return response()->json(MyHelper::checkGet($resDetail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID product can not be empty']]);
        }
    }

    public function listMyCourse(Request $request){
        $post = $request->json()->all();
        $idUser = $request->user()->id;
        $userTimeZone = (empty($request->user()->user_time_zone_utc) ? 7 : $request->user()->user_time_zone_utc);
        $diffTimeZone = $userTimeZone - 7;
        $currentDate = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d H:i:s', strtotime("+".$diffTimeZone." hour", strtotime($currentDate)));

        $list = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
            ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->leftJoin('products', 'products.id_product', 'transaction_products.id_product')
            ->where('transactions.id_user', $idUser)
            ->groupBy('transactions.id_transaction')
            ->with('outlet');

        $post['status'] = $post['status']??'';

        if($post['status'] == 'on_going'){
            $idTransactionOnGoing = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                ->leftJoin('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                ->where('transactions.id_user', $idUser)
                ->where(function ($q) use($currentDate){
                    $q->where('transaction_academy_schedule_status', '>=', $currentDate)->orWhereNull('schedule_date');
                })->pluck('transaction_academy.id_transaction_academy')->toArray();

            $list = $list->whereIn('transaction_academy.id_transaction_academy', $idTransactionOnGoing);
        }elseif($post['status'] == 'completed'){
            $idTransactionOnGoing = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                ->leftJoin('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                ->where('transactions.id_user', $idUser)
                ->where(function ($q) use($currentDate){
                    $q->where('schedule_date', '>=', $currentDate)->orWhereNull('schedule_date');
                })->pluck('transaction_academy.id_transaction_academy')->toArray();

            $list = $list->whereNotIn('transaction_academy.id_transaction_academy', $idTransactionOnGoing);
        }

        $list = $list->get()->toArray();
        $res = [];
        foreach ($list as $value){
            $nextTimeStart = '';
            if($post['status'] == 'on_going'){
                $getNextTime = TransactionAcademySchedule::where('schedule_date', '>=', $currentDate)
                    ->where('id_transaction_academy', $value['id_transaction_academy'])
                    ->orderBy('schedule_date', 'asc')->first()['schedule_date']??'';
                if(!empty($getNextTime)){
                    $nextTimeStart = date('Y-m-d H:i:s', strtotime($getNextTime));
                    $nextTimeEnd = date('Y-m-d H:i:s', strtotime("+".(int)$value['transaction_academy_hours_meeting']." hour", strtotime($nextTimeStart)));
                }
            }

            $res[] = [
                'id_transaction' => $value['id_transaction'],
                'id_transaction_academy' => $value['id_transaction_academy'],
                'course_name' => $value['product_name'],
                'next_schedule_date_display' => (empty($nextTimeStart)? '' : MyHelper::dateFormatInd($nextTimeStart, true, false)),
                'next_schedule_date' => (empty($nextTimeStart)? '' : date('Y-m-d', strtotime($nextTimeStart))),
                'next_schedule_time' => (empty($nextTimeStart)? '' : date('H:i', strtotime($nextTimeStart)) .' - '. date('H:i', strtotime($nextTimeEnd))),
                'location' => $value['outlet']['outlet_name']
            ];
        }

        return response()->json(MyHelper::checkGet($res));
    }

    public function detailMyCourse(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction'])){
            $userTimeZone = (empty($request->user()->user_time_zone_utc) ? 7 : $request->user()->user_time_zone_utc);
            $diffTimeZone = $userTimeZone - 7;
            $currentDate = date('Y-m-d H:i:s');
            $currentDate = date('Y-m-d H:i:s', strtotime("+".$diffTimeZone." hour", strtotime($currentDate)));

            $detail =  Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                        ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                        ->leftJoin('products', 'products.id_product', 'transaction_products.id_product')
                        ->where('transactions.id_transaction', $post['id_transaction'])->with('outlet')->first();

            if(!empty($detail)){
                $nextTimeStart = '';
                $getNextTime = TransactionAcademySchedule::where('schedule_date', '>=', $currentDate)
                        ->where('id_transaction_academy', $detail['id_transaction_academy'])
                        ->orderBy('schedule_date', 'asc')->first()['schedule_date']??'';
                if(!empty($getNextTime)){
                    $nextTimeStart = date('Y-m-d H:i:s', strtotime($getNextTime));
                    $nextTimeEnd = date('Y-m-d H:i:s', strtotime("+".(int)$detail['transaction_academy_hours_meeting']." hour", strtotime($nextTimeStart)));
                }

                $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                    ->where('id_outlet', $detail['id_outlet'])->first();

                if(empty($brand)){
                    return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
                }

                $detail = [
                    'id_transaction' => $detail['id_transaction'],
                    'id_transaction_academy' => $detail['id_transaction_academy'],
                    'course_name' => $detail['product_name'],
                    'next_schedule_date_display' => (empty($nextTimeStart)? '' : MyHelper::dateFormatInd($nextTimeStart, true, false)),
                    'next_schedule_date' => (empty($nextTimeStart)? '' : date('Y-m-d', strtotime($nextTimeStart))),
                    'next_schedule_time' => (empty($nextTimeStart)? '' : date('H:i', strtotime($nextTimeStart)) .' - '. date('H:i', strtotime($nextTimeEnd))),
                    'outlet' => [
                        'outlet_name' => $detail['outlet']['outlet_name'],
                        'outlet_address' => $detail['outlet']['outlet_address'],
                        'color' => $brand['color_brand']
                    ],
                    'brand' => [
                        'id_brand' => $brand['id_brand'],
                        'brand_code' => $brand['code_brand'],
                        'brand_name' => $brand['name_brand'],
                        'brand_logo' => $brand['logo_brand'],
                        'brand_logo_landscape' => $brand['logo_landscape_brand']
                    ]
                ];
            }

            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction can not be empty']]);
        }
    }

    public function scheduleDetailMyCourse(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction_academy'])){
            $totalCompleted = TransactionAcademySchedule::where('id_transaction_academy', $post['id_transaction_academy'])->where('transaction_academy_schedule_status', 'Attend')->count();
            $listSchedule = TransactionAcademySchedule::where('id_transaction_academy', $post['id_transaction_academy'])->paginate(10)->toArray();

            foreach ($listSchedule['data'] as $key=>$value){
                $listSchedule['data'][$key] = [
                    'meeting' => 'Pertemuan '.MyHelper::numberToRomanRepresentation($value['meeting']),
                    'status' => $value['transaction_academy_schedule_status'],
                    'date' => MyHelper::dateFormatInd($value['schedule_date'], true, false),
                    'time' => date('H:i', strtotime($value['schedule_date']))
                ];
            }

            $res = [
                'total_meeting' => count($listSchedule),
                'total_meeting_completed' => $totalCompleted,
                'list_meeting_date' => $listSchedule
            ];
            return response()->json(MyHelper::checkGet($res));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction academy can not be empty']]);
        }
    }

    public function scheduleMyCourse(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction_academy'])){
            $listSchedule = TransactionAcademySchedule::where('id_transaction_academy', $post['id_transaction_academy'])
                ->where('transaction_academy_schedule_status', 'Not Started')
                ->get()->toArray();

            $res = [];
            foreach ($listSchedule as $key=>$value){
                $res[] = [
                    'id_transaction_academy_schedule' => $value['id_transaction_academy_schedule'],
                    'meeting' => 'Pertemuan '.MyHelper::numberToRomanRepresentation($value['meeting']),
                    'date' => MyHelper::dateFormatInd($value['schedule_date'], true, false),
                    'time' => date('H:i', strtotime($value['schedule_date']))
                ];
            }

            return response()->json(MyHelper::checkGet($res));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction academy can not be empty']]);
        }
    }

    public function createDayOff(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction_academy_schedule'])){
            $check = TransactionAcademySchedule::where('id_transaction_academy_schedule', $post['id_transaction_academy_schedule'])->first();
            if(empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Schedule old not found']]);
            }
            $create = TransactionAcademyScheduleDayOff::create([
                            'id_transaction_academy_schedule' => $post['id_transaction_academy_schedule'],
                            'id_transaction_academy' => $check['id_transaction_academy'],
                            'schedule_date_old' => date('Y-m-d H:i:s', strtotime($check['schedule_date'])),
                            'schedule_date_new' => date('Y-m-d H:i:s', strtotime($post['schedule_date_new'])),
                            'description' => $post['description']
                        ]);
            return response()->json(MyHelper::checkCreate($create));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction academy schedule can not be empty']]);
        }
    }

    public function installmentDetail(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction'])){
            $trx = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                    ->where('transactions.id_transaction', $post['id_transaction'])->first();
            if(empty($trx)){
                return response()->json(['status' => 'fail', 'messages' => ['Transaction not found']]);
            }

            $listInstallment = TransactionAcademyInstallment::where('id_transaction_academy', $trx['id_transaction_academy'])->get()->toArray();
            $listNextBill = [];
            foreach ($listInstallment as $key=>$value){
                if(empty($value['completed_installment_at'])){
                    $listNextBill[] = [
                        'text' => 'Pembayaran Tahap '.($key+1),
                        'deadline' => (empty($value['deadline'])? '':MyHelper::dateFormatInd($value['deadline'], true, true)),
                        'amount' => $value['amount']
                    ];
                }
            }

            $nextBill = $listNextBill[0]??null;
            unset($listNextBill[0]);
            $listNextBill = array_values($listNextBill);
            $res = [
                'currency' => 'Rp',
                'total_amount' => $trx['transaction_grandtotal'],
                'amount_completed' => $trx['amount_completed'],
                'amount_not_completed' => $trx['amount_not_completed'],
                'next_bill' => $nextBill,
                'list_next_bill' => $listNextBill
            ];
            return response()->json(MyHelper::checkGet($res));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction can not be empty']]);
        }
    }
}
