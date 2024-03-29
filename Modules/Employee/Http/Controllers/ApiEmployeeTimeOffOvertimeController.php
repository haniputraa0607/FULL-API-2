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
use Modules\Employee\Entities\EmployeeTimeOffDocument;
use Modules\Employee\Entities\EmployeeTimeOffImage;
use Modules\Employee\Entities\EmployeeOvertime;
use Modules\Employee\Entities\EmployeeOvertimeDocument;
use Modules\Employee\Entities\EmployeeNotAvailable;
use App\Http\Models\Province;
use Modules\Employee\Entities\EmployeeAttendance;
use App\Http\Models\Holiday;
use App\Http\Models\Setting;
use Modules\Employee\Http\Requests\EmployeeTimeOffCreate;
use Modules\Employee\Entities\EmployeeOfficeHour;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use Modules\Employee\Entities\EmployeeChangeShift;

class ApiEmployeeTimeOffOvertimeController extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->time_off = "img/employee/time_off/";
        $this->time_off_approve = "img/employee/time_off_approve/";
        $this->overtime_approve = "img/employee/overtime_approve/";
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
                if(empty($cek_employee['office_hour_type'])){
                    $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
                    if($setting_default){
                        $old_data = $cek_employee;
                        $cek_employee = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                        $cek_employee['id_outlet'] = $old_data['id_outlet'];
                    }
                }
                $data_outlet = Outlet::where('id_outlet', $cek_employee['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;

                $schedule = EmployeeSchedule::where('id', $post['id_employee'])->where('schedule_month', $post['month'])->where('schedule_year', $post['year'])->first();
                if($schedule || $cek_employee['office_hour_type'] == 'Without Shift'){
                    if($cek_employee['office_hour_type'] == 'Use Shift' || isset($schedule['id_office_hour_shift'])){
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
                                $listDate = MyHelper::getListDate($post['month'], $post['year']);
                                $send = [];
                                foreach($detail as $key => $data){
                                    if($data['date'] >= date('Y-m-d 00:00:00')){
                                        $send[date('Y-m-d',strtotime($data['date']))]['id_employee_schedule'] = $schedule['id_employee_schedule'];
                                        $send[date('Y-m-d',strtotime($data['date']))]['date'] = $data['date'];
                                        $send[date('Y-m-d',strtotime($data['date']))]['date_format'] = date('d F Y', strtotime($data['date']));
                                        $send[date('Y-m-d',strtotime($data['date']))]['time_start'] = $data['time_start'] ? MyHelper::adjustTimezone($data['time_start'], $timeZone, 'H:i') : null;
                                        $send[date('Y-m-d',strtotime($data['date']))]['time_end'] = $data['time_end'] ? MyHelper::adjustTimezone($data['time_end'], $timeZone, 'H:i') : null;
                                    }
                                }
                                $result = [];
                                foreach($listDate as $date){
                                    if(date('Y-m-d 00:00:00',strtotime($date)) >= date('Y-m-d 00:00:00') && isset($send[$date])){
                                        $result[] = $send[$date];
                                    }elseif(date('Y-m-d 00:00:00',strtotime($date)) >= date('Y-m-d 00:00:00') && !isset($send[$date])){
                                        $result[] = [
                                            'id_employee_schedule' => null,
                                            'date' => date('Y-m-d 00:00:00',strtotime($date)),
                                            'date_format' => date('d F Y', strtotime($date)),
                                            'time_start' => null,
                                            'time_end' => null,
                                        ];
                                    }
                                }
                                return response()->json([
                                    'status' => 'success', 
                                    'result' => $post['type_request'] == 'time_off' ? $result : $send
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
                                
                                if($post['type_request'] == 'time_off'){
                                    $send[$key]['id_employee_schedule'] = $schedule['id_employee_schedule'];
                                    $send[$key]['date'] = $list_date;
                                    $send[$key]['date_format'] = date('d F Y', strtotime($list_date));
                                    $send[$key]['time_start'] = $cek_employee['office_hour_start'] ? MyHelper::adjustTimezone($cek_employee['office_hour_start'], $timeZone, 'H:i') : null;
                                    $send[$key]['time_end'] = $cek_employee['office_hour_end'] ? MyHelper::adjustTimezone($cek_employee['office_hour_end'], $timeZone, 'H:i') : null;
                                }else{
                                    if($outletSchedule[$hari]['is_closed'] != 1){
                                        if(!isset($holidays[$list_date]) && isset($outletSchedule[$hari])) {
                                            $send[$key]['id_employee_schedule'] = $schedule['id_employee_schedule'];
                                            $send[$key]['date'] = $list_date;
                                            $send[$key]['date_format'] = date('d F Y', strtotime($list_date));
                                            $send[$key]['time_start'] = $cek_employee['office_hour_start'] ? MyHelper::adjustTimezone($cek_employee['office_hour_start'], $timeZone, 'H:i') : null;
                                            $send[$key]['time_end'] = $cek_employee['office_hour_end'] ? MyHelper::adjustTimezone($cek_employee['office_hour_end'], $timeZone, 'H:i') : null;
                                        }
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
            $time_off = EmployeeTimeOff::where('id_employee_time_off', $post['id_employee_time_off'])->with(['employee.employee','outlet','approve','request','documents'])->first();
            
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
            if(isset($post['id_approve']) || (isset($post['type']) && $post['type'] == 'HRGA Approved')){
                $get_data = EmployeeTimeOff::where('id_employee_time_off',$post['id_employee_time_off'])->first();
                $post['id_employee'] = $get_data['id_employee'];
                $post['start_date'] = $get_data['start_date'];
                $post['end_date'] = $get_data['end_date'];
                if($get_data['use_quota_time_off']==1){
                    $post['use_quota_time_off'] = 1;
                }
            }
            if(isset($post['id_employee'])){
                $data_update['id_employee'] = $post['id_employee'];
            }
            if(isset($post['start_date'])){
                $data_update['start_date'] = $post['start_date'];
            }
            if(isset($post['end_date'])){
                $data_update['end_date'] = $post['end_date'];
            }
            if(isset($post['approve_notes'])){
                $data_update['approve_notes'] = $post['approve_notes'];
            }
            if(isset($post['use_quota_time_off'])){
                $data_update['use_quota_time_off'] = 1;
            }else{
                $data_update['use_quota_time_off'] = 0;
            }
            if(isset($post['id_outlet'])){
                $data_update['id_outlet'] = $post['id_outlet'];
            }else{
                $data_update['id_outlet'] = $get_data['id_outlet'];
            }
            if(isset($post['type'])){
                $data_update['status'] = $post['type'];
                if($post['type'] == 'HRGA Approved'){
                    $post['approve'] = true;
                }
            }
            if(isset($post['approve'])){
                $data_update['approve_by'] = $post['id_approve'] ?? auth()->user()->id;
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
                //doc
                $dataDoc = [
                    'id_user_approved' => $post['id_approve'] ?? auth()->user()->id,
                    'date' => date('Y-m-d')
                ];
                if(isset($post['notes'])){
                    $dataDoc['notes'] = $post['notes'];
                }
                if (isset($post['attachment']) && !empty($post['attachment'])) {
                    $upload = MyHelper::uploadFile($post['attachment'], $this->time_off_approve, 'pdf');
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $dataDoc['attachment'] = $upload['path'];
                    } else {
                        $result = [
                            'error'    => 1,
                            'status'   => 'fail',
                            'messages' => ['fail upload file']
                        ];
                        return $result;
                    }
                }
                $storeDoc = EmployeeTimeOffDocument::updateOrCreate(['id_employee_time_off' => $post['id_employee_time_off'], 'type' => $post['type']],$dataDoc);
                if(!$storeDoc){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request employee time off']
                    ]);
                }
                if(isset($post['approve'])){

                    if(strtotime($data_update['start_date']) > strtotime($data_update['end_date'])){
                        DB::rollBack();
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Start date cant be greater than end date']
                        ]); 
                    }
                    
                    $time_off_quota = Setting::where('key','quota_employee_time_off')->get('value')->first()['value'] ?? 14;
                    if($data_update['use_quota_time_off']==1){
                        $time_off_this_employee = EmployeeTimeOff::where('id_employee', $data_update['id_employee'])->where('id_employee_time_off','!=',$post['id_employee_time_off'])->whereNotNull('approve_by')->whereNull('reject_at')->whereYear('start_date',date('Y',strtotime($data_update['start_date'])))->where('use_quota_time_off', 1)->sum('range');
                        $time_off_quota = $time_off_quota - $time_off_this_employee;
                        if($time_off_quota <= 0){
                            DB::rollBack();
                            return response()->json([
                                'status'=>'fail',
                                'messages'=>['Employee have no time off qouta']
                            ]); 
                        }
                    }

                    $array_dates = $this->getBetweenDates($data_update['start_date'],$data_update['end_date']);
                    
                    $time_off = EmployeeTimeOff::where('id_employee',$data_update['id_employee'])->where('id_employee_time_off','!=',$post['id_employee_time_off'])->where('id_outlet',$data_update['id_outlet'])
                    ->where(function($time)use($data_update){
                        $time->where(function($w) use($data_update){$w->whereDate('start_date','>=',$data_update['start_date'])->whereDate('end_date','<=',$data_update['end_date']);})
                        ->orWhere(function($w2) use($data_update){$w2->whereDate('start_date','<=',$data_update['start_date'])->whereDate('end_date','>=',$data_update['start_date'])->whereDate('end_date','<=',$data_update['end_date']);})
                        ->orWhere(function($w3) use($data_update){$w3->whereDate('start_date','>=',$data_update['start_date'])->whereDate('start_date','<=',$data_update['end_date'])->whereDate('end_date','>=',$data_update['end_date']);})
                        ->orWhere(function($w4) use($data_update){$w4->whereDate('start_date','<=',$data_update['start_date'])->whereDate('end_date','>=',$data_update['end_date']);});
                    })->get()->toArray();
                    if($time_off){
                        //disetujui tdk bisa lagi mengajukan
                        foreach($time_off as $tf){
                            if(isset($tf['approve_by']) && !isset($tf['reject_at'])){
                                DB::rollBack();
                                return response()->json(['status' => 'fail', 'messages' => ['There has been a request time off approved at '.date('F j, Y', strtotime($tf['start_date'])).' to '.date('F j, Y', strtotime($tf['end_date']))]]);
                            }
                        }
                        //pending
                        foreach($time_off as $tf){
                            if(!isset($tf['approve_by']) && !isset($tf['reject_at'])){
                            DB::rollBack();
                                return response()->json(['status' => 'fail', 'messages' => ['There has been a request time off waiting to approve at '.date('F j, Y', strtotime($tf['start_date'])).' to '.date('F j, Y', strtotime($tf['end_date']))]]);
                            }
                        }
                    }

                    $date_close = 0;
                    $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$data_update['id_employee'])->first();
                    if(empty($type_shift['office_hour_type'])){
                        $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
                        if($setting_default){
                            $type_shift = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                            if(empty($type_shift)){
                                DB::rollBack();
                                return response()->json([
                                    'status'=>'fail',
                                    'messages'=>['Shift schedule has not been created']
                                ]);
                            }
                        }
                    }
                    $type_shift = $type_shift['office_hour_type'];
                    foreach($array_dates ?? [] as $val_date){
                        $closeOrHoliday = false;
                        $array_date = explode('-',$val_date);
                        $schedule_month = EmployeeSchedule::where('id',$data_update['id_employee'])->where('schedule_month',$array_date[1])->where('schedule_year',$array_date[0])->first();
                
                        //closed
                        $outletClosed = Outlet::join('users','users.id_outlet','outlets.id_outlet')->with(['outlet_schedules'])->where('users.id',$data_update['id_employee'])->first();
                        $outletSchedule = [];
                        foreach ($outletClosed['outlet_schedules'] as $s) {
                            $outletSchedule[$s['day']] = [
                                'is_closed' => $s['is_closed'],
                                'time_start' => $s['open'],
                                'time_end' => $s['close'],
                            ];
                        }
                
                        $day = date('l, F j Y', strtotime($val_date));
                        $hari = MyHelper::indonesian_date_v2($val_date, 'l');
                        $hari = str_replace('Jum\'at', 'Jumat', $hari);
                        
                        if($outletSchedule[$hari]['is_closed'] == 1){
                            $date_close = $date_close + 1;
                            $closeOrHoliday = true;
                        }
                
                        //holiday
                        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
                                            ->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                                            ->where('id_outlet', $data_update['id_outlet'])
                                            ->where(function($p1) use($val_date, $array_date) {
                                                $p1->whereDate('date_holidays.date', $val_date)
                                                    ->orWhere(function($p2) use($array_date){
                                                        $p2->where('holidays.yearly', '1')
                                                            ->whereDay('date_holidays.date', $array_date[2])
                                                            ->whereMonth('date_holidays.date', $array_date[1]);
                                                    });
                                            })
                                            ->get()->toArray();
                        if($holidays){
                            $date_close = $date_close + 1;
                            $closeOrHoliday = true;
                        }

                        if((!$schedule_month && $type_shift == 'Use Shift') || (isset($schedule_month['id_office_hour_shift']))){
                            $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                                    ->join('users','users.id','employee_schedules.id')
                                                                    ->where('users.id', $data_update['id_employee'])
                                                                    ->where('employee_schedules.schedule_month', $array_date[1])
                                                                    ->where('employee_schedules.schedule_year', $array_date[0])
                                                                    ->whereDate('employee_schedule_dates.date', $val_date)
                                                                    ->first();
                            
                            if(!$schedule_date && ( !$schedule_month || ($schedule_month && !$closeOrHoliday))){
                                DB::rollBack();
                                return response()->json(['status' => 'fail', 'messages' => ['Schedule for this date has not been created']]);
                            }
                        }

                        
                    }
                    $data_not_avail = [
                        "id_outlet" => $data_update['id_outlet'],
                        "id_employee" => $data_update['id_employee'],
                        "id_employee_time_off" => $post['id_employee_time_off'],
                    ];
                    $diff = strtotime($data_update['end_date']) - strtotime($data_update['start_date']);
                    $diff = ($diff / 60 / 60 / 24) + 1 - $date_close;
                    if($diff > 7){
                        DB::rollBack();
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Maximum duration of time off is 7 days']
                        ]); 
                    }

                    if($data_update['use_quota_time_off']==1){
                        $time_off_quota = $time_off_quota - $diff;
                        if($time_off_quota < 0){
                            DB::rollBack();
                            return response()->json([
                                'status'=>'fail',
                                'messages'=>['Time off qouta not enough']
                            ]); 
                        }
                    }
                    $update_range = EmployeeTimeOff::where('id_employee_time_off',$post['id_employee_time_off'])->update(['range' => $diff]);
                    $store_not_avail = EmployeeNotAvailable::create($data_not_avail);
                    if(!$store_not_avail){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'success', 
                            'messages' => ['Failed to updated a request employee time off']
                        ]);
                    }
                    $user_employee = User::join('employee_time_off','employee_time_off.id_employee','users.id')->where('employee_time_off.id_employee_time_off',$post['id_employee_time_off'])->first();
                    $office = Outlet::where('id_outlet',$user_employee['id_outlet'])->first();
                    $approve_by = null;
                    if(isset($post['id_approve']) && !empty($post['id_approve'])){
                        $approve_by = User::where('id',$post['id_approve'])->first() ?? null;
                    }
                    
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Time Off Approved', 
                            $user_employee['phone'] ?? null,
                            [
                                'user_update'=> $approve_by ? $approve_by['name'] : $request->user()->name,
                                'time_off_date'=> MyHelper::dateFormatInd($data_update['start_date'], true, false, false).' - '.MyHelper::dateFormatInd($data_update['end_date'], true, false, false),
                                'name_office'=> $office['outlet_name'],
                                'category' => 'Time Off',
                            ], null, false, false, $recipient_type = 'employee', null, true
                        );
                        if (!$autocrm) {
                            DB::rollBack();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
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

    public function rejectTimeOff(Request $request){
        $post = $request->all();
        if($post['type'] == 'HRGA Approved'){
            $updateData = [
                'status' => 'Pending'
            ];
        }elseif($post['type'] == 'Manager Approved'){
            return $this->deleteTimeOff(New Request(['id_employee_time_off'=>$post['id_employee_time_off'],'id_approve' => $request->user()->id]));
        }
        $update = EmployeeTimeOff::where('id_employee_time_off', $post['id_employee_time_off'])->update($updateData);
        if($update){
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteTimeOff(Request $request){
        $post = $request->all();
        $update = ['reject_at' => date('Y-m-d')];
        if(isset($post['approve_notes']) && !empty($post['approve_notes'])){
            $update['approve_notes'] = $post['approve_notes'];
        }
        $delete = EmployeeTimeOff::where('id_employee_time_off', $post['id_employee_time_off'])->update($update);
        if($delete){
            $reject_by = null;
            if(isset($post['id_approve']) && !empty($post['id_approve'])){
                $reject_by = User::where('id',$post['id_approve'])->first() ?? null;
            }
            $delete_hs_not_avail = EmployeeNotAvailable::where('id_employee_time_off', $post['id_employee_time_off'])->delete();
            $user_employee = User::join('employee_time_off','employee_time_off.id_employee','users.id')->where('employee_time_off.id_employee_time_off',$post['id_employee_time_off'])->first();
            $office = Outlet::where('id_outlet',$user_employee['id_outlet'])->first();
            $data_time_off =EmployeeTimeOff::where('id_employee_time_off', $post['id_employee_time_off'])->first();
            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Employee Request Time Off Rejected', 
                    $user_employee['phone'] ?? null,
                    [
                        'user_update'=> $reject_by ? $reject_by['name'] : $request->user()->name,
                        'time_off_date'=> MyHelper::dateFormatInd($data_time_off['start_date'], true, false, false).' - '.MyHelper::dateFormatInd($data_time_off['end_date'], true, false, false),
                        'name_office'=> $office['outlet_name'],
                        'category' => 'Time Off',
                    ], null, false, false, $recipient_type = 'employee', null, true
                );
                if (!$autocrm) {
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Failed to send']
                    ]);
                }
            }
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listTimeOffEmployee(Request $request){
        $post = $request->all();
        $user = $request->user()->id;
        $time_off = EmployeeTimeOff::where('id_employee', $user)->whereMonth('start_date', $post['month'])->whereYear('start_date', $post['year'])->select('id_employee_time_off', 'type', 'start_date', 'end_date', 'notes', 'reject_at', 'approve_by')->get()->toArray();
        $time_off = array_map(function($data){
            $data['start_date'] = MyHelper::dateFormatInd($data['start_date'], true, false, false);
            $data['end_date'] = MyHelper::dateFormatInd($data['end_date'], true, false, false);
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
        
        return MyHelper::checkGet($time_off);

    }

    public function createTimeOffEmployee(Request $request){
        $post = $request->all(); 
        $type = Setting::where('key','employee_time_off_type')->get('value_text')->first();
        $type_time_off = [];
        $send;
        if($type){
            $type_time_off = json_decode($type['value_text']??'' , true);
            foreach($type_time_off ?? [] as $key => $val){
                $send[] = $key;
            }
        }
        return MyHelper::checkGet($send);
    }

    public function getBetweenDates($start, $end){
        $rangArray = [];
            
        $start = strtotime($start);
        $end = strtotime($end);
             
        for ($currentDate = $start; $currentDate <= $end; $currentDate += (86400)) {                  
            $date = date('Y-m-d', $currentDate);
            $rangArray[] = $date;
        }
  
        return $rangArray;
    }

    public function storeTimeOffEmployee(EmployeeTimeOffCreate $request){
        $post = $request->all();
        $employee = $request->user()->id;
        $office = $request->user()->id_outlet;

        if(strtotime($post['start_date']) > strtotime($post['end_date'])){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Tanggal mulai cuti tidak bisa melebihi tanggal selesai cuti']
            ]); 
        }

        //cek date
        if(date('Y-m-d', strtotime($post['start_date'])) < date('Y-m-d') || date('Y-m-d', strtotime($post['end_date'])) < date('Y-m-d')){
            return response()->json(['status' => 'fail', 'messages' => ['Minimal tanggal pengajuan cuti adalah hari ini']]);
        }   
        
        $array_dates = $this->getBetweenDates($post['start_date'],$post['end_date']);

        $type = Setting::where('key','employee_time_off_type')->get('value_text')->first();
        $type_time_off = [];
        if($type){
            $type_time_off = json_decode($type['value_text']??'' , true);
        }

        //cek_time_off
        $time_off = EmployeeTimeOff::where('id_employee',$employee)->where('id_outlet',$office)
        ->where(function($time)use($post){
            $time->where(function($w) use($post){$w->whereDate('start_date','>=',$post['start_date'])->whereDate('end_date','<=',$post['end_date']);})
            ->orWhere(function($w2) use($post){$w2->whereDate('start_date','<=',$post['start_date'])->whereDate('end_date','>=',$post['start_date'])->whereDate('end_date','<=',$post['end_date']);})
            ->orWhere(function($w3) use($post){$w3->whereDate('start_date','>=',$post['start_date'])->whereDate('start_date','<=',$post['end_date'])->whereDate('end_date','>=',$post['end_date']);})
            ->orWhere(function($w4) use($post){$w4->whereDate('start_date','<=',$post['start_date'])->whereDate('end_date','>=',$post['end_date']);});
        })->get()->toArray();
        if($time_off){
            //disetujui tdk bisa lagi mengajukan
            foreach($time_off as $tf){
                if(isset($tf['approve_by']) && !isset($tf['reject_at'])){
                    return response()->json(['status' => 'fail', 'messages' => ['Sudah ada pengajuan cuti yang disetujui pada tanggal '.MyHelper::indonesian_date_v2($tf['start_date'],'d F Y').' sampai '.MyHelper::indonesian_date_v2($tf['end_date'],'d F Y')]]);
                }
            }
            //pending
            foreach($time_off as $tf){
                if(!isset($tf['approve_by']) && !isset($tf['reject_at'])){
                    return response()->json(['status' => 'fail', 'messages' => ['Sudah ada pengajuan cuti yang dan sedang menunggu persetujuan pada tanggal '.MyHelper::indonesian_date_v2($tf['start_date'],'d F Y').' sampai '.MyHelper::indonesian_date_v2($tf['end_date'],'d F Y')]]);
                }
            }
        }

        $date_close = 0;
        $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee)->first();
        if(empty($type_shift['office_hour_type'])){
            $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
            if($setting_default){
                $type_shift = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                if(empty($type_shift)){
                    DB::rollBack();
                    return response()->json([
                        'status'=>'fail',
                        'messages'=>['Shift schedule has not been created']
                    ]);
                }
            }
        }
        $type_shift = $type_shift['office_hour_type'];

        foreach($array_dates ?? [] as $val_date){
            $closeOrHoliday = false;
            $array_date = explode('-',$val_date);
            $schedule_month = EmployeeSchedule::where('id',$employee)->where('schedule_month',$array_date[1])->where('schedule_year',$array_date[0])->first();
    
            //closed
            $outletClosed = Outlet::join('users','users.id_outlet','outlets.id_outlet')->with(['outlet_schedules'])->where('users.id',$employee)->first();
            $outletSchedule = [];
            foreach ($outletClosed['outlet_schedules'] as $s) {
                $outletSchedule[$s['day']] = [
                    'is_closed' => $s['is_closed'],
                    'time_start' => $s['open'],
                    'time_end' => $s['close'],
                ];
            }
    
            $day = date('l, F j Y', strtotime($val_date));
            $hari = MyHelper::indonesian_date_v2($val_date, 'l');
            $hari = str_replace('Jum\'at', 'Jumat', $hari);
            
            if($outletSchedule[$hari]['is_closed'] == 1){
                $date_close = $date_close + 1;
                $closeOrHoliday = true;
            }
    
            //holiday
            $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
                                ->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                                ->where('id_outlet', $office)
                                ->where(function($p1) use($val_date, $array_date) {
                                    $p1->whereDate('date_holidays.date', $val_date)
                                        ->orWhere(function($p2) use($array_date){
                                            $p2->where('holidays.yearly', '1')
                                                ->whereDay('date_holidays.date', $array_date[2])
                                                ->whereMonth('date_holidays.date', $array_date[1]);
                                        });
                                })
                                ->get()->toArray();
            if($holidays){
                $date_close = $date_close + 1;
                $closeOrHoliday = true;
            }
            
            //employee with shift
            if((!$schedule_month && $type_shift == 'Use Shift') || (isset($schedule_month['id_office_hour_shift']))){
                $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                        ->join('users','users.id','employee_schedules.id')
                                                        ->where('users.id', $employee)
                                                        ->where('employee_schedules.schedule_month', $array_date[1])
                                                        ->where('employee_schedules.schedule_year', $array_date[0])
                                                        ->whereDate('employee_schedule_dates.date', $val_date)
                                                        ->first();
                
                if(!$schedule_date && ( !$schedule_month || ($schedule_month && !$closeOrHoliday))){
                    return response()->json(['status' => 'fail', 'messages' => ['Jadwal karyawan pada tanggal ini belum dibuat']]);
                }
            }
        }
        
        $diff = strtotime($post['end_date']) - strtotime($post['start_date']);
        $diff = ($diff / 60 / 60 / 24) + 1 - $date_close;
        if($diff > 7){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Maksimal durasi cuti adalah 7 hari']
            ]); 
        }

        $data_time_off = [
            'id_employee' => $employee,
            'id_outlet'   => $office,
            'type'        => $post['type'],
            'request_by'  => $employee,
            'start_date'  => date('Y-m-d 00:00:00', strtotime($post['start_date'])),
            'end_date'    => date('Y-m-d 00:00:00', strtotime($post['end_date'])),
            'request_at'  => date('Y-m-d'),
            'notes'       => $post['notes'],
            'use_quota_time_off' => $type_time_off[$post['type']]['use_quota_time_off'] ?? 1,
        ];
        DB::beginTransaction();
        $store = EmployeeTimeOff::create($data_time_off);
        if(!$store){
            DB::rollBack();
            return response()->json([
                'status' => 'fail', 
                'messages' => ['Gagal mengajukan permintaan cuti']
            ]);
        }
        if(isset($post['attachment'])){
            $delete_image = EmployeeTimeOffImage::where('id_employee_time_off',$store['id_employee_time_off'])->delete();
    
            $files = [];
            foreach ($post['attachment'] as $i => $attachment){
                if(!empty($attachment)){
                    try{
                        $encode = base64_encode(fread(fopen($attachment, "r"), filesize($attachment)));
                    }catch(\Exception $e) {
                        DB::rollBack();
                        return response()->json(['status' => 'fail', 'messages' => ['Ukuran file lebih besar dari 2 MB']]);
                    }
                    $originalName = $attachment->getClientOriginalName();
                    if($originalName == ''){
                        $ext = 'png';
                        $name = $request->user()->name.'_'.$i;
                        $name = str_replace(' ','_',$name);
                    }else{
                        $name = pathinfo($originalName, PATHINFO_FILENAME);
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    }
                    $upload = MyHelper::uploadFile($encode, $this->time_off, $ext, date('YmdHis').'_'.$name);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $save_image = [
                            "id_employee_time_off" => $store['id_employee_time_off'],
                            "path"                 => $upload['path']
                        ];
                        $storage_image = EmployeeTimeOffImage::create($save_image);
                    }else {
                        DB::rollback();
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Gagal menyimpan file']
                        ]);
                    }
                }
            }
        }

        DB::commit();
        $user_sends = User::join('roles_features','roles_features.id_role', 'users.id_role')->where('id_feature',
        510)->get()->toArray();
        $outlet = Outlet::where('id_outlet',$office)->first();
        $employee = $request->user();
        foreach($user_sends ?? [] as $user_send){
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Employee Request Time Off',
                $user_send['phone'],
                [
                    'name_employee' => $employee['name'],
                    'phone_employee' => $employee['phone'],
                    'name_office' => $outlet['outlet_name'],
                    'time_off_date' => MyHelper::dateFormatInd($post['start_date'], true, false, false).' - '.MyHelper::dateFormatInd($post['end_date'], true, false, false),
                    'category' => 'Time Off',
                    'id_time_off' => $store['id_employee_time_off']
                ], null, false, false, 'employee'
            );
        }
        
        return response()->json(['status' => 'success', 'messages' => ['Berhasil mengajukan permintaan cuti, silahkan menunggu persetujuan']]);
    }

    public function listOvertimeEmployee(Request $request){
        $post = $request->all();
        $office = $request->user()->id_outlet;
        $overtime = EmployeeOvertime::join('users','users.id','employee_overtime.id_employee')->where('employee_overtime.id_outlet', $office)->whereMonth('date', $post['month'])->whereYear('date', $post['year'])->select('id_employee_overtime', 'name', 'date', 'notes', 'reject_at', 'approve_by')->get()->toArray();
        $overtime = array_map(function($data){
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
        },$overtime);
        
        return MyHelper::checkGet($overtime);
    }

    public function createOvertimeEmployee(Request $request){
        $post = $request->all(); 
        $office = $request->user()->id_outlet;
        $cek_feature =  $request->user()->role->roles_features->where('id_feature','513')->first();
        if($cek_feature){
            $employees = User::where('id_outlet', $office)->whereNotNull('id_role')->select('id', 'name')->get()->toArray();
        }else{
            $employees[] = [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
            ];
        }

        return MyHelper::checkGet($employees);

    }

    public function checkOvertimeEmployee(Request $request){
        $post = $request->all(); 
        $employee = $post['id_employee'];
        $office = $request->user()->id_outlet;
        $data_office = $request->user()->outlet;
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $data_office['id_city'])->first()['time_zone_utc']??null;
        $array_date = explode('-',$post['date']);
        $date = date('Y-m-d', strtotime($post['date']));

        $check = $this->checkDateOvertime($office, $post);
        if(isset($check['status']) && $check['status'] == 'success'){
            $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee)->first();
            $schedule_month = EmployeeSchedule::where('id',$employee)->where('schedule_month',$array_date[1])->where('schedule_year',$array_date[0])->first();

            if(empty($type_shift['office_hour_type'])){
                $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
                if($setting_default){
                    $type_shift = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                    if(empty($type_shift)){
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Jam kantor tidak ada ']
                        ]);
                    }
                }
            }
            $send = [
                'shift' => null,
                'schedule_in' => $type_shift['office_hour_start'] ? MyHelper::adjustTimezone($type_shift['office_hour_start'], $timeZone, 'H:i', true) : null,
                'schedule_out' => $type_shift['office_hour_end'] ? MyHelper::adjustTimezone($type_shift['office_hour_end'], $timeZone, 'H:i', true) : null,
                'list_shift' => [
                    [
                        'name' => $type_shift['office_hour_name'],
                        'selected' => true
                    ],
                ],
            ];

            //employee with shift
            if($type_shift['office_hour_type'] == 'Use Shift' || isset($schedule_month['id_office_hour_shift'])){
                $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                        ->join('users','users.id','employee_schedules.id')
                                                        ->where('users.id', $employee)
                                                        ->where('employee_schedules.schedule_month', $array_date[1])
                                                        ->where('employee_schedules.schedule_year', $array_date[0])
                                                        ->whereDate('employee_schedule_dates.date', $date)
                                                        ->first();
                if(!$schedule_date){
                    return response()->json(['status' => 'fail', 'messages' => ['Jadwal karyawan pada tanggal ini belum dibuat']]);
                }
                $list_shifts = EmployeeOfficeHourShift::where('id_employee_office_hour',$type_shift['id_employee_office_hour'])->get()->toArray();

                $send['shift'] = $schedule_date['shift'];
                $send['schedule_in'] = MyHelper::adjustTimezone(date('H:i', strtotime($schedule_date['time_start'])), $timeZone, 'H:i', true);
                $send['schedule_out'] = MyHelper::adjustTimezone(date('H:i', strtotime($schedule_date['time_end'])), $timeZone, 'H:i', true);
                $send['list_shift'] = [];
                foreach($list_shifts ?? [] as $list_shift){
                    $send['list_shift'][] = [
                        'name' => $list_shift['shift_name'],
                        'selected' => $list_shift['shift_name'] == $schedule_date['shift'] ? true : false,
                    ]; 
                }
                
            }

            return MyHelper::checkGet($send);

        }else{
            return response()->json($check);
        }
        
    } 

    public function storeOvertimeEmployee(Request $request){
        $post = $request->all(); 
        $employee = $post['id_employee'];
        $office = $request->user()->id_outlet;
        $data_office = $request->user()->outlet;
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $data_office['id_city'])->first()['time_zone_utc']??null;
        $array_date = explode('-',$post['date']);
        $date = date('Y-m-d', strtotime($post['date']));

        $check = $this->checkDateOvertime($office, $post);
        if(isset($check['status']) && $check['status'] == 'success'){
            $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee)->first();
            $schedule_month = EmployeeSchedule::where('id',$employee)->where('schedule_month',$array_date[1])->where('schedule_year',$array_date[0])->first();

            if(empty($type_shift['office_hour_type'])){
                $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
                if($setting_default){
                    $type_shift = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                    if(empty($type_shift)){
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Jam kantor tidak ada ']
                        ]);
                    }
                }
            }
            $type_shift = $type_shift['office_hour_type'];

            //employee with shift
            if($type_shift == 'Use Shift' || isset($schedule_month['id_office_hour_shift'])){
                $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                        ->join('users','users.id','employee_schedules.id')
                                                        ->where('users.id', $employee)
                                                        ->where('employee_schedules.schedule_month', $array_date[1])
                                                        ->where('employee_schedules.schedule_year', $array_date[0])
                                                        ->whereDate('employee_schedule_dates.date', $date)
                                                        ->first();
                if(!$schedule_date){
                    return response()->json(['status' => 'fail', 'messages' => ['Jadwal karyawan pada tanggal ini belum dibuat']]);
                }
            }

            //duration
            $new_time =  $this->getDuration($post['end_time_off'],$post['start_time_off']);

            //rest
            if(isset($post['start_rest']) && isset($post['end_rest'])){
                $new_time_rest =  $this->getDuration($post['end_rest'],$post['start_rest']);

                //duration - rest
                $new_time =  $this->getDuration($new_time,$new_time_rest);
            }

            $data_overtime = [
                'id_employee' => $employee,
                'id_assign'   => $request->user()->id,  
                'id_outlet'   => $office,
                'request_by'  => $request->user()->id,
                'date'        => $date,
                'time'        => $post['time'],
                'duration'    => $new_time,
                'rest_before' => $post['start_rest'] ? MyHelper::reverseAdjustTimezone(date('H:i:s', strtotime($post['start_rest'])), $timeZone, 'Y-m-d H:i:s', true) : null,
                'rest_after'  => $post['end_rest'] ? MyHelper::reverseAdjustTimezone(date('H:i:s', strtotime($post['end_rest'])), $timeZone, 'Y-m-d H:i:s', true) : null,
                'request_at'  => date('Y-m-d'),
                'notes'       => $post['notes']
            ];

            if(!isset($data_overtime['notes']) && empty($data_overtime['notes'])){
                return response()->json(['status' => 'fail', 'messages' => ['Keterangan wajib diisi']]);
            }

            DB::beginTransaction();
            $store = EmployeeOvertime::create($data_overtime);
            
            if(!$store){
                DB::rollBack();
                return response()->json([
                    'status' => 'fail', 
                    'messages' => ['Gagal mengajukan permintaan lembur']
                ]);
            }

            DB::commit();
            $user_sends = User::join('roles_features','roles_features.id_role', 'users.id_role')->where('id_feature',
            514)->get()->toArray();
            $employee_overtime = User::where('id',$data_overtime['id_employee'])->first();
            foreach($user_sends ?? [] as $user_send){
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Employee Request Overtime',
                    $user_send['phone'],
                    [
                        'name_employee' => $employee_overtime['name'],
                        'phone_employee' => $employee_overtime['phone'],
                        'name_assign' => $request->user()->name,
                        'name_office' => $data_office['outlet_name'],
                        'overtime_date' => MyHelper::dateFormatInd($data_overtime['date'], true, false, false),
                        'start_overtime' => date('H:i', strtotime($post['start_time_off'])),
                        'end_overtime' => date('H:i', strtotime($post['end_time_off'])),
                        'category' => 'Overtime',
                        'id_overtime' => $store['id_employee_overtime']
                    ], null, false, false, 'employee'
                );
            }
            if($data_overtime['id_employee'] != $data_overtime['id_assign']){
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Employee Request Overtime Assign to Other Employee',
                    $employee_overtime['phone'],
                    [
                        'name_office' => $data_office['outlet_name'],
                        'overtime_date' => MyHelper::dateFormatInd($data_overtime['date'], true, false, false),
                        'name_assign' => $request->user()->name,
                        'category' => 'Overtime',
                    ], null, false, false, 'employee'
                );
            }
            return response()->json(['status' => 'success', 'messages' => ['Berhasil mengajukan permintaan lembur, silahkan menunggu persetujuan']]);
        }else{
            return response()->json($check);
        }
    }

    public function getDuration($start_time, $end_time){
        $duration = strtotime($end_time);
        $start = strtotime($start_time);
        $diff = $start - $duration;
        $hour = floor($diff / (60*60));
        $minute = floor(($diff - ($hour*60*60))/(60));
        $second = floor(($diff - ($hour*60*60))%(60));
        return $new_time =  date('H:i:s', strtotime($hour.':'.$minute.':'.$second));
    }

    public function checkDateOvertime($office, $data){
        $employee = $data['id_employee'];
        $array_date = explode('-',$data['date']);
        $date = date('Y-m-d', strtotime($data['date']));

        //cek date
        if($date < date('Y-m-d')){
            return ['status' => 'fail', 'messages' => ['Minimal tanggal pengajuan lembur adalah hari ini']];
        }

        //cekavail
        $notAvail = EmployeeNotAvailable::join('employee_time_off', 'employee_time_off.id_employee_time_off', 'employee_not_available.id_employee_time_off')->where('employee_not_available.id_employee',$employee)->where('employee_not_available.id_outlet', $office)->whereDate('employee_time_off.start_date','<=' ,$date)->whereDate('employee_time_off.end_date','>=' ,$date)->first();
        if($notAvail){
            return ['status' => 'fail', 'messages' => ['Karyawan akan mengambil cuti pada tanggal ini']];
        }
        
        //cek_overtime
        $overtime = EmployeeOvertime::where('id_employee',$employee)->where('id_outlet',$office)->whereDate('date', $date)->get()->toArray();
        if($overtime){
            //disetujui tdk bisa lagi mengajukan
            foreach($overtime as $ovt){
                if(isset($ovt['approve_by']) && !isset($ovt['reject_at'])){
                    return ['status' => 'fail', 'messages' => ['Sudah ada pengajuan cuti yang disetujui pada tanggal ini']];
                }
            }
            //pending
            foreach($overtime as $ovt){
                if(!isset($ovt['approve_by']) && !isset($ovt['reject_at'])){
                    return ['status' => 'fail', 'messages' => ['Sudah ada pengajuan cuti yang dan sedang menunggu persetujuan']];
                }
            }
        }

        //closed
        $outletClosed = Outlet::join('users','users.id_outlet','outlets.id_outlet')->with(['outlet_schedules'])->where('users.id',$employee)->first();
        $outletSchedule = [];
        foreach ($outletClosed['outlet_schedules'] as $s) {
            $outletSchedule[$s['day']] = [
                'is_closed' => $s['is_closed'],
                'time_start' => $s['open'],
                'time_end' => $s['close'],
            ];
        }
        
        $day = date('l, F j Y', strtotime($date));
        $hari = MyHelper::indonesian_date_v2($date, 'l');
        $hari = str_replace('Jum\'at', 'Jumat', $hari);
        
        if($outletSchedule[$hari]['is_closed'] == 1){
            return ['status' => 'fail', 'messages' => ['Kantor tutup pada tanggal ini']];
        }

        //holiday
        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
                            ->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                            ->where('id_outlet', $office)
                            ->where(function($p1) use($date, $array_date) {
                                $p1->whereDate('date_holidays.date', $date)
                                    ->orWhere(function($p2) use($array_date){
                                        $p2->where('holidays.yearly', '1')
                                            ->whereDay('date_holidays.date', $array_date[2])
                                            ->whereMonth('date_holidays.date', $array_date[1]);
                                    });
                            })
                            ->get()->toArray();
        if($holidays){
            return ['status' => 'fail', 'messages' => ['Kantor libur pada tanggal ini']];
        }
        
        return ['status' => 'success', 'date' => $outletSchedule[$hari]];
    }

    public function listOvertime(Request $request)
    {
        $post = $request->all();
        $overtime = EmployeeOvertime::join('users as employees','employees.id','=','employee_overtime.id_employee')
                    ->join('outlets', 'outlets.id_outlet', '=', 'employee_overtime.id_outlet')
                    ->join('users as requests', 'requests.id', '=', 'employee_overtime.request_by')
                    ->select(
                        'employee_overtime.*',
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
                            $overtime = $overtime->where($subject, $condition['parameter']);
                        }else{
                            $overtime = $overtime->where($subject, 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $overtime = $overtime->where(function ($q) use ($post){
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
                $order = 'employee_overtime.created_at';
            }
            if(isset($post['page'])){
                $overtime = $overtime->orderBy($order, $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $overtime = $overtime->orderBy($order, $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $overtime = $overtime->orderBy('employee_overtime.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $overtime = $overtime->orderBy('employee_overtime.created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($overtime);
    }

    public function detailOvertime(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_employee_overtime']) && !empty($post['id_employee_overtime'])){
            $time_off = EmployeeOvertime::where('id_employee_overtime', $post['id_employee_overtime'])->with(['employee.employee','outlet','approve','request','documents'])->first();
            $data_outlet = Outlet::where('id_outlet', $time_off['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;
            $date = date('Y-m-d', strtotime($time_off['date']));
            $array_date = explode('-', $date);
            //
            $cek_employee = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$time_off['id_employee'])->first();
            if(empty($cek_employee['office_hour_type'])){
                $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
                if($setting_default){
                    $old_data = $cek_employee;
                    $cek_employee = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                }
            }
            if($cek_employee['office_hour_type'] == 'Without Shift'){
                $schedule_date_without = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                    ->join('users','users.id','employee_schedules.id')
                                    ->where('users.id', $time_off['id_employee'])
                                    ->where('employee_schedules.schedule_month', $array_date[1])
                                    ->where('employee_schedules.schedule_year', $array_date[0])
                                    ->whereDate('employee_schedule_dates.date', $date)
                                    ->first();
                if($schedule_date_without){ 
                    $send['schedule_in'] = date('H:i', strtotime($schedule_date_without['time_start']));
                    $send['schedule_out'] = date('H:i', strtotime($schedule_date_without['time_end']));
                }else{
                    $send['schedule_in'] = MyHelper::reverseAdjustTimezone(date('H:i', strtotime($cek_employee['office_hour_start'])), $timeZone, 'H:i');
                    $send['schedule_out'] = MyHelper::reverseAdjustTimezone(date('H:i', strtotime($cek_employee['office_hour_end'])), $timeZone, 'H:i');
                }
            }else{
                $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                        ->join('users','users.id','employee_schedules.id')
                                                        ->where('users.id', $time_off['id_employee'])
                                                        ->where('employee_schedules.schedule_month', $array_date[1])
                                                        ->where('employee_schedules.schedule_year', $array_date[0])
                                                        ->whereDate('employee_schedule_dates.date', $date)
                                                        ->first();

                $send['schedule_in'] = date('H:i', strtotime($schedule_date['time_start']));
                $send['schedule_out'] = date('H:i', strtotime($schedule_date['time_end']));
            }
            
            $time_off['schedule_in'] = $send['schedule_in'] ? MyHelper::adjustTimezone($send['schedule_in'], $timeZone, 'H:i') : null;
            $time_off['schedule_out'] = $send['schedule_out'] ? MyHelper::adjustTimezone($send['schedule_out'], $timeZone, 'H:i') : null;
            $time_off['rest_before'] = $time_off['rest_before'] ? MyHelper::adjustTimezone($time_off['rest_before'], $timeZone, 'H:i') : null;
            $time_off['rest_after'] = $time_off['rest_after'] ? MyHelper::adjustTimezone($time_off['rest_after'], $timeZone, 'H:i') : null;
            
            $duration_time = $time_off['duration'];
            if(isset($time_off['rest_before']) && isset($time_off['rest_after'])){
                $duration_rest = strtotime($time_off['rest_before']);
                $start_rest = strtotime($time_off['rest_after']);
                $diff_rest = $start_rest - $duration_rest;
                $hour_rest = floor($diff_rest / (60*60));
                $minute_rest = floor(($diff_rest - ($hour_rest*60*60))/(60));
                $new_time_rest =  date('H:i', strtotime($hour_rest.':'.$minute_rest));
                $secs = strtotime($new_time_rest)-strtotime("00:00:00");
                $duration_time = date("H:i:s",strtotime($time_off['duration'])+$secs);
            }

            if(isset($time_off['approve_by']) or isset($time_off['reject_at'])){
                if($time_off['time']=='after'){
                    $duration = strtotime($duration_time);
                    $start = strtotime($time_off['schedule_out']);
                    $diff = $start - $duration;
                    $hour = floor($diff / (60*60));
                    $minute = floor(($diff - ($hour*60*60))/(60));
                    $second = floor(($diff - ($hour*60*60))%(60));
                    $new_time =  date('H:i', strtotime($hour.':'.$minute.':'.$second));
                    $time_off['schedule_out'] = $new_time;
                }elseif($time_off['time']=='before'){
                    $secs = strtotime($duration_time)-strtotime("00:00:00");
                    $new_time = date("H:i:s",strtotime($time_off['schedule_in'])+$secs);
                    $time_off['schedule_in'] = $new_time;
                }else{
                    return false;
                }
            }

            if($time_off['time']=='before'){
                $duration = strtotime($duration_time);
                $start = strtotime($time_off['schedule_in']);
                $diff = $start - $duration;
                $hour = floor($diff / (60*60));
                $minute = floor(($diff - ($hour*60*60))/(60));
                $second = floor(($diff - ($hour*60*60))%(60));
                $new_time =  date('H:i', strtotime($hour.':'.$minute.':'.$second));
                $time_off['start_overtime'] = $new_time;
                $time_off['end_overtime'] = $time_off['schedule_in'];
            }elseif($time_off['time']=='after'){
                $secs = strtotime($duration_time)-strtotime("00:00:00");
                $new_time = date("H:i:s",strtotime($time_off['schedule_out'])+$secs);
                $time_off['start_overtime'] = $time_off['schedule_out'];
                $time_off['end_overtime'] = $new_time;
            }else{
                return false;
            }

            if($time_off==null){
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => 'Empty',
                ]]);
            } else {
                $time_zone = [
                    '7' => 'WIB',
                    '8' => 'WITA',
                    '9' => 'WIT'
                ];
                $time_off['time_zone'] = $time_zone[$timeZone];
                return response()->json(['status' => 'success', 'result' => [
                    'time_off' => $time_off,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function updateOvertime(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_employee_overtime']) && !empty($post['id_employee_overtime'])){
            $data_update = [];
            $duration = '';
            $approve_by = null;
            if(isset($post['id_approve']) || (isset($post['type']) && $post['type'] == 'HRGA Approved')){
                $get_data = EmployeeOvertime::where('id_employee_overtime',$post['id_employee_overtime'])->first();
                $post['id_employee'] = $get_data['id_employee'];
                $post['date'] = date('Y-m-d', strtotime($get_data['date']));
                $duration = $get_data['duration'];
                if(isset($post['id_approve'])){
                    $approve_by = User::where('id',$post['id_approve'])->first() ?? null;
                }
            }
            if(isset($post['id_employee'])){
                $data_update['id_employee'] = $post['id_employee'];
            }
            if(isset($post['id_outlet'])){
                $data_update['id_outlet'] = $post['id_outlet'];
            }else{
                $data_update['id_outlet'] = $get_data['id_outlet'];
            }
            $data_outlet = Outlet::where('id_outlet', $data_update['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $data_outlet['id_city'])->first()['time_zone_utc']??null;
            if(isset($post['date'])){
                $data_update['date'] = $post['date'];
            }
            if(isset($post['time'])){
                $data_update['time'] =$post['time'];
                //getduration
                if($post['time'] == 'before'){
                    $duration = $this->getDuration($post['schedule_in'],$post['time_start_overtime']);
                }elseif($post['time'] == 'after'){
                    $duration = $this->getDuration($post['time_end_overtime'],$post['schedule_out']);
                }
            }
            
            if(isset($post['rest_before']) && date('H:i',strtotime($post['rest_before']))=='00:00'){
                $post['rest_before'] = null;
            }

            if(isset($post['rest_after']) && date('H:i',strtotime($post['rest_after']))=='00:00'){
                $post['rest_after'] = null;
            }
            
            if(isset($post['rest_before']) && isset($post['rest_after'])){
                $data_update['rest_before'] = MyHelper::reverseAdjustTimezone(date("H:i:s",strtotime($post['rest_before'])), $timeZone, 'H:i:s', true);
                $data_update['rest_after'] = MyHelper::reverseAdjustTimezone(date("H:i:s",strtotime($post['rest_after'])), $timeZone, 'H:i:s', true);
                $duration_rest = $this->getDuration($data_update['rest_after'],$data_update['rest_before']);
                $duration = $this->getDuration($duration,$duration_rest);
            }
            
            $data_update['duration'] = $duration;
            if(isset($post['id_approve']) || (isset($post['type']) && $post['type'] == 'HRGA Approved')){
                $data_update['time'] = $get_data['time'];
                if(isset($get_data['rest_before']) && isset($get_data['rest_after'])){
                    $duration_rest = $this->getDuration($get_data['rest_after'],$get_data['rest_before']);
                    $secs_dr = strtotime($duration_rest)-strtotime("00:00:00");
                    $duration = date("H:i",strtotime($duration)+$secs_dr);
                }
                if($data_update['time']=='before'){
                    $duration = strtotime($duration);
                    $start = strtotime($post['schedule_in']);
                    $diff = $start - $duration;
                    $hour = floor($diff / (60*60));
                    $minute = floor(($diff - ($hour*60*60))/(60));
                    $post['time_start_overtime'] = $new_time =  date('H:i', strtotime($hour.':'.$minute));
                }elseif($data_update['time'] == 'after'){
                    $secs = strtotime($duration)-strtotime("00:00:00");
                    $post['time_end_overtime'] = $new_time = date("H:i",strtotime($post['schedule_out'])+$secs);
                }

            }
            if(isset($post['type'])){
                $data_update['status'] = $post['type'];
                if($post['type'] == 'HRGA Approved'){
                    $post['approve'] = true;
                }
            }
            if(isset($post['approve'])){
                $data_update['approve_by'] = $post['id_approve'] ?? auth()->user()->id;
                $data_update['approve_at'] = date('Y-m-d');
            }
            if(isset($post['approve_notes'])){
                $data_update['approve_notes'] = $post['approve_notes'];
            }
            
            if($data_update){
                DB::beginTransaction();
                $update = EmployeeOvertime::where('id_employee_overtime',$post['id_employee_overtime'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'fail', 
                        'messages' => ['Failed to updated a request employee overtime']
                    ]);
                }
                 //doc
                $dataDoc = [
                    'id_user_approved' => $post['id_approve'] ?? auth()->user()->id,
                    'date' => date('Y-m-d')
                ];
                if(isset($post['notes'])){
                    $dataDoc['notes'] = $post['notes'];
                }
                if (isset($post['attachment']) && !empty($post['attachment'])) {
                    $upload = MyHelper::uploadFile($post['attachment'], $this->overtime_approve, 'pdf');
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $dataDoc['attachment'] = $upload['path'];
                    } else {
                        $result = [
                            'error'    => 1,
                            'status'   => 'fail',
                            'messages' => ['fail upload file']
                        ];
                        return $result;
                    }
                }
                $storeDoc = EmployeeOvertimeDocument::updateOrCreate(['id_employee_overtime' => $post['id_employee_overtime'], 'type' => $post['type']],$dataDoc);
                if(!$storeDoc){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request employee time off']
                    ]);
                }
                if(isset($post['approve'])){
                    $update_schedule = $this->updatedScheduleOvertime($data_update,$post,$timeZone);
                    if(!$update_schedule){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'fail', 
                            'messages' => ['Failed to updated a request employee overtime']
                        ]);
                    }
                    $user_assign = User::join('employee_overtime','employee_overtime.id_assign','users.id')->where('employee_overtime.id_employee_overtime',$post['id_employee_overtime'])->first();
                    $employee_overtime = User::where('id',$user_assign['id_employee'])->first();
                    $office = Outlet::where('id_outlet',$employee_overtime['id_outlet'])->first();
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Overtime Approved', 
                            $user_assign['phone'] ?? null,
                            [
                                'user_update'=> $approve_by ? $approve_by['name'] : $request->user()->name,
                                'overtime_date' => MyHelper::dateFormatInd($data_update['date'], true, false, false),
                                'name_employee' => $employee_overtime['name'],
                                'name_office' => $office['outlet_name'],
                                'category' => 'Overtime',
                            ], null, false, false, $recipient_type = 'employee', null, true
                        );
                        if (!$autocrm) {
                            DB::rollBack();
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                    if($user_assign['id'] != $employee_overtime['id']){
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Overtime Assign to Other Employee Approved',
                            $employee_overtime['phone'],
                            [
                                'name_office' => $office['outlet_name'],
                                'overtime_date' => MyHelper::dateFormatInd($data_update['date'], true, false, false),
                                'name_assign' => $user_assign['name'],
                                'user_update' => $approve_by ? $approve_by['name'] : $request->user()->name,
                                'category' => 'Overtime',
                            ], null, false, false, 'employee'
                        );
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

    public function updatedScheduleOvertime($data,$data2,$timeZone){
        //get schedule
        $month_sc = date('m', strtotime($data['date']));
        $year_sc = date('Y', strtotime($data['date']));
        $cek_employee = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$data['id_employee'])->first();
        if(empty($cek_employee['office_hour_type'])){
            $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
            if($setting_default){
                $cek_employee = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
            }
        }
        $get_schedule = EmployeeSchedule::where('id', $data['id_employee'])->where('schedule_month', $month_sc)->where('schedule_year',$year_sc)->first();
        if($get_schedule){
            $get_schedule_date = EmployeeScheduleDate::where('id_employee_schedule',$get_schedule['id_employee_schedule'])->where('date',$data['date'])->first();
            if($get_schedule_date){
                //update
                if($data['time']=='before'){
                    $order = 'time_start';
                    $new_time = MyHelper::reverseAdjustTimezone($data2['time_start_overtime'], $timeZone, 'H:i:s', true);
                }elseif($data['time']=='after'){
                    $order = 'time_end';
                    $new_time = MyHelper::reverseAdjustTimezone($data2['time_end_overtime'], $timeZone, 'H:i:s', true);
                }else{
                    return false;
                }

                $update_date = EmployeeScheduleDate::where('id_employee_schedule_date',$get_schedule_date['id_employee_schedule_date'])->update([$order => $new_time,  'is_overtime' => 1]);
                if($update_date){
                    return true;
                }
            }elseif($cek_employee['office_hour_type'] == 'Without Shift'){
                if($data['time']=='before'){
                    $time_start = MyHelper::reverseAdjustTimezone($data2['time_start_overtime'], $timeZone, 'H:i:s', true);
                    $time_end = MyHelper::reverseAdjustTimezone($data2['schedule_out'], $timeZone, 'H:i:s', true);
                }elseif($data['time']=='after'){
                    $time_start = MyHelper::reverseAdjustTimezone($data2['schedule_in'], $timeZone, 'H:i:s', true);
                    $time_end = MyHelper::reverseAdjustTimezone($data2['time_end_overtime'], $timeZone, 'H:i:s', true);
                }else{
                    return false;
                }
                $schdule_date = EmployeeScheduleDate::updateOrCreate([
                    'id_employee_schedule' => $get_schedule['id_employee_schedule'],
                    'date' => $data['date'],
                ],[
                    'is_overtime' => 1,
                    'time_start' => $time_start,
                    'time_end' => $time_end,
                ]);
                if($schdule_date){
                    return true;
                }
            }
        }else{
            if($cek_employee['office_hour_type'] == 'Without Shift'){
                $schdule = EmployeeSchedule::create([
                    'id' => $data['id_employee'],
                    'id_outlet' => $data['id_outlet'],
                    'schedule_month' => $month_sc,
                    'schedule_year' => $year_sc,
                    'request_at' => date('Y-m-d H:i:s')
                ]);
                if($schdule){
                    if($data['time']=='before'){
                        $time_start = MyHelper::reverseAdjustTimezone($data2['time_start_overtime'], $timeZone, 'H:i:s', true);
                        $time_end = MyHelper::reverseAdjustTimezone($data2['schedule_out'], $timeZone, 'H:i:s', true);
                    }elseif($data['time']=='after'){
                        $time_start = MyHelper::reverseAdjustTimezone($data2['schedule_in'], $timeZone, 'H:i:s', true);
                        $time_end = MyHelper::reverseAdjustTimezone($data2['time_end_overtime'], $timeZone, 'H:i:s', true);
                    }else{
                        return false;
                    }
                    $schdule_date = EmployeeScheduleDate::updateOrCreate([
                        'id_employee_schedule' => $schdule['id_employee_schedule'],
                        'date' => $data['date'],
                    ],[
                        'is_overtime' => 1,
                        'time_start' => $time_start,
                        'time_end' => $time_end,
                    ]);
                    if($schdule_date){
                        return true;
                    }
                }
            }
        }
        return false;
    }

    public function rejectOvertime(Request $request){
        $post = $request->all();
        if($post['type'] == 'HRGA Approved'){
            $updateData = [
                'status' => 'Pending'
            ];
        }elseif($post['type'] == 'Manager Approved'){
            return $this->deleteOvertime(New Request(['id_employee_overtime'=>$post['id_employee_overtime'],'id_approve' => $request->user()->id]));
        }
        $update = EmployeeOvertime::where('id_employee_overtime', $post['id_employee_overtime'])->update($updateData);
        if($update){
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function deleteOvertime(Request $request){
        $post = $request->all();
        $check = EmployeeOvertime::where('id_employee_overtime', $post['id_employee_overtime'])->first();
        if($check){
            DB::beginTransaction();
            $month_sc = date('m', strtotime($check['date']));
            $year_sc = date('Y', strtotime($check['date']));
            $reject_by = null;
            if(isset($post['id_approve']) && !empty($post['id_approve'])){
                $reject_by = User::where('id',$post['id_approve'])->first() ?? null;
            }
            $get_schedule = EmployeeSchedule::where('id', $check['id_employee'])->where('schedule_month', $month_sc)->where('schedule_year',$year_sc)->first();
            if($get_schedule){
                $get_schedule_date = EmployeeScheduleDate::where('id_employee_schedule',$get_schedule['id_employee_schedule'])->where('date',$check['date'])->first();
                if($get_schedule_date){
                    $duration = date("H:i:s",strtotime($check['duration']));
                    if(isset($check['rest_before']) && isset($check['rest_after'])){
                        $duration_rest = strtotime($check['rest_before']);
                        $start_rest = strtotime($check['rest_after']);
                        $diff_rest = $start_rest - $duration_rest;
                        $hour_rest = floor($diff_rest / (60*60));
                        $minute_rest = floor(($diff_rest - ($hour_rest*60*60))/(60));
                        $new_time_rest =  date('H:i', strtotime($hour_rest.':'.$minute_rest));
                        $secs = strtotime($new_time_rest)-strtotime("00:00:00");
                        $duration = date("H:i:s",strtotime($check['duration'])+$secs);
                    }
                    
                    if($check['time'] == 'after'){
                        $duration = strtotime($duration);
                        $start = strtotime($get_schedule_date['time_end']);
                        $diff = $start - $duration;
                        $hour = floor($diff / (60*60));
                        $minute = floor(($diff - ($hour*60*60))/(60));
                        $second = floor(($diff - ($hour*60*60))%(60));
                        $new_time =  date('H:i:s', strtotime($hour.':'.$minute.':'.$second));
                        $order = 'time_end';
                        $order_att = 'clock_out_requirement';
                    }elseif($check['time'] = 'before'){
                        $secs = strtotime($duration)-strtotime("00:00:00");
                        $new_time = date("H:i:s",strtotime($get_schedule_date['time_start'])+$secs);
                        $order = 'time_start';
                        $order_att = 'clock_in_requirement';
                    }

                    //check anothet ovt
                    $check_another = EmployeeOvertime::where('id_employee_overtime', '<>',$post['id_employee_overtime'])
                    ->where('id_employee',$check['id_employee'])
                    ->whereDate('date',$get_schedule_date['date'])
                    ->WhereNotNull('approve_at')->whereNotNull('approve_by')->whereNull('reject_at')
                    ->get()->toArray();

                    if($check_another){
                        $is_overtime = 1;
                    }else{
                        $is_overtime = 0;
                    }

                    if($get_schedule_date['is_overtime']==1){
                        $update_schedule = EmployeeScheduleDate::where('id_employee_schedule_date',$get_schedule_date['id_employee_schedule_date'])->update([$order => $new_time,  'is_overtime' => $is_overtime]);
                        if(!$update_schedule){
                            DB::rollBack();
                            return response()->json([
                                'status' => 'fail'
                            ]);
                        }
                    }
                   
                    
                    $attendance = EmployeeAttendance::where('id_employee_schedule_date',$get_schedule_date['id_employee_schedule_date'])->where('id', $check['id_employee'])->where('attendance_date',$check['date'])->update([$order_att => $new_time]);

                }
                $update = ['reject_at' => date('Y-m-d')];
                if(isset($post['approve_notes']) && !empty($post['approve_notes'])){
                    $update['approve_notes'] = $post['approve_notes'];
                }
                $update_overtime = EmployeeOvertime::where('id_employee_overtime', $post['id_employee_overtime'])->update($update);
                if(!$update_overtime){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'fail'
                    ]);
                }
                $user_assign = User::where('id',$check['id_assign'])->first();
                $employee_overtime = User::where('id',$check['id_employee'])->first();
                $office = Outlet::where('id_outlet',$employee_overtime['id_outlet'])->first();
                if (\Module::collections()->has('Autocrm')) {
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Employee Request Overtime Rejected', 
                        $user_assign['phone'] ?? null,
                        [
                            'user_update'=> $reject_by ? $reject_by['name'] : $request->user()->name,
                            'overtime_date' => MyHelper::dateFormatInd($check['date'], true, false, false),
                            'name_employee' => $employee_overtime['name'],
                            'name_office' => $office['outlet_name'],
                            'category' => 'Overtime',
                        ], null, false, false, $recipient_type = 'employee', null, true
                    );
                    if (!$autocrm) {
                        DB::rollBack();
                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Failed to send']
                        ]);
                    }
                }
                if($user_assign['id'] != $employee_overtime['id']){
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Employee Request Overtime Assign to Other Employee Rejeted',
                        $employee_overtime['phone'],
                        [
                            'name_office' => $office['outlet_name'],
                            'overtime_date' => MyHelper::dateFormatInd($check['date'], true, false, false),
                            'name_assign' => $user_assign['name'],
                            'user_update' => $reject_by ? $reject_by['name'] : $request->user()->name,
                            'category' => 'Overtime',
                        ], null, false, false, 'employee'
                    );
                }
                DB::commit();
                return response()->json([
                    'status' => 'success'
                ]);
            }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function checkTimeOffOvertime(){
        $log = MyHelper::logCron('Check Request Employee Time Off, Overtime, Change Shift');
        try{
            DB::beginTransaction();
            $data_time_off = EmployeeTimeOff::whereNull('reject_at')->whereNull('approve_at')->whereDate('request_at','<',date('Y-m-d'))->get()->toArray();
            if($data_time_off){
                foreach($data_time_off as $time_off){
                    $update = EmployeeTimeOff::where('id_employee_time_off', $time_off['id_employee_time_off'])->update(['reject_at' => date('Y-m-d')]);
                    $user_employee = User::join('employee_time_off','employee_time_off.id_employee','users.id')->where('employee_time_off.id_employee_time_off',$time_off['id_employee_time_off'])->first();
                    $office = Outlet::where('id_outlet',$user_employee['id_outlet'])->first();
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Time Off Rejected', 
                            $user_employee['phone'] ?? null,
                            [
                                'user_update'=>'Admin',
                                'time_off_date'=> MyHelper::dateFormatInd($time_off['start_date'], true, false, false).' - '.MyHelper::dateFormatInd($time_off['end_date'], true, false, false),
                                'name_office'=> $office['outlet_name'],
                                'category' => 'Time Off',
                            ], null, false, false, $recipient_type = 'employee', null, true
                        );
                        if (!$autocrm) {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                }
            }

            $data_overtime = EmployeeOvertime::whereNull('reject_at')->whereNull('approve_at')->whereDate('request_at','<',date('Y-m-d'))->get()->toArray();
            if($data_overtime){
                foreach($data_overtime as $overtime){
                    $update = EmployeeOvertime::where('id_employee_overtime', $overtime['id_employee_overtime'])->update(['reject_at' => date('Y-m-d')]);
                    $user_assign = User::join('employee_overtime','employee_overtime.id_assign','users.id')->where('employee_overtime.id_employee_overtime',$overtime['id_employee_overtime'])->first();
                    $employee_overtime = User::where('id',$user_assign['id_employee'])->first();
                    $office = Outlet::where('id_outlet',$employee_overtime['id_outlet'])->first();
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Overtime Rejected', 
                            $user_assign['phone'] ?? null,
                            [
                                'user_update'=>'Admin',
                                'overtime_date' => MyHelper::dateFormatInd($overtime['date'], true, false, false),
                                'name_employee' => $employee_overtime['name'],
                                'name_office' => $office['outlet_name'],
                                'category' => 'Overtime',
                            ], null, false, false, $recipient_type = 'employee', null, true
                        );
                        if (!$autocrm) {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                    if($user_assign['id'] != $employee_overtime['id']){
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Overtime Assign to Other Employee Rejeted',
                            $employee_overtime['phone'],
                            [
                                'name_office' => $office['outlet_name'],
                                'overtime_date' => MyHelper::dateFormatInd($overtime['date'], true, false, false),
                                'name_assign' => $user_assign['name'],
                                'user_update' => 'Admin',
                                'category' => 'Overtime',
                            ], null, false, false, 'employee'
                        );
                    }
                }
            }

            $data_changeshift = EmployeeChangeShift::where('status','Pending')->whereDate('created_at','<',date('Y-m-d'))->get()->toArray();
            if($data_changeshift){
                foreach($data_changeshift as $changeshift){
                    $update = EmployeeChangeShift::where('id_employee_change_shift', $changeshift['id_employee_change_shift'])->update(['status'=>'Rejected']);
                    $user_employee = User::join('employee_change_shifts','employee_change_shifts.id_user','users.id')->where('employee_change_shifts.id_employee_change_shift',$changeshift['id_employee_change_shift'])->first();
                    $office = Outlet::where('id_outlet',$user_employee['id_outlet'])->first();
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Change Shift Rejected', 
                            $user_employee['phone'] ?? null,
                            [
                                'user_update'=> 'Admin',
                                'change_shift_date'=> MyHelper::dateFormatInd($changeshift['change_shift_date'], true, false, false),
                                'name_office'=> $office['outlet_name'],
                                'category' => 'Change Shift',
                            ], null, false, false, $recipient_type = 'employee', null, true
                        );
                        if (!$autocrm) {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            $log->success('success');
            return response()->json(['status' => 'success']);

        }catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        }    
    }

}