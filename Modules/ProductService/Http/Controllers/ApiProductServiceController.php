<?php

namespace Modules\ProductService\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use App\Http\Models\Setting;
use App\Http\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Brand\Entities\Brand;
use Modules\Product\Entities\ProductDetail;
use App\Lib\MyHelper;
use DB;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\ProductService\Entities\ProductServiceUse;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Favorite\Entities\FavoriteUserHiarStylist;

class ApiProductServiceController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->product      = "Modules\Product\Http\Controllers\ApiProductController";
        $this->outlet       = "Modules\Outlet\Http\Controllers\ApiOutletController";
    }
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->json()->all();

        if (isset($post['visibility'])) {

            if ($post['visibility'] == 'Hidden') {
                $product = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                    ->where('product_detail.id_outlet','=',$post['id_outlet'])
                    ->where('product_detail.product_detail_visibility','=','Hidden')
                    ->where('products.product_type', 'service');
            } else {
                $ids = Product::join('product_detail','product_detail.id_product','=','products.id_product')
                    ->where('product_detail.id_outlet','=',$post['id_outlet'])
                    ->where('product_detail.product_detail_visibility','=','Hidden')
                    ->where('products.product_type', 'service')->pluck('products.id_product')->toArray();
                $product = Product::whereNotIn('id_product', $ids)
                    ->where('products.product_type', 'service');
            }

            unset($post['id_outlet']);
        }else{
            if(isset($post['product_setting_type']) && $post['product_setting_type'] == 'product_price'){
                $product = Product::with(['category', 'discount', 'product_special_price', 'global_price'])->where('products.product_type', 'service');
            }elseif(isset($post['product_setting_type']) && $post['product_setting_type'] == 'outlet_product_detail'){
                $product = Product::with(['category', 'discount', 'product_detail'])->where('products.product_type', 'service');
            }else{
                $product = Product::with(['category', 'product_hs_category', 'discount','product_icount_use_ima' => function($ima){$ima->where('company_type','ima');},'product_icount_use_ims' => function($ims){$ims->where('company_type','ims');}]);
            }
        }

        if(isset($post['rule'])){
            foreach ($post['rule'] as $rule){
                if($rule[0] !== 'all_product'){
                    if ($rule[1] == 'like' && isset($rule[2])) {
                        $rule[2] = '%' . $rule[2] . '%';
                    }

                    if($post['operator'] == 'or'){
                        if(isset($rule[2])){
                            $product->orWhere('products.'.$rule[0], $rule[1],$rule[2]);
                        }else{
                            $product->orWhere('products.'.$rule[0], $rule[1]);
                        }
                    }else{
                        if(isset($rule[2])){
                            $product->where('products.'.$rule[0], $rule[1],$rule[2]);
                        }else{
                            $product->where('products.'.$rule[0], $rule[1]);
                        }
                    }
                }
            }
        }

        if (isset($post['id_product'])) {
            $product->with('category')->where('products.id_product', $post['id_product'])->with(['brands']);
        }

        if (isset($post['product_code'])) {
            $product->with(['global_price','product_special_price','product_tags','brands','product_promo_categories'=>function($q){$q->select('product_promo_categories.id_product_promo_category');},'product_detail'=>function($detail){
                $detail->join('outlets','outlets.id_outlet','=', 'product_detail.id_outlet');
                $detail->groupBy('product_detail.id_outlet');
                $detail->SelectRaw( 'id_product,
                                    product_detail_stock_item,
                                    product_detail_stock_status,
                                    product_detail.id_outlet,
                                    outlet_name');
            }])->where('products.product_code', $post['product_code']);
        }

        if (isset($post['update_price']) && $post['update_price'] == 1) {
            $product->where('product_variant_status', 0);
        }

        if(isset($post['product_type'])){
            $product = $product->where('product_type', $post['product_type']);
        }

        if (isset($post['product_name'])) {
            $product->where('products.product_name', 'LIKE', '%'.$post['product_name'].'%');
        }

        if(isset($post['orderBy'])){
            $product = $product->orderBy($post['orderBy']);
        }
        else{
            $product = $product->orderBy('position', 'asc');
        }

        if(isset($post['admin_list'])){
            $product = $product->withCount('product_detail')->withCount('product_detail_hiddens')->with(['brands']);
        }

        if(isset($post['pagination'])){
            $product = $product->paginate(10);
        }else{
            $product = $product->get();
        }

        if (!empty($product)) {
            foreach ($product as $key => $value) {
                $product[$key]['photos'] = ProductPhoto::select('*', DB::raw('if(product_photo is not null, (select concat("'.config('url.storage_url_api').'", product_photo)), "'.config('url.storage_url_api').'img/default.jpg") as url_product_photo'))->where('id_product', $value['id_product'])->orderBy('product_photo_order', 'ASC')->get()->toArray();
            }
        }

        $product = $product->toArray();
        return response()->json(MyHelper::checkGet($product));
    }

    public function productUseList(){
        $list = Product::where('product_type', 'product')->select('id_product', 'product_name', 'product_code')->get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    public function productUseUpdate(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_product_service']) && !empty($post['id_product_service'])){
            $delete = ProductServiceUse::where('id_product_service', $post['id_product_service'])->delete();
            $insert = [];
            foreach ($post['product_use_data']??[] as $pu){
                $insert[] = [
                    'id_product_service' => $post['id_product_service'],
                    'id_product' => $pu['id_product'],
                    'quantity_use' => (int)$pu['quantity_use'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }
            if(!empty($insert)){
                $save = ProductServiceUse::insert($insert);
                return response()->json(MyHelper::checkUpdate($save));
            }
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID product service can not be empty']]);
        }
    }

    public function homeServiceListProduct(Request $request){
        $post = $request->json()->all();
        $outletHomeService = Setting::where('key', 'default_outlet_home_service')->first()['value']??null;
        $outlet = Outlet::where('id_outlet', $outletHomeService)->first();
        if(empty($outlet)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Outlet default not found']
            ]);
        }

        $brand = Brand::join('brand_outlet', 'brand_outlet.id_brand', 'brands.id_brand')
            ->where('id_outlet', $outlet['id_outlet'])->first();

        if(empty($brand)){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet does not have brand']]);
        }

        $stockStatus = $post['stock_status']??false;

        $productServie = Product::select([
            'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status',
            'product_global_price.product_global_price as product_price', 'processing_time_service', 'product_visibility'
            ])
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->where('product_type', 'service')
            ->where('available_home_service', 1)
            ->with(['photos', 'product_hs_category'])
            ->having('product_price', '>', 0)
            ->orderBy('products.position')
            ->orderBy('products.id_product')
            ->get()->toArray();

        if(!empty($post['id_user_hair_stylist'])){
            $hsFavorite = UserHairStylist::where('id_user_hair_stylist', $post['id_user_hair_stylist'])->first();
        }
        $resProdService = [];
        foreach ($productServie as $val){
            //check category hs when use hs favorite
            if(!empty($hsFavorite['id_hairstylist_category']) && !empty($val['product_hs_category']) && !in_array($hsFavorite['id_hairstylist_category'], array_column($val['product_hs_category'], 'id_hairstylist_category'))){
                continue;
            }

            $stock = 'Available';
            $getProductDetail = ProductDetail::where('id_product', $val['id_product'])->where('id_outlet', $outlet['id_outlet'])->first();
            $val['visibility_outlet'] = $getProductDetail['product_detail_visibility']??null;

            if($val['visibility_outlet'] == 'Hidden' || (empty($val['visibility_outlet']) && $val['product_visibility'] == 'Hidden')){
                continue;
            }

            if($stockStatus === false && !is_null($getProductDetail['product_detail_stock_item']) && $getProductDetail['product_detail_stock_item'] <= 0){
                continue;
            }

            if($stockStatus === true && !is_null($getProductDetail['product_detail_stock_item']) && $getProductDetail['product_detail_stock_item'] <= 0){
                $stock = 'Sold Out';
            }elseif ($stockStatus === true && is_null($getProductDetail['product_detail_stock_item']) && ($getProductDetail['product_detail_stock_status'] == 'Sold Out' || $getProductDetail['product_detail_status'] == 'Inactive')){
                $stock = 'Sold Out';
            }

            if($stock == 'Available' && is_null($getProductDetail['product_detail_stock_item'])){
                $getProductDetail['product_detail_stock_item'] = 50;
            }

            $resProdService[] = [
                'id_product' => $val['id_product'],
                'id_brand' => $brand['id_brand'],
                'product_code' => $val['product_code'],
                'product_name' => $val['product_name'],
                'product_description' => $val['product_description'],
                'product_price' => (int)$val['product_price'],
                'string_product_price' => 'Rp '.number_format((int)$val['product_price'],0,",","."),
                'processing_time' => (int)$val['processing_time_service'],
                'product_stock_status' => $stock,
                'qty_stock' => (int)$getProductDetail['product_detail_stock_item'],
                'photo' => (empty($val['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$val['photos'][0]['product_photo'])
            ];
        }

        $totalAvailableTime = null;
        $timeEnd = Setting::where('key', 'home_service_time_end')->first()['value']??'22:00:00';
        if(!empty($post['id_user_hair_stylist'])){
            if($post['booking_time'] == 'Sekarang'){
                $post['booking_time'] = date('H:i:s', strtotime("+2 minutes", strtotime($post['booking_time_user'])));
            }

            $bookDate = date('Y-m-d H:i:s', strtotime($post['booking_date'].' '.$post['booking_time']));
            //total available time
            $nextSchedule = HairstylistNotAvailable::where('booking_start', '>', $bookDate)->where('id_user_hair_stylist', $post['id_user_hair_stylist'])
                    ->orderBy('booking_start', 'asc')->first()['booking_start']??'';

            if(!empty($nextSchedule) && strtotime(date('Y-m-d', strtotime($nextSchedule))) <= strtotime(date('Y-m-d', strtotime($bookDate)))){
                $to_time = strtotime($nextSchedule);
                $from_time = strtotime($bookDate);
                $totalAvailableTime = round(abs($to_time - $from_time) / 60,2);
            }else{
                $endWork = date('Y-m-d H:i:s', strtotime($post['booking_date'].' '.$timeEnd));
                $to_time = strtotime($endWork);
                $from_time = strtotime($bookDate);
                $totalAvailableTime = round(abs($to_time - $from_time) / 60,2);
            }
        }

        $res = [];
        if(!empty($resProdService)){
            $res = [
                'total_available_time' => $totalAvailableTime,
                'list_service' => $resProdService
            ];
        }
        return response()->json(MyHelper::checkGet($res));
    }

    public function homeServiceDetailProductService(Request $request){
        $post = $request->json()->all();
        $product = Product::where('product_type', 'service')
            ->select([
                'products.id_product', 'products.product_name', 'products.product_code', 'products.product_description', 'product_variant_status', 'processing_time_service',
                'product_global_price.product_global_price as product_price', 'brand_product.id_brand'
            ])
            ->join('brand_product', 'brand_product.id_product', '=', 'products.id_product')
            ->leftJoin('product_global_price', 'product_global_price.id_product', '=', 'products.id_product')
            ->where('products.id_product', $post['id_product'])->first();

        if (!$product) {
            return [
                'status' => 'fail',
                'messages' => ['Product not found']
            ];
        }

        $totalDateShow = Setting::where('key', 'total_show_date_booking_service')->first()->value??1;
        $userTimeZone = (empty($request->user()->user_time_zone_utc) ? 7 : $request->user()->user_time_zone_utc);
        $diffTimeZone = $userTimeZone - 7;
        $currentDate = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d H:i:s', strtotime("+".$diffTimeZone." hour", strtotime($currentDate)));
        $today = date('Y-m-d', strtotime($currentDate));
        $currentTime = date('H:i', strtotime($currentDate));
        $listDate = [];

        $x = 0;
        $count = 1;
        $processingTime = Setting::where('key', 'home_service_processing_time')->first()['value']??60;
        $timeStart = Setting::where('key', 'home_service_time_start')->first()['value']??'07:00:00';
        $timeEnd = Setting::where('key', 'home_service_time_end')->first()['value']??'22:00:00';
        while($count <= (int)$totalDateShow) {
            $date = date('Y-m-d', strtotime('+'.$x.' day', strtotime($today)));
            $open = date('H:i', strtotime($timeStart));
            $close = date('H:i', strtotime($timeEnd));
            $times = [];
            $tmpTime = $open;
            if(strtotime($date.' '.$open) > strtotime($today.' '.$currentTime)) {
                $times[] = $open;
            }elseif($date == $today){
                $times[] = 'Sekarang';
            }
            while(strtotime($tmpTime) < strtotime($close)) {
                $timeConvert = date('H:i', strtotime("+".$processingTime." minutes", strtotime($tmpTime)));
                if(strtotime($date.' '.$timeConvert) > strtotime($today.' '.$currentTime)){
                    $times[] = $timeConvert;
                }
                $tmpTime = $timeConvert;
            }
            if(!empty($times)){
                $listDate[] = [
                    'date' => $date,
                    'times' => $times
                ];
            }
            $count++;
            $x++;
        }

        $result = [
            'id_product' => $product['id_product'],
            'id_brand' => $brand['id_brand']??null,
            'product_code' => $product['product_code'],
            'product_name' => $product['product_name'],
            'product_description' => (empty($product['product_description']) ? '' : $product['product_description']),
            'product_price' => (int)$product['product_price'],
            'string_product_price' => 'Rp '.number_format((int)$product['product_price'],0,",","."),
            'photo' => (empty($product['photos'][0]['product_photo']) ? config('url.storage_url_api').'img/product/item/default.png':config('url.storage_url_api').$product['photos'][0]['product_photo']),
            'list_date' => $listDate
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    /**
     * Return home service available datetime
     * @param  Request $request
     * @return Response
     */
    public function availableDateTime(Request $request){
        $post = $request->json()->all();

        $user = $request->user();
        $totalDateShow = Setting::where('key', 'total_show_date_booking_service')->first()->value??1;
        $today = date('Y-m-d');
        $currentTime = date('H:i');
        $listDate = [];

        $x = 0;
        $count = 1;
        $processingTime = Setting::where('key', 'home_service_processing_time')->first()['value']??60;
        $timeStart = Setting::where('key', 'home_service_time_start')->first()['value']??'07:00:00';
        $timeEnd = Setting::where('key', 'home_service_time_end')->first()['value']??'22:00:00';
        $timeEnd = date('H:i:s', strtotime('-15 minute', strtotime($timeEnd)));

        while($count <= (int)$totalDateShow) {
            $date = date('Y-m-d', strtotime('+'.$x.' day', strtotime($today)));
            $open = date('H:i', strtotime($timeStart));
            $close = date('H:i', strtotime($timeEnd));
            $times = [];
            $tmpTime = $open;
            if(strtotime($date.' '.$open) > strtotime($today.' '.$currentTime)) {
                $times[] = $open;
            }elseif($date == $today){
                $times[] = 'Sekarang';
            }
            while(strtotime($tmpTime) < strtotime($close)) {
                $timeConvert = date('H:i', strtotime("+".$processingTime." minutes", strtotime($tmpTime)));
                if(strtotime($date.' '.$timeConvert) > strtotime($today.' '.$currentTime) && strtotime($date.' '.$timeConvert) <= strtotime($date.' '.$close)){
                    $times[] = $timeConvert;
                }
                $tmpTime = $timeConvert;
            }
            if(!empty($times)){
                $listDate[] = [
                    'date' => $date,
                    'times' => $times
                ];
            }
            $count++;
            $x++;
        }

        $result = [
            'favorite_hs' => FavoriteUserHiarStylist::where('id_user', $user->id)->exists(),
            'dates' => $listDate
        ];

        return response()->json(MyHelper::checkGet($result));
    }

    public function homeServiceAvailableHsFavorite(Request $request){

        $post = $request->json()->all();
        $idUser = $request->user()->id??null;
        $address = UserAddress::where('id_user', $idUser)->where('id_user_address', $post['id_user_address'])->first();
        if(empty($address)){
            return response()->json([
                'status'    => 'fail',
                'messages'  => ['Address user not found']
            ]);
        }

        $post['latitude'] = $address['latitude'];
        $post['longitude'] = $address['longitude'];

        if($post['booking_time'] == 'Sekarang'){
            $post['booking_time'] = date('H:i', strtotime("+2 minutes", strtotime($post['booking_time_user'])));
        }

        $bookDate = date('Y-m-d', strtotime($post['booking_date']));
        $bookTime = date('H:i:s', strtotime($post['booking_time']));
        $bookTimeStart = date('Y-m-d H:i:s', strtotime($bookDate.' '.$bookTime));
        $bookTimeEnd = date('Y-m-d H:i:s', strtotime("+30 minutes", strtotime($bookDate.' '.$bookTime)));
        $userTimeZone = (empty($request->user()->user_time_zone_utc) ? 7 : $request->user()->user_time_zone_utc);
        $diffTimeZone = $userTimeZone - 7;
        $currentDate = date('Y-m-d H:i:s');
        $currentDate = date('Y-m-d H:i:s', strtotime("+".$diffTimeZone." hour", strtotime($currentDate)));
        $maximumRadius = (int)(Setting::where('key', 'home_service_hs_maximum_radius')->first()['value']??25);

        if(strtotime($bookDate.' '.$bookTime) < strtotime($currentDate)){
            return response()->json(['status' => 'fail', 'messages' => ['Book time is invalid']]);
        }

        $hsNotAvailable = HairstylistNotAvailable::whereRaw('"'.$bookTimeStart.'" BETWEEN booking_start AND booking_end')
                        ->pluck('id_user_hair_stylist')->toArray();
        $hsNotAvailable = array_unique($hsNotAvailable);

        $listHs = UserHairStylist::where('user_hair_stylist_status', 'Active')
            ->whereIn('id_user_hair_stylist', function($query) use($idUser){
                $query->select('id_user_hair_stylist')
                    ->from('favorite_user_hair_stylist')
                    ->where('id_user', $idUser);
            })->get()->toArray();

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
        $timeEnd = Setting::where('key', 'home_service_time_end')->first()['value']??'22:00:00';
        $timeEnd = date('H:i:s', strtotime('-15 minute', strtotime($timeEnd)));
        $res = [];
        foreach ($listHs as $val){
            $available = true;
            $availableText = 'Tersedia';
            //check schedule hs
            $shiftTimeStart = null;
            $shiftTimeEnd = null;
            $shift = HairstylistScheduleDate::leftJoin('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                    ->whereNotNull('approve_at')->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                    ->whereDate('date', $bookDate)
                    ->first()['shift']??'';
            if(!empty($shift)){
                $idOutletSchedule = OutletSchedule::where('id_outlet', $val['id_outlet'])
                        ->where('day', $bookDay)->first()['id_outlet_schedule']??null;
                $getTimeShift = app($this->product)->getTimeShift(strtolower($shift),$val['id_outlet'], $idOutletSchedule);
                if(!empty($getTimeShift['end'])){
                    $shiftTimeStart = date('H:i:s', strtotime($getTimeShift['start']));
                    $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
                    if(strtotime($bookTime) >= strtotime($shiftTimeStart) && strtotime($bookTime) < strtotime($shiftTimeEnd)){
                        $available = false;
                    }
                }
            }

            if(array_search($val['id_user_hair_stylist'], $hsNotAvailable) !== false){
                $available = false;
            }

            if(empty($val['latitude']) && empty($val['longitude'])){
                continue;
            }
            $distance = (float)app($this->outlet)->distance($post['latitude'], $post['longitude'], $val['latitude'], $val['longitude'], "K");

            if($distance > $maximumRadius){
                continue;
            }

            if($val['home_service_status'] == 0){
                $available = false;
            }

            if($available == true){
                $nextSchedule = HairstylistNotAvailable::where('booking_start', '>', $bookDate.' '.$bookTime)->where('id_user_hair_stylist', $val['id_user_hair_stylist'])
                        ->orderBy('booking_start', 'asc')->first()['booking_start']??'';
                if(!empty($nextSchedule) && strtotime(date('Y-m-d', strtotime($nextSchedule))) <= strtotime($bookDate)){
                    $availableText = 'Tersedia sampai pukul '.date('H:i', strtotime($nextSchedule));
                }elseif(!empty($shiftTimeStart) && strtotime($bookTime) < strtotime($shiftTimeStart)){
                    $availableText = 'Tersedia sampai pukul '.date('H:i', strtotime($shiftTimeStart));
                }
            }else{
                $availableText = '';
            }

            $res[] = [
                'id_user_hair_stylist' => $val['id_user_hair_stylist'],
                'name' => $val['fullname'],
                'photo' => (empty($val['user_hair_stylist_photo']) ? config('url.storage_url_api').'img/product/item/default.png':$val['user_hair_stylist_photo']),
                'rating' => $val['total_rating'],
                'available_status' => $available,
                'available_text' => $availableText,
                'distance' => $distance
            ];
        }

        usort($res, function($a, $b) {
            return $a['distance'] > $b['distance'];
        });

        return response()->json(['status' => 'success', 'result' => $res]);
    }
}
