<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Employee\Entities\EmployeeSchedule;
use Modules\Employee\Entities\EmployeeScheduleDate;
use App\Http\Models\Outlet;
use App\Http\Models\User;
use DB;
use App\Lib\MyHelper;
use Modules\Employee\Entities\EmployeetAttendance;
use Modules\Employee\Entities\EmployeeTimeOff;
use Modules\Employee\Entities\EmployeeOverTime;
use Modules\Employee\Entities\EmployeeNotAvailable;
use App\Http\Models\Holiday;
use App\Http\Models\Setting;

class ApiEmployeeTimeOffOvertimeController extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function listTimeOff(Request $request)
    {
        $post = $request->all();
        $time_off = EmployeeTimeOff::join('users as employees','employees.id','=','employee_time_off.id_employee')
                    ->join('outlets', 'outlets.id_outlet', '=', 'employee_time_off.id_outlet')
                    ->join('users as requests', 'requests.id', '=', 'employee_time_off.request_by')
                    ->select(
                        'employee_time_off.*',
                        'employees.name',
                        'outlets.outlet_name',
                        'requests.name as request_by'
                    );
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){
                         
                        if($condition['subject']=='name_employee'){
                            $subject = 'employees.name';
                        }elseif($condition['subject']=='outlet'){
                            $subject = 'outlets.outlet_name';
                        }elseif($condition['subject']=='request'){
                            $subject = 'requests.name';
                        }else{
                            $subject = $condition['subject'];  
                        }

                        if($condition['operator'] == '='){
                            $time_off = $time_off->where($subject, $condition['parameter']);
                        }else{
                            $time_off = $time_off->where($subject, 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $time_off = $time_off->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['subject']=='name_employee'){
                                $subject = 'employees.name';
                            }elseif($condition['subject']=='outlet'){
                                $subject = 'outlets.outlet_name';
                            }elseif($condition['subject']=='request'){
                                $subject = 'requests.name';
                            }else{
                                $subject = $condition['subject'];  
                            }

                            if($condition['operator'] == '='){
                                $q->orWhere($subject, $condition['parameter']);
                            }else{
                                $q->orWhere($subject, 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            if($post['order']=='name_employee'){
                $order = 'employees.name';
            }elseif($post['order']=='outlet'){
                $order = 'outlets.outlet_name';
            }elseif($post['order']=='request'){
                $order = 'requests.name';
            }else{
                $order = 'employee_time_off.created_at';
            }
            if(isset($post['page'])){
                $time_off = $time_off->orderBy($order, $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy($order, $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $time_off = $time_off->orderBy('employee_time_off.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $time_off = $time_off->orderBy('employee_time_off.created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($time_off);
    }

    public function listEmployee(Request $request){
        $post = $request->all();
        $list_employee = User::where('id_outlet', $post['id_outlet'])->whereNotNull('id_role')->get()->toArray();
        return $list_employee;
    }

    public function listDate(Request $request){
        $post = $request->all();
        if(empty($post['id_employee']) || empty($post['month']) || empty($post['year'])){
            return response()->json([
            	'status' => 'empty', 
            ]);
        }

        if($post['year']>=date('Y') || (isset($post['type']) && $post['type'] == 'getDetail')){
            if($post['month']>=date('m')|| (isset($post['type']) && $post['type'] == 'getDetail')){

                $cek_employee = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$post['id_employee'])->first();
                $schedule = EmployeeSchedule::where('id', $post['id_employee'])->where('schedule_month', $post['month'])->where('schedule_year', $post['year'])->first();
                if($schedule){
                    if($cek_employee['office_hour_type'] == 'Use Shift'){
                            $id_schedule = $schedule['id_employee_schedule'];

                            if(isset($post['date'])){
                                $time = EmployeeScheduleDate::where('id_employee_schedule',$id_schedule)->where('date',$post['date'])->first();
                                return response()->json([
                                    'status' => 'success', 
                                    'result' => $time
                                ]); 
                            }

                            $detail = EmployeeScheduleDate::where('id_employee_schedule',$id_schedule)->get()->toArray();
                            if($detail){
                                $send = [];
                                foreach($detail as $key => $data){
                                    if($data['date'] >= date('Y-m-d 00:00:00')){
                                        $send[$key]['id_employee_schedule'] = $schedule['id_employee_schedule'];
                                        $send[$key]['date'] = $data['date'];
                                        $send[$key]['date_format'] = date('d F Y', strtotime($data['date']));
                                        $send[$key]['time_start'] = $data['time_start'];
                                        $send[$key]['time_end'] = $data['time_end'];
                                    }
                                }
                                return response()->json([
                                    'status' => 'success', 
                                    'result' => $send
                                ]); 
                            }
                        
                    }elseif($cek_employee['office_hour_type'] == 'Without Shift'){
                        $listDate = MyHelper::getListDate($post['month'], $post['year']);
                        $outletClosed = Outlet::join('users','users.id_outlet','outlets.id_outlet')->with(['outlet_schedules'])->where('users.id',$post['id_employee'])->first();
                        
                        $outletSchedule = [];
                        foreach ($outletClosed['outlet_schedules'] as $s) {
                            $outletSchedule[$s['day']] = [
                                'is_closed' => $s['is_closed'],
                                'time_start' => $s['open'],
                                'time_end' => $s['close'],
                            ];
                        }
                        
                        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
                                    ->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                                    ->where('id_outlet', $outletClosed['id_outlet'])
                                    ->whereMonth('date_holidays.date', $post['month'])
                                    ->where(function($q) use ($post) {
                                        $q->whereYear('date_holidays.date', $post['year'])
                                            ->orWhere('yearly', '1');
                                    })
                                    ->get()
                                    ->keyBy('date');

                        $send = [];
                        foreach($listDate as $key => $list_date){
                            if($list_date >= date('Y-m-d')){
                                $day = date('l, F j Y', strtotime($list_date));
                                $hari = MyHelper::indonesian_date_v2($list_date, 'l');
                                $hari = str_replace('Jum\'at', 'Jumat', $hari);
                                
                                if($outletSchedule[$hari]['is_closed'] != 1){
                                    if(!isset($holidays[$list_date]) && isset($outletSchedule[$hari])) {
                                        $send[$key]['id_employee_schedule'] = $schedule['id_employee_schedule'];
                                        $send[$key]['date'] = $list_date;
                                        $send[$key]['date_format'] = date('d F Y', strtotime($list_date));
                                        $send[$key]['time_start'] = $outletSchedule[$hari]['time_start'];
                                        $send[$key]['time_end'] = $outletSchedule[$hari]['time_end'];
                                    }
                                }
                            }
                        }
                        return response()->json([
                            'status' => 'success', 
                            'result' => $send
                        ]); 
                    }else{
                        return response()->json([
                            'status' => 'empty', 
                        ]);
                    }
                }
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['The schedule for this employee has not been created yet']
                ]);
            }else{
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['The month must be greater than or equal to this month']
                ]);
            }
        }else{
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['The year must be greater than or equal to this year']
            ]);
        }
    }

    public function createTimeOff(Request $request){
        $post = $request->all();
        $data_store = [];
        if(isset($post['id_employee'])){
            $data_store['id_employee'] = $post['id_employee'];
        }
        if(isset($post['id_outlet'])){
            $data_store['id_outlet'] = $post['id_outlet'];
        }
        if(isset($post['date'])){
            $data_store['date'] = $post['date'];
        }
        if(isset($post['time_start'])){
            $data_store['start_time'] = date('H:i:s', strtotime($post['time_start']));
        }
        if(isset($post['time_end'])){
            $data_store['end_time'] = date('H:i:s', strtotime($post['time_end']));
        }
        
        $data_store['request_by'] = auth()->user()->id;
        $data_store['request_at'] = date('Y-m-d');
        
        if($data_store){
            DB::beginTransaction();
            $store = EmployeeTimeOff::create($data_store);
            if(!$store){
                DB::rollBack();
                return response()->json([
                    'status' => 'success', 
                    'messages' => ['Failed to create a request employee time off']
                ]);
            }
            DB::commit();
            return response()->json([
                'status' => 'success', 
                'result' => $store
            ]);
        }
    }

    public function detailTimeOff(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_employee_time_off']) && !empty($post['id_employee_time_off'])){
            $time_off = EmployeeTimeOff::where('id_employee_time_off', $post['id_employee_time_off'])->with(['employee','outlet','approve','request'])->first();
            
            if($time_off==null){
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => $time_off,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateTimeOff(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_employee_time_off']) && !empty($post['id_employee_time_off'])){
            $data_update = [];
            if(isset($post['id_employee'])){
                $data_update['id_employee'] = $post['id_employee'];
            }
            if(isset($post['id_outlet'])){
                $data_update['id_outlet'] = $post['id_outlet'];
            }
            if(isset($post['date'])){
                $data_update['date'] = $post['date'];
            }
            if(isset($post['time_start'])){
                $data_update['start_time'] = date('H:i:s', strtotime($post['time_start']));
            }
            if(isset($post['time_end'])){
                $data_update['end_time'] = date('H:i:s', strtotime($post['time_end']));
            }
            if(isset($post['approve'])){
                $data_update['approve_by'] = auth()->user()->id;
                $data_update['approve_at'] = date('Y-m-d');
            }

            if($data_update){
                DB::beginTransaction();
                $update = EmployeeTimeOff::where('id_employee_time_off',$post['id_employee_time_off'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request employee time off']
                    ]);
                }
                if(isset($post['approve'])){
                    $data_not_avail = [
                        "id_outlet" => $data_update['id_outlet'],
                        "id_employee" => $data_update['id_employee'],
                        "id_employee_time_off" => $post['id_employee_time_off'],
                        "booking_start" => date('Y-m-d', strtotime($data_update['date'])).' '.$data_update['start_time'],
                        "booking_end" => date('Y-m-d', strtotime($data_update['date'])).' '.$data_update['end_time'],
                    ];
                    $store_not_avail = EmployeeNotAvailable::create($data_not_avail);
                    if(!$store_not_avail){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'success', 
                            'messages' => ['Failed to updated a request employee time off']
                        ]);
                    }
                }
                DB::commit();
                return response()->json([
                    'status' => 'success'
                ]);
            }
            
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteTimeOff(Request $request){
        $post = $request->all();
        $delete = EmployeeTimeOff::where('id_employee_time_off', $post['id_employee_time_off'])->update(['reject_at' => date('Y-m-d')]);
        if($delete){
            $delete_hs_not_avail = EmployeeNotAvailable::where('id_employee_time_off', $post['id_employee_time_off'])->delete();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listTimeOffEmployee(Request $request){
        $post = $request->all();
        $user = $request->user()->id;
        $time_off = EmployeeTimeOff::where('id_employee', $user)->whereMonth('date', $post['month'])->whereYear('date', $post['year'])->select('id_employee_time_off', 'type', 'date', 'notes', 'reject_at', 'approve_by')->get()->toArray();
        $time_off = array_map(function($data){
            $data['date'] = MyHelper::dateFormatInd($data['date'], true, false, false);
            if(isset($data['reject_at'])){
                $data['status'] = 'Ditolak';
            }elseif(isset($data['approve_by'])){
                $data['status'] = 'Disetujui';
            }else{
                $data['status'] = 'Pending';
            }
            unset($data['approve_by']);
            unset($data['reject_at']);
            return $data;
        },$time_off);
        
		return $time_off;
    }

    public function createTimeOffEmployee(Request $request){
        $post = $request->all(); 
        $type = Setting::where('key','employee_time_off_type')->get('value_text')->first();
        $type_time_off = [];
        if($type){
            $type_time_off = json_decode($type['value_text']??'' , true);
        }
        return $type_time_off;

    }

}
