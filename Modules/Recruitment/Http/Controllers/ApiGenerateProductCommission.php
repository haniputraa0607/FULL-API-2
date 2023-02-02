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
use Modules\Recruitment\Entities\GeneratedProductCommissionQueue;
use App\Jobs\ExportPayrollJob;
use App\Exports\PayrollExport;
use File;
use Illuminate\Support\Facades\Storage;
use Modules\Recruitment\Entities\HairstylistPayrollQueue;
use App\Jobs\RefreshTransactionCommission;

class ApiGenerateProductCommission extends Controller
{
    
    public function newGenerate(Request $request)
    {
        $post = $request->json()->all();
        $start_date = date('Y-m-01',strtotime($post['start_date']));
        $end_date = date('Y-m-t',strtotime($post['end_date']));
        $setting = Setting::where('key' , 'Refresh Commission Transaction')->first();
        if($setting){
            if($setting['value'] != 'finished'){
                return ['status' => 'fail', 'messages' => ['Cant refresh now, because refresh is in progress']]; 
            }
            $update_setting = Setting::where('key', 'Refresh Commission Transaction')->update(['value' => 'start']);
        }else{
            $create_setting = Setting::updateOrCreate(['key' => 'Refresh Commission Transaction'],['value' => 'start']);
        }
        $send = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
        $create = GeneratedProductCommissionQueue::create($send);
        if($create){
            RefreshTransactionCommission::dispatch($send)->onConnection('refreshcommissionqueue');
        }
        return response()->json(MyHelper::checkCreate($create));
    }
    public function index(Request $request) {
        $post = $request->all();
        $employee = GeneratedProductCommissionQueue::orderBy('created_at', 'desc')->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
    }
    public function status() {
        $employee = GeneratedProductCommissionQueue::where('status', 'Running')->first();
        return MyHelper::checkGet($employee);
    }
    public function exportGenerate(){
        
    	$queue = GeneratedProductCommissionQueue::where('status', 'Running')->first();
    	if (!$queue) {
    		return false;
    	}else{
    		$queue = $queue->toArray();
    	}
    	$data['start_date'] = $queue['start_date'];
    	$data['end_date'] = $queue['end_date'];
        return $data = app('Modules\Recruitment\Http\Controllers\ApiIncomeRefresh')->generate($data);
        if (isset($data['status']) && $data['status'] == "success") {
            return 'success';
        }else{
            return false;
        }
    }
}
