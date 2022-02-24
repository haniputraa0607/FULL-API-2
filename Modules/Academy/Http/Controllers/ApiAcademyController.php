<?php

namespace Modules\Academy\Http\Controllers;

use App\Http\Models\TransactionProduct;
use App\Lib\Midtrans;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use Modules\Academy\Http\Requests\Pay;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductSpecialPrice;
use App\Lib\MyHelper;
use Modules\Transaction\Entities\TransactionAcademy;
use Modules\Transaction\Entities\TransactionAcademyInstallment;
use Modules\Transaction\Entities\TransactionAcademyInstallmentPaymentMidtrans;
use Modules\Transaction\Entities\TransactionAcademyInstallmentUpdate;
use Modules\Transaction\Entities\TransactionAcademySchedule;
use Modules\Transaction\Entities\TransactionAcademyScheduleDayOff;
use Modules\Xendit\Entities\TransactionAcademyInstallmentPaymentXendit;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;

use Modules\Brand\Entities\Brand;

use Modules\Outlet\Http\Requests\Outlet\Upload;

use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use App\Http\Models\Transaction;

class ApiAcademyController extends Controller
{
    public $saveImage = "img/outlet/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->online_trx      = "Modules\Transaction\Http\Controllers\ApiOnlineTransaction";
        $this->autocrm       = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
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

        if(!empty($post['id_product'])){
            $listOutletAvailable = ProductDetail::where('id_product', $post['id_product'])->where('product_detail_visibility', 'Hidden')->pluck('id_outlet')->toArray();
            $listOutletAvailable = array_unique($listOutletAvailable);
            if(!empty($listOutletAvailable)){
                $outlet = $outlet->whereNotIn('id_outlet', $listOutletAvailable);
            }
        }

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

        return response()->json(['status' => 'success', 'result' => $res]);
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

        $list = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
            ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
            ->leftJoin('brands', 'brands.id_brand', 'transaction_products.id_brand')
            ->leftJoin('products', 'products.id_product', 'transaction_products.id_product')
            ->where('transactions.id_user', $idUser)
            ->whereNotIn('transaction_payment_status', ['Cancelled'])
            ->groupBy('transactions.id_transaction')
            ->orderBy('transactions.id_transaction', 'desc')
            ->with('outlet');

        $post['status'] = $post['status']??'';

        if($post['status'] == 'ongoing'){
            $idTransactionOnGoing = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                ->leftJoin('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                ->where('transactions.id_user', $idUser)
                ->where(function ($q){
                    $q->where('transaction_academy_schedule_status', '=', 'Not Started')->orWhereNull('schedule_date');
                })->pluck('transaction_academy.id_transaction_academy')->toArray();
            $idTransactionOnGoing = array_unique($idTransactionOnGoing);
            $list = $list->whereIn('transaction_academy.id_transaction_academy', $idTransactionOnGoing);
        }elseif($post['status'] == 'completed'){
            $idTransactionOnGoing = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                ->leftJoin('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                ->where('transactions.id_user', $idUser)
                ->where(function ($q){
                    $q->where('transaction_academy_schedule_status', '=', 'Not Started')->orWhereNull('schedule_date');
                })->pluck('transaction_academy.id_transaction_academy')->toArray();
            $idTransactionOnGoing = array_unique($idTransactionOnGoing);

            $list = $list->whereNotIn('transaction_academy.id_transaction_academy', $idTransactionOnGoing);
        }

        $list = $list->get()->toArray();
        $res = [];
        foreach ($list as $value){
            $nextTimeStart = '';
            if($post['status'] == 'ongoing'){
                $getNextTime = TransactionAcademySchedule::where('id_transaction_academy', $value['id_transaction_academy'])
                        ->where('transaction_academy_schedule_status', 'Not Started')->orderBy('meeting', 'asc')->first()['schedule_date']??'';
                if(!empty($getNextTime)){
                    $nextTimeStart = date('Y-m-d H:i:s', strtotime($getNextTime));
                    $nextTimeEnd = date('Y-m-d H:i:s', strtotime("+".(int)$value['transaction_academy_hours_meeting']." hour", strtotime($nextTimeStart)));
                }
            }

            $res[] = [
                'id_transaction' => $value['id_transaction'],
                'id_transaction_academy' => $value['id_transaction_academy'],
                'receipt_number' => $value['transaction_receipt_number'],
                'course_name' => $value['product_name'],
                'next_schedule_date_display' => (empty($nextTimeStart)? '' : MyHelper::dateFormatInd($nextTimeStart, true, false)),
                'next_schedule_date' => (empty($nextTimeStart)? '' : date('Y-m-d', strtotime($nextTimeStart))),
                'next_schedule_time' => (empty($nextTimeStart)? '' : date('H:i', strtotime($nextTimeStart)) .' - '. date('H:i', strtotime($nextTimeEnd))),
                'location' => $value['outlet']['outlet_name'],
                'color' => $value['color_brand']??''
            ];
        }

        return response()->json(['status' => 'success', 'result' => $res]);
    }

    public function detailMyCourse(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction']) || !empty($post['transaction_receipt_number'])){

            $detail =  Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                        ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                        ->leftJoin('products', 'products.id_product', 'transaction_products.id_product')
                        ->with('outlet');

            if (!empty($post['transaction_receipt_number']) && substr_count($post['transaction_receipt_number'],"-") >= 2) {
                $trxReciptNumber = TransactionAcademyInstallment::join('transaction_academy', 'transaction_academy_installment.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                    ->where('installment_receipt_number', $post['transaction_receipt_number'])->first();
                $post['id_transaction'] = $trxReciptNumber['id_transaction'];
            }elseif (!empty($post['transaction_receipt_number'])) {
                $post['id_transaction'] = Transaction::where('transaction_receipt_number', $post['transaction_receipt_number'])->first()['id_transaction']??null;
            }

            $detail = $detail->where('transactions.id_transaction', $post['id_transaction']);

            $detail = $detail->first();
            if(!empty($detail)){
                $ongoingCheck = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                    ->leftJoin('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                    ->where('transaction_academy.id_transaction_academy', $detail['id_transaction_academy'])
                    ->where(function ($q){
                        $q->where('transaction_academy_schedule_status', '=', 'Not Started')->orWhereNull('schedule_date');
                    })->first();

                $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
                    ->where('id_outlet', $detail['id_outlet'])->first();

                if(empty($brand)){
                    return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
                }

                $status = 'completed';
                $nextTimeStart = '';
                if(!empty($ongoingCheck)){
                    $status = 'ongoing';
                    $getNextTime = TransactionAcademySchedule::where('id_transaction_academy', $detail['id_transaction_academy'])
                            ->where('transaction_academy_schedule_status', 'Not Started')->orderBy('meeting', 'asc')->first()['schedule_date']??'';
                    if(!empty($getNextTime)){
                        $nextTimeStart = date('Y-m-d H:i:s', strtotime($getNextTime));
                        $nextTimeEnd = date('Y-m-d H:i:s', strtotime("+".(int)$detail['transaction_academy_hours_meeting']." hour", strtotime($nextTimeStart)));
                    }
                }

                $detail = [
                    'id_transaction' => $detail['id_transaction'],
                    'id_transaction_academy' => $detail['id_transaction_academy'],
                    'course_name' => $detail['product_name'],
                    'status' => $status,
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

            $nextMeeting = TransactionAcademySchedule::where('id_transaction_academy', $post['id_transaction_academy'])
                        ->where('transaction_academy_schedule_status', 'Not Started')->orderBy('meeting', 'asc')->first()['meeting']??null;
            if(!isset($post['page'])){
                $listSchedule = TransactionAcademySchedule::where('id_transaction_academy', $post['id_transaction_academy'])->get()->toArray();

                foreach ($listSchedule as $key=>$value){
                    $listSchedule[$key] = [
                        'meeting' => 'Pertemuan '.MyHelper::numberToRomanRepresentation($value['meeting']),
                        'status' => ($nextMeeting == $value['meeting'] ? 'Next':$value['transaction_academy_schedule_status']),
                        'date' => MyHelper::dateFormatInd($value['schedule_date'], true, false),
                        'time' => date('H:i', strtotime($value['schedule_date']))
                    ];
                }
            }else{
                $listSchedule = TransactionAcademySchedule::where('id_transaction_academy', $post['id_transaction_academy'])->paginate(15)->toArray();

                foreach ($listSchedule['data'] as $key=>$value){
                    $listSchedule['data'][$key] = [
                        'meeting' => 'Pertemuan '.MyHelper::numberToRomanRepresentation($value['meeting']),
                        'status' => ($nextMeeting == $value['meeting'] ? 'Next':$value['transaction_academy_schedule_status']),
                        'date' => MyHelper::dateFormatInd($value['schedule_date'], true, false),
                        'time' => date('H:i', strtotime($value['schedule_date']))
                    ];
                }
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

            return response()->json(['status' => 'success', 'result' => $res]);
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
            $newDate = date('Y-m-d', strtotime($post['schedule_date_new']));
            $newTime = date('H:i:s', strtotime($check['schedule_date']));
            $create = TransactionAcademyScheduleDayOff::create([
                            'id_transaction_academy_schedule' => $post['id_transaction_academy_schedule'],
                            'id_transaction_academy' => $check['id_transaction_academy'],
                            'schedule_date_old' => date('Y-m-d H:i:s', strtotime($check['schedule_date'])),
                            'schedule_date_new' => date('Y-m-d H:i:s', strtotime($newDate.' '.$newTime)),
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

            $listInstallment = TransactionAcademyInstallment::where('id_transaction_academy', $trx['id_transaction_academy'])->orderBy('id_transaction_academy_installment', 'asc')->get()->toArray();
            $listNextBill = [];
            $listHistory = [];
            foreach ($listInstallment as $key=>$value){
                if(empty($value['completed_installment_at'])){
                    $listNextBill[] = [
                        'id_transaction_academy_installment' => $value['id_transaction_academy_installment'],
                        'text' => ($key == 0 ? 'Uang Muka':'Pembayaran Tahap '.($key)),
                        'deadline' => (empty($value['deadline'])? '':MyHelper::dateFormatInd($value['deadline'], true, false)),
                        'amount' => $value['amount']
                    ];
                }else{
                    $listHistory[] = [
                        'payment_date' => MyHelper::dateFormatInd($value['completed_installment_at'], true, false),
                        'receipt_number' => $value['installment_receipt_number'],
                        'title' => ($key == 0 ? 'Uang Muka':'Pembayaran Tahap '.($key)),
                        'amount' => number_format($value['amount'],0,",",".")
                    ];
                }
            }

            if($trx['trasaction_payment_type'] != 'Installment'){
                $listHistory[] = [
                    'payment_date' => MyHelper::dateFormatInd($trx['transaction_date'], true, false),
                    'receipt_number' => $trx['transaction_receipt_number'],
                    'title' => 'Total',
                    'amount' => number_format($trx['transaction_grandtotal'],0,",",".")
                ];
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
                'list_next_bill' => $listNextBill,
                'list_history_bill' => $listHistory
            ];

            $fake_request = new Request(['show_all' => 1]);
            $res['available_payment'] = app($this->online_trx)->availablePayment($fake_request)['result'] ?? [];
            return response()->json(MyHelper::checkGet($res));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction can not be empty']]);
        }
    }

    public function installmentPay(Pay $request){
        $post = $request->json()->all();
        $dataInstallment = TransactionAcademyInstallment::join('transaction_academy', 'transaction_academy.id_transaction_academy', 'transaction_academy_installment.id_transaction_academy')
                            ->where('id_transaction_academy_installment', $post['id_transaction_academy_installment'])
                            ->select('transaction_academy_installment.*', 'transaction_academy.id_transaction')->first();

        if(empty($dataInstallment)){
            return response()->json(['status' => 'fail', 'messages' => ['Data installment not found']]);
        }

        if($dataInstallment['paid_status'] == 'Completed'){
            return response()->json(['status' => 'fail', 'messages' => ['Installment already paid']]);
        }

        $expired   = date('Y-m-d H:i:s',strtotime('- 5minutes'));
        if($dataInstallment['paid_status'] == 'Pending' && strtotime($dataInstallment['updated_at']) > strtotime($expired)){
            return response()->json(['status' => 'fail', 'messages' => ['Installment still pending']]);
        }elseif ($dataInstallment['paid_status'] == 'Pending' && strtotime($dataInstallment['updated_at']) <= strtotime($expired)){
            if(strtolower($dataInstallment['installment_payment_type']) == 'midtrans'){
                Midtrans::expire($dataInstallment['installment_receipt_number']);
            }
            $dataInstallment->update(['paid_status' => 'Cancelled']);
        }

        if ($post['payment_type']) {
            $available_payment = app('Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->availablePayment(new Request())['result'] ?? [];
            if (!in_array($post['payment_type'], array_column($available_payment, 'payment_gateway'))) {
                return [
                    'status' => 'fail',
                    'messages' => 'Metode pembayaran yang dipilih tidak tersedia untuk saat ini'
                ];
            }
        }

        if($dataInstallment['paid_status'] == 'Cancelled'){
            $insertUpdate = TransactionAcademyInstallmentUpdate::create(['id_transaction_academy_installment' => $dataInstallment['id_transaction_academy_installment'], 'installment_receipt_number_old' => $dataInstallment['installment_receipt_number']]);
            if($insertUpdate){
                $trx = Transaction::join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')->where('id_transaction', $dataInstallment['id_transaction'])->first();
                $newReceiptNumber = 'TRX'.substr($trx['outlet_code'], -4).'-'.substr($trx['transaction_receipt_number'], -5).'-'.sprintf("%02d", $dataInstallment['installment_step']).'-'.time();
                $dataInstallment->update(['installment_receipt_number' => $newReceiptNumber]);
            }
        }

        $dataInstallment->update(['installment_payment_type' => $request->payment_type]);

        $res = [];
        if ($request->json('payment_type') && $request->json('payment_type') == "Midtrans") {
            $pay = $this->midtrans($dataInstallment, $post);

            if(!empty($pay)){
                $res = [
                    "snap_token" => $pay['midtrans']['token'],
                    "redirect_url" => $pay['midtrans']['redirect_url'],
                    "transaction_data" => [
                        "transaction_details" => [
                            "order_id" => $dataInstallment['installment_receipt_number'],
                            "gross_amount" => $dataInstallment['amount'],
                            "id_transaction_academy_installment" =>  $dataInstallment['id_transaction_academy_installment'],
                            "id_transaction" => $dataInstallment['id_transaction']
                        ]
                    ]
                ];
            }
        } elseif ($request->payment_type == 'Xendit') {
            $pay = $this->xendit($dataInstallment, $post);

            if(!empty($pay)){
                $res = [
                    "snap_token" => '',
                    "redirect_url" => $pay['redirect_url'],
                    "transaction_data" => [
                        "transaction_details" => [
                            "order_id" => $dataInstallment['installment_receipt_number'],
                            "gross_amount" => $dataInstallment['amount'],
                            "id_transaction_academy_installment" =>  $dataInstallment['id_transaction_academy_installment'],
                            "id_transaction" => $dataInstallment['id_transaction']
                        ]
                    ]
                ];
            }
        }

        return response()->json(MyHelper::checkGet($res));
    }

    function midtrans($data, $post)
    {
        $data['gross_amount'] = $data['amount'];
        $requestToMidtrans = Midtrans::token($data['installment_receipt_number'], $data['gross_amount'], null, null, null, 'transaction', $data['id_transaction'], $post['payment_detail'] ?? null, 'apps', null, 'academy');
        $requestToMidtrans['order_id'] = $data['installment_receipt_number'];
        $requestToMidtrans['gross_amount'] = $data['amount'];

        if (isset($requestToMidtrans['token'])) {
            TransactionAcademyInstallment::where('id_transaction_academy_installment', $data['id_transaction_academy_installment'])->update(['paid_status' => 'Pending', 'installment_payment_type' => 'Midtrans']);
            $insert = [
                'id_transaction_academy' => $data['id_transaction_academy'],
                'id_transaction_academy_installment' => $data['id_transaction_academy_installment'],
                'gross_amount' => $data['gross_amount'],
                'order_id' => $data['installment_receipt_number'],
                'token' => $requestToMidtrans['token'],
                'redirect_url' => $requestToMidtrans['redirect_url']
            ];
            if (TransactionAcademyInstallmentPaymentMidtrans::create($insert)) {
                return [
                    'midtrans'      => $requestToMidtrans,
                    'data'          => $data,
                    'redirect_url'  => $requestToMidtrans['redirect_url'] ?? null,
                    'redirect'      => true,
                ];
            }
        }

        return false;
    }

    public function xendit($data, $post)
    {
        $paymentXendit = TransactionAcademyInstallmentPaymentXendit::where('order_id', $data['installment_receipt_number'])->first();
        $post['payment_detail'] = request()->payment_detail;
        if (!($post['phone'] ?? false)) {
            $post['phone'] = request()->user()->phone;
        }
        $grossAmount = $data['amount'];
        if (!$paymentXendit) {
            $paymentXendit = new TransactionAcademyInstallmentPaymentXendit([
                'id_transaction_academy' => $data['id_transaction_academy'],
                'id_transaction_academy_installment' => $data['id_transaction_academy_installment'],
                'order_id' => $data['installment_receipt_number'],
                'xendit_id' => null,
                'external_id' => $data['installment_receipt_number'],
                'business_id' => null,
                'phone' => $post['phone'],
                'type' => $post['payment_detail'],
                'amount' => $grossAmount,
                'expiration_date' => null,
                'failure_code' => null,
                'status' => null,
                'checkout_url' => null,
            ]);
        }

        if ($post['payment_detail'] == 'LINKAJA') {
            $paymentXendit->items = [
                [
                    'id'       => (string) $data['id_transaction_academy'],
                    'price'    => $grossAmount,
                    'name'     => $data['installment_receipt_number'],
                    'quantity' => 1,
                ]
            ];
        }

        if ($paymentXendit->pay($errors)) {
            $result = [
                'redirect' => true,
                'payment_type' => 'Xendit',
                'payment_detail' => $post['payment_detail'],
                'data' => $data,
            ];

            if ($paymentXendit->type == 'OVO') {
                $result['timer']  = (int) MyHelper::setting('setting_timer_ovo', 'value', 60);
                $result['message_timeout'] = 'Sorry, your payment has expired';
            } else {
                $result['redirect_url'] = $paymentXendit->checkout_url;
            }

            TransactionAcademyInstallment::where('id_transaction_academy_installment', $data['id_transaction_academy_installment'])->update(['paid_status' => 'Pending', 'installment_payment_type' => 'Xendit']);

            return $result;
        }
        return [
            'redirect' => true,
            'payment_type' => 'Xendit',
            'payment_detail' => $post['payment_detail'],
            'data' => $data,
        ];
    }

    //run every day at 11:00
    //7 days before deadline
    public function paymentInstallmentReminder(){
        $settingDeadline = Setting::where('key', 'transaction_academy_installment_deadline_date')->first()['value']??null;
        if(empty($settingDeadline)){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Deadline date is empty']
            ]);
        }

        $deadlineDate = date('Y-m-d', strtotime(date('Y-m').'-'.$settingDeadline));
        $currentDate = date('Y-m-d');

        if($currentDate < $deadlineDate){
            $now = strtotime($deadlineDate);
            $your_date = strtotime($currentDate);
            $datediff = $now - $your_date;
            $diff = (int) ($datediff / (60 * 60 * 24));

            if($diff == 7){
                $transactions = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                    ->join('transaction_academy_installment', 'transaction_academy_installment.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                    ->where(function ($q){
                        $q->whereNull('transaction_academy_installment.paid_status')
                            ->orWhere('transaction_academy_installment.paid_status', 'Pending');
                    })
                    ->where('deadline', $deadlineDate)
                    ->select('transaction_academy_installment.*', 'transactions.id_transaction', 'transactions.id_user')->with('user')->get()->toArray();

                foreach ($transactions as $value){
                    app($this->autocrm)->SendAutoCRM(
                        'Payment Academy Installment Reminder',
                        $value['user']['phone'],
                        [
                            'id_transaction' => $value['id_transaction'],
                            'deadline'=> (!empty($value['deadline'])? MyHelper::dateFormatInd($value['deadline'], true, false) : ''),
                            'amount' => number_format($value['amount']),
                            'installment_step' => MyHelper::numberToRomanRepresentation($value['installment_step'])
                        ]
                    );
                }
            }
        }

        return true;
    }

    //run every day at 14:00
    //on time limit
    public function paymentInstallmentDueDate(){
        $settingDeadline = Setting::where('key', 'transaction_academy_installment_deadline_date')->first()['value']??null;
        if(empty($settingDeadline)){
            DB::rollback();
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Deadline date is empty']
            ]);
        }

        $deadlineDate = date('Y-m-d', strtotime(date('Y-m').'-'.$settingDeadline));
        $currentDate = date('Y-m-d');

        if($currentDate == $deadlineDate){
            $transactions = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                ->join('transaction_academy_installment', 'transaction_academy_installment.id_transaction_academy', 'transaction_academy.id_transaction_academy')
                ->where(function ($q){
                    $q->whereNull('transaction_academy_installment.paid_status')
                        ->orWhere('transaction_academy_installment.paid_status', 'Pending');
                })
                ->where('deadline', $deadlineDate)
                ->select('transaction_academy_installment.*', 'transactions.id_transaction', 'transactions.id_user')->with('user')->get()->toArray();

            foreach ($transactions as $value){
                app($this->autocrm)->SendAutoCRM(
                    'Payment Academy Installment Due Date',
                    $value['user']['phone'],
                    [
                        'id_transaction' => $value['id_transaction'],
                        'deadline'=> (!empty($value['deadline'])? MyHelper::dateFormatInd($value['deadline'], true, false) : ''),
                        'amount' => number_format($value['amount']),
                        'installment_step' => ($value['installment_step'] == 1 ? 'Uang Muka' : MyHelper::numberToRomanRepresentation($value['installment_step']))
                    ]
                );
            }
        }

        return true;
    }

    //run every day at 11:00
    //2 days before course
    public function courseReminder(){
        $currentDate = date('Y-m-d');
        $date = date("Y-m-d", strtotime($currentDate." +2 days"));

        $transactions = Transaction::join('transaction_academy', 'transactions.id_transaction', 'transaction_academy.id_transaction')
            ->join('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy', 'transaction_academy.id_transaction_academy')
            ->where('amount_completed', '>', 0)
            ->whereDate('schedule_date', $date)
            ->where('transaction_academy_schedule_status', 'Not Started')
            ->select('transaction_academy_schedules.*', 'transactions.id_transaction', 'transactions.id_user')->with('user')->get()->toArray();

        foreach ($transactions as $value){
            $courseName = TransactionProduct::where('id_transaction', $value['id_transaction'])
                            ->join('products', 'products.id_product', 'transaction_products.id_product')
                            ->first()['product_name']??'';

            app($this->autocrm)->SendAutoCRM(
                'Academy Course Reminder',
                $value['user']['phone'],
                [
                    'id_transaction' => $value['id_transaction'],
                    'course_name'=> $courseName,
                    'schedule_date' => MyHelper::dateFormatInd($value['schedule_date'], true, true),
                    'meeting' => MyHelper::numberToRomanRepresentation($value['meeting'])
                ]
            );
        }

        return true;
    }
}
