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

class ApiOutletServiseController extends Controller
{
    public $saveImage = "img/outlet/";

    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
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
            ->where('outlets.outlet_status', 'Active')
            ->whereNotNull('outlets.outlet_latitude')
            ->whereNotNull('outlets.outlet_longitude')
            ->whereHas('brands',function($query){
                $query->where('brands.brand_active',1)->where('brands.brand_visibility',1);
            })
            ->with(['brands', 'holidays.date_holidays', 'today'])
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
        if(empty($post['id_outlet']) && empty($post['outlet_code'])){
            return response()->json(['status' => 'fail', 'messages' => ['ID/Code outlet can not be empty']]);
        }

        $detail = Outlet::join('cities', 'cities.id_city', 'outlets.id_city')
                    ->where('outlets.outlet_status', 'Active')
                    ->with(['outlet_schedules','brands', 'today', 'holidays', 'holidays.date_holidays'])
                    ->select('outlets.*', 'cities.city_name');

        if(!empty($post['id_outlet'])){
            $detail = $detail->where('id_outlet', $post['id_outlet'])->first();
        }elseif(!empty($post['outlet_code'])){
            $detail = $detail->where('outlets.outlet_code', $post['outlet_code'])->first();
        }

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

        $isClose = false;
        $currentDate = date('Y-m-d');
        $currentHour = date('H:i:s');
        $open = date('H:i:s', strtotime($detail['today']['open']));
        $close = date('H:i:s', strtotime($detail['today']['close']));
        foreach ($detail['holidays'] as $holidays){
            $holiday = $holidays['date_holidays'];
            $dates = array_column($holiday, 'date');
            if(array_search($currentDate, $dates) !== false){
                $isClose = true;
                break;
            }
        }

        if(strtotime($currentHour) < strtotime($open) || strtotime($currentHour) > strtotime($close) || $detail['today']['is_closed'] == 1){
            $isClose = true;
        }

        $res = [
            'id_outlet' => $detail['id_outlet'],
            'is_close' => $isClose,
            'outlet_code' => $detail['outlet_code'],
            'outlet_name' => $detail['outlet_name'],
            'outlet_description' => (empty($detail['outlet_description']) ? "":$detail['outlet_description']),
            'outlet_image' => (empty($detail['outlet_image']) ? $detail['brands'][0]['image_brand']??'':$detail['outlet_image']),
            'outlet_address' => $detail['outlet_address'],
            'city_name' => $detail['city_name'],
            'color' => (empty($detail['brands'][0]['color_brand']) ? '':$detail['brands'][0]['color_brand']),
            'brand_logo' =>  (empty($detail['brands'][0]['color_brand']) ? '':$detail['brands'][0]['logo_brand']),
            'brand_logo_landscape' => (empty($detail['brands'][0]['logo_landscape_brand']) ? '':$detail['brands'][0]['logo_landscape_brand']),
            'schedules' => $arrSchedule
        ];

        return response()->json(MyHelper::checkGet($res));
    }
}
