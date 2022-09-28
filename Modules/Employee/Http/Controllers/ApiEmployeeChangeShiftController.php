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
        $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$employee['id_employee_office_hour'])->first();
        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];

        $change = false;
        $shift = MyHelper::adjustTimezone($office_hour['office_hour_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($office_hour['office_hour_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone];
        if($office_hour['office_hour_type'] == 'Use Shift' && isset($office_hour['office_hour_shift'])){
            $schedule_date = EmployeeSchedule::join('employee_schedule_dates','employee_schedule_dates.id_employee_schedule','employee_schedules.id_employee_schedule')
            ->where('schedule_month',date('m'))
            ->where('schedule_year', date('Y'))
            ->whereDate('date',date('Y-m-d'))
            ->first();
            $change = true;
            $shift = MyHelper::adjustTimezone($schedule_date['time_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($schedule_date['time_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone];
        }
        return $data = [
            'type_shift' => $office_hour['office_hour_name'],
            'shift' => $shift,
            'change_shift' => $change,
        ];
    }

    public function create(Request $request){
        $post = $request->all();
        $employee = $request->user()->role()->first();
        $outlet = $request->user()->outlet()->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$employee['id_employee_office_hour'])->first();
        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];

        if(!$post){
            $data = [];
            foreach($office_hour['office_hour_shift'] ?? [] as $key => $office_hour_shift){
                $data[] = [
                    'name' => $office_hour['office_hour_name'].' '.'('.$office_hour_shift['shift_name'].')',
                    'shift' => MyHelper::adjustTimezone($office_hour_shift['shift_start'], $timeZone, 'H:i', true).'-'.MyHelper::adjustTimezone($office_hour_shift['shift_end'], $timeZone, 'H:i', true).' '.$time_zone[$timeZone],
                    'id_employee_office_hour_shift' => $office_hour_shift['id_employee_office_hour_shift']
                ];
            }
            return $data;
        }else{
            $request->validate([
                'date' => 'date|required',
                'id_employee_office_hour_shift' => 'numeric|required',
                'reason' => 'string|required',
            ]);
            $schedule_date = EmployeeSchedule::join('employee_schedule_dates','employee_schedule_dates.id_employee_schedule','employee_schedules.id_employee_schedule')
            ->where('schedule_month',date('m',strtotime($post['date'])))
            ->where('schedule_year', date('Y',strtotime($post['date'])))
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
            
            DB::commit();
            return response()->json(MyHelper::checkGet($store));

        }
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
            'id_approve'   => $request->user()->id,
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
            // if (\Module::collections()->has('Autocrm')) {
            //     $autocrm = app($this->autocrm)->SendAutoCRM(
            //         'Employee Request Time Off Rejected', 
            //         $user_employee['phone'] ?? null,
            //         [
            //             'user_update'=> $reject_by ? $reject_by['name'] : $request->user()->name,
            //             'change_shift_date'=> $user_employee['date'],
            //             'name_office'=> $office['name_outlet'],
            //             'categore' => 'Time Off',
            //         ], null, false, false, $recipient_type = 'employee', null, true
            //     );
            //     if (!$autocrm) {
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Failed to send']
            //         ]);
            //     }
            // }
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detailChangeShift(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_employee_change_shift']) && !empty($post['id_employee_change_shift'])){
            $change_shift = EmployeeChangeShift::where('id_employee_change_shift', $post['id_employee_change_shift'])->with(['user.outlet','approve'])->first();
            
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
                if($schedule){
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
                                return response()->json([
                                    'status' => 'success', 
                                    'result' => $send
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
        $office_hour = EmployeeOfficeHour::with(['office_hour_shift'])->where('id_employee_office_hour',$employee['id_employee_office_hour'])->first();
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
                $get_data = EmployeeTimeOff::where('id_employee_change_shift',$post['id_employee_change_shift'])->first();
                $post['id_employee'] = $get_data['id_employee'];
                $post['start_date'] = $get_data['start_date'];
                $post['end_date'] = $get_data['end_date'];
                if($get_data['use_quota_time_off']==1){
                    $post['use_quota_time_off'] = 1;
                }
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
            }

            if($data_update){
                DB::beginTransaction();
                $update = EmployeeChangeShift::where('id_employee_change_shift',$post['id_employee_change_shift'])->update($data_update);
                if(!$update){
                    DB::rollBack();
                    return response()->json([
                        'status' => 'success', 
                        'messages' => ['Failed to updated a request employee change shift']
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
                        $time_off_this_employee = EmployeeTimeOff::where('id_employee', $data_update['id_employee'])->where('id_employee_change_shift','!=',$post['id_employee_change_shift'])->whereNotNull('approve_by')->whereNull('reject_at')->whereYear('start_date',date('Y',strtotime($data_update['start_date'])))->where('use_quota_time_off', 1)->sum('range');
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
                    
                    $time_off = EmployeeTimeOff::where('id_employee',$data_update['id_employee'])->where('id_employee_change_shift','!=',$post['id_employee_change_shift'])->where('id_outlet',$data_update['id_outlet'])
                    ->where(function($time)use($data_update){
                        $time->where(function($w) use($data_update){$w->whereDate('start_date','>=',$data_update['start_date'])->whereDate('end_date','<=',$data_update['end_date']);})
                        ->orWhere(function($w2) use($data_update){$w2->whereDate('start_date','<=',$data_update['start_date'])->whereDate('end_date','>=',$data_update['start_date'])->whereDate('end_date','<=',$data_update['end_date']);})
                        ->orWhere(function($w3) use($data_update){$w3->whereDate('start_date','>=',$data_update['start_date'])->whereDate('start_date','<=',$data_update['end_date'])->whereDate('end_date','>=',$data_update['end_date']);})
                        ->orWhere(function($w4) use($data_update){$w4->whereDate('start_date','<=',$data_update['start_date'])->whereDate('end_date','>=',$data_update['end_date']);});
                    })->get()->toArray();
                    if($time_off){
                        //disetujui tdk bisa lagi mengajukan
                        foreach($time_off as $tf){
                            DB::rollBack();
                            if(isset($tf['approve_by']) && !isset($tf['reject_at'])){
                                return response()->json(['status' => 'fail', 'messages' => ['There has been a request time off approved at '.date('F j, Y', strtotime($tf['start_date'])).' to '.date('F j, Y', strtotime($tf['end_date']))]]);
                            }
                        }
                        //pending
                        foreach($time_off as $tf){
                            DB::rollBack();
                            if(!isset($tf['approve_by']) && !isset($tf['reject_at'])){
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
                        "id_employee_change_shift" => $post['id_employee_change_shift'],
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
                    $update_range = EmployeeTimeOff::where('id_employee_change_shift',$post['id_employee_change_shift'])->update(['range' => $diff]);
                    $store_not_avail = EmployeeNotAvailable::create($data_not_avail);
                    if(!$store_not_avail){
                        DB::rollBack();
                        return response()->json([
                            'status' => 'success', 
                            'messages' => ['Failed to updated a request employee time off']
                        ]);
                    }
                    $user_employee = User::join('employee_time_off','employee_time_off.id_employee','users.id')->where('employee_time_off.id_employee_change_shift',$post['id_employee_change_shift'])->first();
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
                                'time_off_date'=> $user_employee['date'],
                                'name_office'=> $office['name_outlet'],
                                'categore' => 'Time Off',
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
}
