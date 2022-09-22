<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Jobs\UpdateScheduleHSJob;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\ProductService\Entities\ProductHairstylistCategory;
use Modules\Recruitment\Entities\HairstylistCategory;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Modules\Recruitment\Entities\HairstylistSchedule;	
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Outlet\Entities\OutletBox;
use App\Http\Models\LogOutletBox;
use Modules\Recruitment\Entities\UserHairStylistTheory;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;
use Image;
use DB;
use Modules\Recruitment\Entities\UserHairStylistExperience;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionProductService;
use App\Http\Models\Transaction;
use File;
use Storage;
use Modules\Recruitment\Entities\HairstylistCategoryLoan;
use Modules\Recruitment\Entities\HairstylistLoan;
use Modules\Recruitment\Entities\HairstylistLoanReturn;
use Modules\Recruitment\Http\Requests\loan\CreateLoan;
use Modules\Recruitment\Entities\HairstylistSalesPayment;
use Modules\Recruitment\Http\Requests\loan\CreateLoanIcount;
use Modules\Recruitment\Http\Requests\loan\CancelLoanIcount;
use Modules\Recruitment\Http\Requests\loan\SignatureLoan;
use Modules\Recruitment\Http\Requests\loan\SignatureLoanCancel;
use Modules\Recruitment\Entities\HairstylistHoliday;
use Modules\Recruitment\Http\Requests\holiday\CreateHoliday;

class ApiHairStylistHolidayController extends Controller
{
    public function create(CreateHoliday $request){
        $post = $request->json()->all();
        $post['month'] = (int)date('m', strtotime($post['holiday_date']));
        $post['year'] = (int)date('Y', strtotime($post['holiday_date']));
        $save = HairstylistHoliday::create($post);
        return response()->json(MyHelper::checkUpdate($save));
    }
    public function update(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_hs_holiday'])){
            $post = $request->json()->all();
            $post['month'] = (int)date('m', strtotime($post['holiday_date']));
            $post['year'] = (int)date('Y', strtotime($post['holiday_date']));
            $save = HairstylistHoliday::where('id_hs_holiday', $post['id_hs_holiday'])
                    ->update([
                        'holiday_date'  => $post['holiday_date'],
                        'holiday_name'  => $post['holiday_name'],
                        'month'         => $post['month'],
                        'year'          => $post['year'],
                    ]);
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function delete(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_hs_holiday'])){
            $save = HairstylistHoliday::where('id_hs_holiday', $post['id_hs_holiday'])
                    ->delete();
            return response()->json(MyHelper::checkDelete($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
    public function generate(Request $request){
        $year = date('Y');
        $data = MyHelper::get('https://api-harilibur.vercel.app/api?year='.$year);
        if($data){
            foreach ($data as $value) {
                $value['month'] = (int)date('m', strtotime($value['holiday_date']));
                $value['year'] = (int)date('Y', strtotime($value['holiday_date']));
                $first = HairstylistHoliday::where([
                    "holiday_date"=>$value['holiday_date'],
                    "holiday_name"=> $value['holiday_name'],
                    "month"=> $value['month'],
                    "year"=> $value['year'],
                ])->first();
                if(!$first){
                    if($value['is_national_holiday']){
                        unset($value['is_national_holiday']);
                        $save = HairstylistHoliday::create($value);   
                    }
                }
                
            }
        }
        $start = $year.'-01-01';
        $end = $year.'-12-31';
        $date = array();
        for($i=1;$i<2;$i){
            if($start<$end){
                 $day = date('D', strtotime($start));
                    if ($day === "Sun") {
                        $month = date('m', strtotime($start));
                        $year = date('Y', strtotime($start));
                        $name = "Hari Libur Sabtu";
                        $first = HairstylistHoliday::where([
                            "holiday_date"=>$start,
                            "holiday_name"=>$name,
                            "month"=> $month,
                            "year"=> $year,
                        ])->first();
                        if(!$first){
                            $save = HairstylistHoliday::create([
                            "holiday_date"=>$start,
                            "holiday_name"=>$name,
                            "month"=> $month,
                            "year"=> $year,
                        ]);   
                        }
                    } elseif($day === "Sat") {
                        $month = date('m', strtotime($start));
                        $year = date('Y', strtotime($start));
                        $name = "Hari Libur Minggu";
                        $first = HairstylistHoliday::where([
                            "holiday_date"=>$start,
                            "holiday_name"=>$name,
                            "month"=> $month,
                            "year"=> $year,
                        ])->first();
                        if(!$first){
                            $save = HairstylistHoliday::create([
                            "holiday_date"=>$start,
                            "holiday_name"=>$name,
                            "month"=> $month,
                            "year"=> $year,
                        ]);   
                        }
                    }
                    $start= date('Y-m-d', strtotime($start.'+1 days'));
            }else{
                break;
            }
        }
        return response()->json(MyHelper::checkCreate(1));
      
    }
    
    public function index(Request $request){
        $year = date('Y');
        $post = $request->json()->all();
        if(!empty($post['id_hs_holiday'])){
            $data = HairstylistHoliday::where('id_hs_holiday', $post['id_hs_holiday'])
                    ->first();
        }else{
            $data = HairstylistHoliday::where('year', $year)
                    ->orderby('holiday_date','asc')
                    ->get()
                    ->groupby('month');
        }
        return response()->json(MyHelper::checkGet($data));
    }

}
