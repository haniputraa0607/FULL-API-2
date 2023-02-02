<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\OauthAccessToken;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;

use Modules\Franchise\Entities\TransactionProduct;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;
use Modules\Recruitment\Entities\HairstylistAttendance;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use Modules\Users\Http\Requests\users_forgot;
use Modules\Users\Http\Requests\users_phone_pin_new_v2;
use PharIo\Manifest\EmailTest;
use Auth;
use Modules\Recruitment\Http\Requests\Income;
use Modules\Recruitment\Entities\HairstylistIncome;
use Config;
use Modules\Recruitment\Http\Requests\Export_Outlet;
use Modules\Transaction\Entities\TransactionBreakdown;
use Modules\Recruitment\Entities\HairstylistOverTime;
use Modules\Recruitment\Entities\HairstylistLoan;
use Modules\Recruitment\Entities\HairstylistGroupProteksi;
use Modules\Recruitment\Entities\HairStylistTimeOff;
use Modules\Recruitment\Entities\HairstylistIncomeDetail;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentive;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDefault;
use Modules\Recruitment\Entities\HairstylistGroupProteksiAttendanceDefault;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDayDefault;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDefault;
use Modules\Recruitment\Entities\HairstylistGroupLateDefault;
use Modules\Recruitment\Entities\HairstylistLoanReturn;
use Modules\Recruitment\Entities\HairstylistPayrollQueue;
use App\Jobs\PayrollHs;
use App\Jobs\RefreshTransactionCommission;
use Modules\Recruitment\Entities\GeneratedProductCommissionQueue;

class ApiIncomeRefresh extends Controller
{

    
    public function generate() {
        $data = GeneratedProductCommissionQueue::where('status','Running')->first();
        if($data){
           $income = HairstylistIncome::wherebetween('periode',[$data->start_date,$data->end_date])->get();
            foreach($income as $value){
                $id = $value['id_hairstylist_income'];
                $type = $value['type'];
                $hs = UserHairStylist::where('id_user_hair_stylist',$value['id_user_hair_stylist'])->first();
                $startDate = $value['start_date'];
                $endDate = $value['end_date'];
                $b = new HairstylistIncome();
                $year = date('Y', strtotime($value['periode']));
                $month = date('m', strtotime($value['periode']));
                $date = date('d', strtotime($value['periode']));
               $b->generateIncome($hs,$type,$startDate,$endDate,$id,$year,$month,$date);
            }
            $data->status = "Ready";
            $data->save();
        }
       
        return true;
    }
   
}
