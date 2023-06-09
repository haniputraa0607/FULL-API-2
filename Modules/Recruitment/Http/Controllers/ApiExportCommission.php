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
use Modules\Recruitment\Entities\ExportCommissionQueue;
use App\Jobs\ExportCommissionJob;
use App\Exports\PayrollExport;
use File;
use Illuminate\Support\Facades\Storage;
use Modules\Recruitment\Entities\HairstylistPayrollQueue;
use App\Exports\DataExport;

class ApiExportCommission extends Controller
{
    
    public function newExport(Request $request)
    {
        $post = $request->json()->all();
        $name_outlet = array();
        foreach ($post['id_outlet'] as $value) {
            $name = Outlet::where('id_outlet',$value)->select('outlet_name','outlet_code')->first();
            $name_outlet[] = $name->outlet_name.' ('.$name->outlet_code.')';
        }
        $insertToQueue = [
            'id_outlet' => json_encode($post['id_outlet']),
            'name_outlet' => json_encode($name_outlet),
            'start_date' => date('Y-m-d', strtotime($post['date_start'])),
            'end_date' => date('Y-m-d', strtotime($post['date_end'])),
            'status_export' => 'Running',
        ];
            $create = ExportCommissionQueue::create($insertToQueue);
        if($create){
            ExportCommissionJob::dispatch($create)->allOnConnection('database');
        }
        return response()->json(MyHelper::checkCreate($create));
    }
    public function exportExcel($queue){
        $id = $queue;
    	$queue = ExportCommissionQueue::where('id_export_commission_queue',  $id)
                ->where('status_export', 'Running')->first();
	if (!$queue) {
    		return false;
    	}
    	$data['date_start'] = $queue['start_date'];
    	$data['date_end'] = $queue['end_date'];
    	$data['id_outlet'] = json_decode($queue['id_outlet']);
        $data = app('Modules\Recruitment\Http\Controllers\ApiHairStylistController')->exportCommission($data);
        if ($data) {
               $excelFile = 'Export_Commission_'.strtotime(date('Y-m-d H:i:s')).mt_rand(0, 1000).time().'.xlsx';
                $directory = 'hairstylist/export-commission/'.$excelFile;
                $dataExport['head'] = array_keys($data[0]);
                $dataExport['body'] = $data;
                $dataExport['title'] = 'Commission_'.date('Ymdhis');
               $store = (new DataExport($dataExport))->store($directory, null, null, ['visibility' => 'public']);
                if ($store) {
                    ExportCommissionQueue::where('id_export_commission_queue',  $queue['id_export_commission_queue'])->update(['url_export' => $directory, 'status_export' => 'Ready']);
                }

            return 'success';
        }else{
            return false;
        }
    }
    public function deleteExport($id) {
        $queue = ExportCommissionQueue::where('id_export_commission_queue', $id)->delete();
        return MyHelper::checkDelete($queue);
   }
    public function index(Request $request) {
        $post = $request->all();
        $employee = ExportCommissionQueue::orderBy('created_at', 'desc')->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
   }
}
