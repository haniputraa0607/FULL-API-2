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

use App\Jobs\SendOutletJob;
use function Clue\StreamFilter\fun;

class ApiAcademyController extends Controller
{
    public $saveImage = "img/outlet/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function settingInstalment(){
        $data = (array)json_decode(Setting::where('key', 'setting_academy_instalment')->first()['value_text']??'');
        return response()->json(MyHelper::checkGet($data));
    }

    public function settingInstalmentSave(Request $request){
        $post = $request->json()->all();
        $save = Setting::updateOrCreate(['key' => 'setting_academy_instalment'], ['value_text' => json_encode(array_values($post['data']))]);
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
            ->whereNotIn('outlet_code', ['00000'])
            ->orderBy('distance_in_km', 'asc')
            ->limit($totalListOutlet)->get()->toArray();

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
                'product_code' => $product['product_code'],
                'product_name' => $product['product_name'],
                'product_short_description' => $product['product_short_description'],
                'product_price' => (int)$product['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$product['product_price'],0,",","."),
                'duration' => 'Durasi'.$product['product_academy_duration'].' bulan',
                'total_meeting' => $product['product_academy_total_meeting'].' x Pertemuan @'.$product['product_academy_hours_meeting'].' jam',
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
}
