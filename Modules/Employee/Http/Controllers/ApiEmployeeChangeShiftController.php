<?php

namespace Modules\Employee\Http\Controllers;


use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use App\Http\Models\User;
use App\Http\Models\Outlet;
use App\Http\Models\Province;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Holiday;
use Modules\Employee\Entities\EmployeeSchedule;
use Modules\Employee\Entities\EmployeeAttendance;
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use Modules\Employee\Entities\EmployeeOvertime;
use Modules\Users\Entities\Role;
use App\Http\Models\Setting;

use DB;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Entities\EmployeeChangeShift;
use Modules\Employee\Entities\EmployeeOfficeHour;

class ApiEmployeeChangeShiftController extends Controller
{
    public function __construct()
    {
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    public function index(Request $request){
        $post = $request->all();

        $employee = $request->user()->role()->first();
        $outlet = $request->user()->outlet()->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        if(isset($employee['id_employee_office_hour'])){
            $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$employee['id_employee_office_hour'])->first();
        }else{
            $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
            if($setting_default){
                $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$setting_default['value'])->first();
                if(empty($office_hour)){
                    return response()->json([
                        'status'=>'fail',
                        'messages'=>['Terjadi kesalahan']
                    ]);
                }
            }
        }
        
        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];

        $change = false;
        $shift = MyHelper::adjustTimezone($office_hour['office_hour_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($office_hour['office_hour_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone];
        $schedule = EmployeeSchedule::where('schedule_month',date('m'))
        ->where('schedule_year', date('Y'))
        ->where('id',$request->user()->id)
        ->first();
        if(($office_hour['office_hour_type'] == 'Use Shift' && isset($office_hour['office_hour_shift'])) || isset($schedule['id_office_hour_shift'])){
            $schedule_date = EmployeeScheduleDate::where('id_employee_schedule',$schedule['id_employee_schedule'])
            ->whereDate('date',date('Y-m-d'))
            ->first();
            $change = true;
            $shift = MyHelper::adjustTimezone($schedule_date['time_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($schedule_date['time_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone];
        }
        $data = [
            'type_shift' => $office_hour['office_hour_name'],
            'shift' => $shift,
            'change_shift' => $change,
        ];
        return response()->json([
            'status' => 'success', 
            'result' => $data
        ]);
    }

    public function sendDate(Request $request){
        $post = $request->all();
        $employee = $request->user()->role()->first();

        $schedule = EmployeeSchedule::where('schedule_month',date('m',strtotime($post['date'])))
        ->where('schedule_year', date('Y',strtotime($post['date'])))
        ->where('id',$request->user()->id)
        ->first();
        if(!$schedule){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Jadwal pada bulan tersebut belum dibuat']
            ]);
        }
        $schedule_date = EmployeeScheduleDate::where('id_employee_schedule',$schedule['id_employee_schedule'])
        ->whereDate('date',date('Y-m-d',strtotime($post['date'])))
        ->first();
        if(!$schedule_date){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Jadwal pada tanggal tersebut belum dibuat']
            ]);
        }

        $outlet = $request->user()->outlet()->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];
        $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$schedule['id_office_hour_shift'])->first();
        $data = [];
        foreach($office_hour['office_hour_shift'] ?? [] as $key => $office_hour_shift){
            $data[] = [
                'name' => $office_hour['office_hour_name'].' '.'('.$office_hour_shift['shift_name'].')',
                'shift' => MyHelper::adjustTimezone($office_hour_shift['shift_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($office_hour_shift['shift_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone],
                'id_employee_office_hour_shift' => $office_hour_shift['id_employee_office_hour_shift']
            ];
        }
        $data;
        return response()->json([
            'status' => 'success', 
            'result' => $data
        ]);
    }

    public function create(Request $request){
        $request->validate([
            'date' => 'date|required',
            'id_employee_office_hour_shift' => 'numeric|required',
            'reason' => 'string|required',
        ]);
        $post = $request->all();
        $office = $request->user()->outlet()->first();
        $user_employee = $request->user();
        $employee = $request->user()->role()->first();
        
        if(strtotime($post['date'])<strtotime(date('Y-m-d'))){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Minimal pengajuan ganti shift adalah hari ini']
            ]);
        }
        
        $schedule_date = EmployeeSchedule::join('employee_schedule_dates','employee_schedule_dates.id_employee_schedule','employee_schedules.id_employee_schedule')
        ->where('schedule_month',date('m',strtotime($post['date'])))
        ->where('schedule_year', date('Y',strtotime($post['date'])))
        ->Where('id',$request->user()->id)
        ->whereDate('date',date('Y-m-d',strtotime($post['date'])))
        ->first();
        if(!$schedule_date){
            return response()->json([
                'status' => 'fail', 
                'messages' => ['Jadwal pada tanggal tersebut belum dibuat']
            ]);
        }
        $dataStore = [
            'id_user'  => $request->user()->id,
            'change_shift_date'  => $post['date'],
            'id_employee_office_hour_shift'  => $post['id_employee_office_hour_shift'],
            'reason'  => $post['reason'],
        ];

        DB::beginTransaction();

        $check = EmployeeChangeShift::where('id_user',$dataStore['id_user'])->whereDate('change_shift_date',$dataStore['change_shift_date'])->first();
        if($check){
            DB::rollBack();
            return response()->json([
                'status' => 'fail', 
                'messages' => ['Permintaan atur shift pada tanggal ini sudah ada']
            ]);
        }

        $store = EmployeeChangeShift::create($dataStore);
        if(!$store){
            DB::rollBack();
            return response()->json([
                'status' => 'fail', 
                'messages' => ['Gagal mengajukan permintaan atur shift']
            ]);
        }
        $user_sends = User::join('roles_features','roles_features.id_role', 'users.id_role')->where('id_feature',
        546)->get()->toArray();
        foreach($user_sends ?? [] as $user_send){
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Employee Request Change Shift',
                $user_send['phone'],
                [
                    'name_employee' => $user_employee['name'],
                    'phone_employee' => $user_employee['phone'],
                    'name_office' => $office->outlet_name,
                    'change_shift_date' => MyHelper::dateFormatInd($dataStore['change_shift_date'], true, false, false),
                    'category' => 'Change Shift',
                    'id_change_shift' => $store['id_employee_time_off']
                ], null, false, false, 'employee'
            );
        }
        
        DB::commit();
        return response()->json(MyHelper::checkGet($store));
    }

    public function listChangeShift(Request $request){
        $post = $request->all();
        $change_shift = EmployeeChangeShift::join('users as employees','employees.id','=','employee_change_shifts.id_user')
                    ->join('outlets', 'outlets.id_outlet', '=', 'employees.id_outlet')
                    ->select(
                        'employee_change_shifts.*',
                        'employees.name',
                        'outlets.outlet_name'
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
                        }else{
                            $subject = $condition['subject'];  
                        }

                        if($condition['operator'] == '='){
                            $change_shift = $change_shift->where($subject, $condition['parameter']);
                        }else{
                            $change_shift = $change_shift->where($subject, 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $change_shift = $change_shift->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['subject']=='name_employee'){
                                $subject = 'employees.name';
                            }elseif($condition['subject']=='outlet'){
                                $subject = 'outlets.outlet_name';
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
            }else{
                $order = 'employee_change_shifts.created_at';
            }
            if(isset($post['page'])){
                $change_shift = $change_shift->orderBy($order, $post['order_type'])->paginate($request->length ?: 10);
            }else{
                $change_shift = $change_shift->orderBy($order, $post['order_type'])->get()->toArray();
            }
        }else{
            if(isset($post['page'])){
                $change_shift = $change_shift->orderBy('employee_change_shifts.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $change_shift = $change_shift->orderBy('employee_change_shifts.created_at', 'desc')->get()->toArray();
            }
        } 
        return MyHelper::checkGet($change_shift);
    }

    public function deleteChangeShift(Request $request){
        $post = $request->all();
        $update = [
            'approve_date' => date('Y-m-d'),
            'id_approve'   => $post['id_approve'] ?? $request->user()->id,
            'status'       => 'Rejected' 
        ];
        $delete = EmployeeChangeShift::where('id_employee_change_shift', $post['id_employee_change_shift'])->update($update);
        if($delete){
            $reject_by = null;
            if(isset($update['id_approve']) && !empty($update['id_approve'])){
                $reject_by = User::where('id',$update['id_approve'])->first() ?? null;
            }
            $user_employee = User::join('employee_change_shifts','employee_change_shifts.id_user','users.id')->where('employee_change_shifts.id_employee_change_shift',$post['id_employee_change_shift'])->first();
            $office = Outlet::where('id_outlet',$user_employee['id_outlet'])->first();
            $data_change_shift = EmployeeChangeShift::where('id_employee_change_shift', $post['id_employee_change_shift'])->first();
            if (\Module::collections()->has('Autocrm')) {
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Employee Request Change Shift Rejected', 
                    $user_employee['phone'] ?? null,
                    [
                        'user_update'=> $reject_by ? $reject_by['name'] : $request->user()->name,
                        'change_shift_date'=> MyHelper::dateFormatInd($data_change_shift['change_shift_date'], true, false, false),
                        'name_office'=> $office['name_outlet'],
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
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detailChangeShift(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_employee_change_shift']) && !empty($post['id_employee_change_shift'])){
            $change_shift = EmployeeChangeShift::where('id_employee_change_shift', $post['id_employee_change_shift'])->with(['user.outlet','approve','office_hour_shift'])->first();
            
            if($change_shift==null){
                return response()->json(['status' => 'success', 'result' => [
                    'change_shift' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'change_shift' => $change_shift,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listDate(Request $request){
        $post = $request->all();
        if(empty($post['id_employee']) || empty($post['month']) || empty($post['year'])){
            return response()->json([
            	'status' => 'empty', 
            ]);
        }

        if($post['year']>=date('Y') ){
            if($post['month']>=date('m')){

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
                if($schedule && isset($schedule['id_office_hour_shift'])){
                    if($cek_employee['office_hour_type'] == 'Use Shift' || isset($schedule['id_office_hour_shift'])){

                        $employee = User::with(['role'])->where('id',$post['id_employee'])->first();
                        $outlet = Outlet::where('id_outlet',$employee['id_outlet'])->first();
                        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                        $time_zone = [
                            '7' => 'WIB',
                            '8' => 'WITA',
                            '9' => 'WIT',
                        ];
                        $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$schedule['id_office_hour_shift'])->first();
                        $shifts = [];
                        foreach($office_hour['office_hour_shift'] ?? [] as $shift){
                            $shifts[] = [
                                'id_employee_office_hour_shift' => $shift['id_employee_office_hour_shift'],
                                'shift_name' => $shift['shift_name'].' '.'('.MyHelper::adjustTimezone($shift['shift_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($shift['shift_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone].')'
                            ];
                        }
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
                            $list_date = [];
                            foreach($detail as $key => $data){
                                if($data['date'] >= date('Y-m-d 00:00:00')){
                                    $list_date[date('Y-m-d',strtotime($data['date']))]['id_employee_schedule'] = $schedule['id_employee_schedule'];
                                    $list_date[date('Y-m-d',strtotime($data['date']))]['date'] = $data['date'];
                                    $list_date[date('Y-m-d',strtotime($data['date']))]['date_format'] = date('d F Y', strtotime($data['date']));
                                    $list_date[date('Y-m-d',strtotime($data['date']))]['time_start'] = $data['time_start'] ? MyHelper::adjustTimezone($data['time_start'], $timeZone, 'H:i') : null;
                                    $list_date[date('Y-m-d',strtotime($data['date']))]['time_end'] = $data['time_end'] ? MyHelper::adjustTimezone($data['time_end'], $timeZone, 'H:i') : null;
                                }
                            }
                            return response()->json([
                                'status' => 'success', 
                                'result' => [
                                    'list_dates' => $list_date,
                                    'shifts' => $shifts
                                ]
                            ]);
                        }
                        return response()->json([
                            'status' => 'empty', 
                        ]); 
                        
                    }elseif($cek_employee['office_hour_type'] == 'Without Shift'){
                        return response()->json([
                            'status' => 'empty', 
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

    public function listShift(Request $request){
        $post = $request->all();
        if(empty($post['id_employee'])){
            return response()->json([
            	'status' => 'fail', 
            ]);
        }
        $employee = User::with(['role'])->where('id',$post['id_employee'])->first();
        $outlet = Outlet::where('id_outlet',$employee['id_outlet'])->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];
        $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$employee['role']['id_employee_office_hour'])->first();
        
        if($office_hour['office_hour_type'] == 'Use Shift' && isset($office_hour['office_hour_shift'])){
            $data = [];
            foreach($office_hour['office_hour_shift'] ?? [] as $shift){
                $data[] = [
                    'id_employee_office_hour_shift' => $shift['id_employee_office_hour_shift'],
                    'shift_name' => $shift['shift_name'].' '.'('.MyHelper::adjustTimezone($shift['shift_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($shift['shift_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone].')'
                ];
            }
            return $return = [
                'status' => 'success',
                'result' => $data
            ];
        }
        return $return = [
            'status' => 'fail',
        ];
    } 

    public function updateChangeShift(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_employee_change_shift']) && !empty($post['id_employee_change_shift'])){
            $data_update = [];
            if(isset($post['id_approve'])){
                $get_data = EmployeeChangeShift::where('id_employee_change_shift',$post['id_employee_change_shift'])->first();
                $post['id_user'] = $get_data['id_user'];
                $post['change_shift_date'] = $get_data['change_shift_date'];
                $post['id_employee_office_hour_shift'] = $get_data['id_employee_office_hour_shift'];
            }
            if(isset($post['change_shift_date'])){
                $data_update['change_shift_date'] = date('Y-m-d',strtotime($post['change_shift_date']));
            }
            if(isset($post['id_employee_office_hour_shift'])){
                $data_update['id_employee_office_hour_shift'] = $post['id_employee_office_hour_shift'];
            }
            if(isset($post['reason'])){
                $data_update['reason'] = $post['reason'];
            }
            if(isset($post['approve'])){
                $data_update['id_approve'] = $post['id_approve'] ?? auth()->user()->id;
                $data_update['approve_date'] = date('Y-m-d');
                $data_update['status'] = 'Approved';
            }
            
            if($data_update){
                DB::beginTransaction();
                $update = EmployeeChangeShift::where('id_employee_change_shift',$post['id_employee_change_shift'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'fail', 
                        'messages' => ['Failed to updated a request employee change shift']
                    ]);
                }
                if(isset($post['approve'])){
                    $schedule_date = EmployeeSchedule::join('employee_schedule_dates','employee_schedule_dates.id_employee_schedule','employee_schedules.id_employee_schedule')
                    ->where('schedule_month',date('m',strtotime($data_update['change_shift_date'])))
                    ->where('schedule_year', date('Y',strtotime($data_update['change_shift_date'])))
                    ->Where('id',$post['id_user'])
                    ->whereDate('date',date('Y-m-d',strtotime($data_update['change_shift_date'])))
                    ->first();
                    if(!$schedule_date || !isset($schedule_date['id_office_hour_shift'])){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'fail', 
                            'messages' => ['Failed to updated a request employee change shift']
                        ]);
                    }
                    
                    $office_hour = EmployeeOfficeHour::with(['office_hour_shift'=>function($of)use($data_update){$of->where('id_employee_office_hour_shift',$data_update['id_employee_office_hour_shift']);}])->where('id_employee_office_hour',$schedule_date['id_office_hour_shift'])->first();
                    if(!$office_hour || !isset($office_hour['office_hour_shift'])){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'fail', 
                            'messages' => ['Failed to updated a request employee change shift']
                        ]);
                    }   
                    $new_start_shift = $office_hour['office_hour_shift'][0]['shift_start'];
                    $new_end_shift = $office_hour['office_hour_shift'][0]['shift_end'];
                    if(isset($schedule_date['is_overtime']) && $schedule_date['is_overtime']==1){
                        $overtime = EmployeeOvertime::where('id_employee',$post['id_user'])->whereDate('date',$schedule_date['date'])->whereNotNull('approve_by')->whereNull('reject_at')->first();

                        $duration = $overtime['duration'];
                        if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
                            $duration_rest = $this->getDuration($overtime['rest_after'],$overtime['rest_before']);
                            $secs = strtotime($duration_rest)-strtotime("00:00:00");
                            $duration = date("H:i:s",strtotime($duration)+$secs);
                        }

                        if($overtime['time']=='after'){
                            $new_end_shift = $this->getDuration2($new_end_shift,$duration);
                            if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
                                $duration_rest_before = $this->getDuration($schedule_date['time_end'],$overtime['rest_before']);
                                $duration_rest_after = $this->getDuration($schedule_date['time_end'],$overtime['rest_after']);
                                $new_rest_before = $this->getDuration($new_end_shift,$duration_rest_before);
                                $new_rest_after = $this->getDuration($new_end_shift,$duration_rest_after);
                            }
                        }elseif($overtime['time']=='before'){
                            $new_start_shift = $this->getDuration($new_start_shift,$duration);
                            if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
                                $duration_rest_before = $this->getDuration($overtime['rest_before'],$schedule_date['time_start']);
                                $duration_rest_after = $this->getDuration($overtime['rest_after'],$schedule_date['time_start']);
                                $new_rest_before = $this->getDuration2($new_start_shift,$duration_rest_before);
                                $new_rest_after = $this->getDuration2($new_start_shift,$duration_rest_after);
                            }
                            
                        }

                    }
                    $update_schedule_date = EmployeeScheduleDate::where('id_employee_schedule_date',$schedule_date['id_employee_schedule_date'])->update([
                        'shift' => $office_hour['office_hour_shift'][0]['shift_name'],
                        'time_start' => date('H:i:s',strtotime($new_start_shift)),
                        'time_end' => date('H:i:s',strtotime($new_end_shift)),
                    ]);
                    if(!$update_schedule_date){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'fail', 
                            'messages' => ['Failed to updated a request employee change shift']
                        ]);
                    }
                    if(isset($schedule_date['is_overtime']) && $schedule_date['is_overtime']==1){
                        if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
                            $update_ovt = EmployeeOvertime::where('id_employee',$post['id_user'])->whereDate('date',$schedule_date['date'])->whereNotNull('approve_by')->whereNull('reject_at')->update([
                                'rest_before' => date('H:i:s',strtotime($new_rest_before)),
                                'rest_after' => date('H:i:s',strtotime($new_rest_after)),
                            ]);
                            if(!$update_ovt){
                                DB::rollBack();
                                return response()->json([
                                    'status' => 'fail', 
                                    'messages' => ['Failed to updated a request employee change shift']
                                ]);
                            }
                        }
                    }

                    $user_employee = User::join('employee_change_shifts','employee_change_shifts.id_employee','users.id')->where('employee_change_shifts.id_employee_change_shift',$post['id_employee_change_shift'])->first();
                    $office = Outlet::where('id_outlet',$user_employee['id_outlet'])->first();
                    $approve_by = null;
                    if(isset($data_update['id_approve']) && !empty($data_update['id_approve'])){
                        $approve_by = User::where('id',$data_update['id_approve'])->first() ?? null;
                    }
                    if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Employee Request Change Shift Approved', 
                            $user_employee['phone'] ?? null,
                            [
                                'user_update'=> $approve_by ? $approve_by['name'] : $request->user()->name,
                                'change_shift_date'=> MyHelper::dateFormatInd($data_update['change_shift_date'], true, false, false),
                                'name_office'=> $office['name_outlet'],
                                'category' => 'Change Shift',
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

    public function getDuration($start_time, $end_time){
        $duration = strtotime($end_time);
        $start = strtotime($start_time);
        $diff = $start - $duration;
        $hour = floor($diff / (60*60));
        $minute = floor(($diff - ($hour*60*60))/(60));
        $second = floor(($diff - ($hour*60*60))%(60));
        return $new_time =  date('H:i:s', strtotime($hour.':'.$minute.':'.$second));
    }

    public function getDuration2($start_time,$end_time){
        $secs = strtotime($end_time)-strtotime("00:00:00");
        return $new_time = date("H:i:s",strtotime($start_time)+$secs);
    }
}
