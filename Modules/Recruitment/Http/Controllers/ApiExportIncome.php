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
use Modules\Recruitment\Entities\ExportPayrollQueue;
use App\Jobs\ExportPayrollJob;
use App\Exports\PayrollExport;
use File;
use Illuminate\Support\Facades\Storage;

class ApiExportIncome extends Controller
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
            'start_date' => $post['start_date'],
            'end_date' => $post['end_date'],
            'status_export' => 'Running'
        ];
        $create = ExportPayrollQueue::create($insertToQueue);
        if($create){
            ExportPayrollJob::dispatch($create)->allOnConnection('database');
        }
        return response()->json(MyHelper::checkCreate($create));
    }
    public function exportExcel($queue){
        $id = $queue;
    	$queue = ExportPayrollQueue::where('id_export_payroll_queue', $queue)->where('status_export', 'Running')->first();
        
    	if (!$queue) {
    		return false;
    	}else{
    		$queue = $queue->toArray();
    	}
    	$data['start_date'] = $queue['start_date'];
    	$data['end_date'] = $queue['end_date'];
    	$data['id_outlet'] = json_decode($queue['id_outlet']);
        $data = app('Modules\Recruitment\Http\Controllers\ApiIncome')->export_periode($data);
        if (isset($data['status']) && $data['status'] == "success") {
               $excelFile = 'Export_'.strtotime(date('Y-m-d H:i:s')).mt_rand(0, 1000).time().'.xlsx';
                $directory = 'hairstylist/export-payroll/'.$excelFile;
                $dataExport = $data['result'];
                $store = (new PayrollExport($dataExport))->store($directory); 
                if ($store) {
//                    $path = storage_path('app/'.$directory);
//                    $contents = File::get($path);
//                    if(config('configs.STORAGE') != 'local'){
//                        $store = Storage::disk(config('configs.STORAGE'))->put($directory, $contents, 'public');
//                        $delete = File::delete($path);
//                    }
                    ExportPayrollQueue::where('id_export_payroll_queue', $id)->update(['url_export' => $directory, 'status_export' => 'Ready']);
                }

            return 'success';
        }else{
            return false;
        }
//    	 $excelFile = 'Transaction_['.$start.'_'.$end.']['.$getOutlet['outlet_code'].']_'.mt_rand(0, 1000).time().'.xlsx';
//                $directory = 'franchise/report/transaction/'.$excelFile;
//                $store  = (new MultipleSheetExport([
//                    "Summary" => $summary,
//                    "Calculation Fee" => $dataDisburse,
//                    "Detail Transaction" => $generateTrx
//                ]))->store($directory);
//
//
//                if ($store) {
//                	$path = storage_path('app/public/'.$directory);
//                    $contents = File::get($path);
//	                if(config('configs.STORAGE') != 'local'){
//	                    $store = Storage::disk(config('configs.STORAGE'))->put($directory, $contents, 'public');
//	                }
//	                $delete = File::delete($path);
//                    ExportFranchiseQueue::where('id_export_franchise_queue', $queue['id_export_franchise_queue'])->update(['url_export' => $directory, 'status_export' => 'Ready']);
//                }
//            }
//
//            return 'success';
//        }else{
//            return 'Outlet Not Found';
//        }
    }
    public function deleteExport($id) {
        $queue = ExportPayrollQueue::where('id_export_payroll_queue', $id)->delete();
        return MyHelper::checkDelete($queue);
   }
    public function index(Request $request) {
        $post = $request->all();
        $employee = ExportPayrollQueue::orderBy('created_at', 'desc')->paginate($request->length ?: 10);
        return MyHelper::checkGet($employee);
   }
}
