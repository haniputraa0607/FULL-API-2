<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Employee\Entities\EmployeeOfficeHour;
use Modules\Employee\Entities\EmployeeOfficeHourAssign;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Employee\Entities\EmployeeOvertime;
use Modules\Users\Entities\Role;
use App\Http\Models\User;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Holiday;
use Modules\Employee\Entities\EmployeeSchedule;
use Modules\Employee\Entities\EmployeeScheduleDate;

use DB;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Entities\EmployeeDevice;
use Modules\Employee\Entities\EmployeeTimeOff;

class ApiEmployeeController extends Controller
{
    public function officeHoursCreate(Request $request){
        $post = $request->all();

        if($post['office_hour_type'] == 'Use Shift' && empty($post['shift'])){
            return response()->json(['status'   => 'fail', 'messages' => ['Data shift can not be empty']]);
        }

        $data = [
            'office_hour_name' => $post['office_hour_name'],
            'office_hour_type' => $post['office_hour_type'],
            'office_hour_start' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_start'])) : NULL),
            'office_hour_end' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_end'])) : NULL)
        ];

        $create = EmployeeOfficeHour::create($data);

        if($create){
            if($post['office_hour_type'] == 'Use Shift'){
                $insertShift = [];
                foreach ($post['shift'] as $data){
                    $insertShift[] = [
                        'id_employee_office_hour' => $create['id_employee_office_hour'],
                        'shift_name' => $data['name'],
                        'shift_start' => date('H:i:s', strtotime($data['start'])),
                        'shift_end'  => date('H:i:s', strtotime($data['end'])),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                if(!empty($insertShift)){
                    EmployeeOfficeHourShift::insert($insertShift);
                }
            }

            if(!empty($post['employee_office_hour_default'])){
                $create = Setting::updateOrCreate(['key' => 'employee_office_hour_default'], ['value' => $create['id_employee_office_hour']]);
            }
        }

        return response()->json(MyHelper::checkCreate($create));
    }

    public function officeHoursDefault(){
        $value = Setting::where('key', 'employee_office_hour_default')->first()['value']??NULL;
        return response()->json(MyHelper::checkGet($value));
    }

    public function officeHoursList(){
        $res = EmployeeOfficeHour::with('office_hour_shift')->get()->toArray();
        return response()->json(MyHelper::checkGet($res));
    }

    public function officeHoursDetail(Request $request){
        $post = $request->all();

        if(!empty($post['id_employee_office_hour'])){
            $detail = EmployeeOfficeHour::where('id_employee_office_hour', $post['id_employee_office_hour'])->with('office_hour_shift')->first();

            if($detail){
                $detail['employee_office_hour_default'] = Setting::where('key', 'employee_office_hour_default')->first()['value']??NULL;
            }
            return response()->json(MyHelper::checkGet($detail));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function officeHoursUpdate(Request $request){
        $post = $request->all();

        if(empty($post['id_employee_office_hour'])){
            return response()->json(['status'   => 'fail', 'messages' => ['ID can not be empty']]);
        }

        if($post['office_hour_type'] == 'Use Shift' && empty($post['shift'])){
            return response()->json(['status'   => 'fail', 'messages' => ['Data shift can not be empty']]);
        }

        $data = [
            'office_hour_name' => $post['office_hour_name'],
            'office_hour_type' => $post['office_hour_type'],
            'office_hour_start' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_start'])) : NULL),
            'office_hour_end' => ($post['office_hour_type'] == 'Without Shift' ? date('H:i:s', strtotime($post['office_hour_end'])) : NULL)
        ];

        $update = EmployeeOfficeHour::where('id_employee_office_hour', $post['id_employee_office_hour'])->update($data);

        EmployeeOfficeHourShift::where('id_employee_office_hour', $post['id_employee_office_hour'])->delete();
        if($update){

            if($post['office_hour_type'] == 'Use Shift'){
                $insertShift = [];
                foreach ($post['shift'] as $data){
                    $insertShift[] = [
                        'id_employee_office_hour' => $post['id_employee_office_hour'],
                        'shift_name' => $data['name'],
                        'shift_start' => date('H:i:s', strtotime($data['start'])),
                        'shift_end'  => date('H:i:s', strtotime($data['end'])),
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                if(!empty($insertShift)){
                    EmployeeOfficeHourShift::insert($insertShift);
                }
            }

            $checkSetting = Setting::where('key', 'employee_office_hour_default')->first()['value']??null;
            if(!empty($post['employee_office_hour_default'])){
                $default = $post['id_employee_office_hour'];
                $update = Setting::updateOrCreate(['key' => 'employee_office_hour_default'], ['value' => $default]);
            }elseif(empty($post['employee_office_hour_default']) && $checkSetting == $post['id_employee_office_hour']){
                $default = null;
                $update = Setting::updateOrCreate(['key' => 'employee_office_hour_default'], ['value' => $default]);
            }
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    public function officeHoursDelete(Request $request){
        $post = $request->all();

        if(!empty($post['id_employee_office_hour'])){
            $check = EmployeeOfficeHour::where('id_employee_office_hour', $post['id_employee_office_hour'])->first();

            if(empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Data office hours not found']]);
            }

            $delete = EmployeeOfficeHour::where('id_employee_office_hour', $check['id_employee_office_hour'])->delete();

            if($delete && $check['office_hour_type'] == 'Use Shift'){
                $delete = EmployeeOfficeHourShift::where('id_employee_office_hour', $check['id_employee_office_hour'])->delete();
            }

            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function officeHoursAssign(Request $request){
        $post = $request->all();

        if(empty($post)){
            $role =  Role::select('id_role', 'role_name', 'id_employee_office_hour')->get()->toArray();
            return response()->json(MyHelper::checkGet($role));
        }else{
            if(empty($post['data'])){
                return response()->json(['status' => 'fail', 'messages' => ['Data can not be empty']]);
            }

            foreach ($post['data'] as $val){
                Role::where('id_role', $val['id_role'])->update(['id_employee_office_hour' => ($val['id_employee_office_hour'] == 'default' ? NULL: $val['id_employee_office_hour'])]);
            }
            return response()->json(['status' => 'success']);
        }
    }
    public function employeeList(Request $request){
        $post = $request->json()->all();

        $data = User::join('roles', 'roles.id_role', 'users.id_role')->join('outlets', 'outlets.id_outlet', 'users.id_outlet')->where('outlets.type','Office')->orderBy('created_at', 'desc');

        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }

            if($rule == 'and'){
                foreach ($post['conditions'] as $row){
                    if(isset($row['subject'])){
                        if($row['subject'] == 'nickname'){
                            if($row['operator'] == '='){
                                $data->where('nickname', $row['parameter']);
                            }else{
                                $data->where('nickname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'email'){
                            if($row['operator'] == '='){
                                $data->where('email', $row['parameter']);
                            }else{
                                $data->where('email', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'phone_number'){
                            if($row['operator'] == '='){
                                $data->where('phone_number', $row['parameter']);
                            }else{
                                $data->where('phone_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'fullname'){
                            if($row['operator'] == '='){
                                $data->where('fullname', $row['parameter']);
                            }else{
                                $data->where('fullname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if($row['subject'] == 'gender'){
                            $data->where('gender', $row['operator']);
                        }

                        if($row['subject'] == 'level'){
                            $data->where('user_hair_stylist.level', $row['operator']);
                        }
                        if($row['subject'] == 'outlet'){
                            $data->where('user_hair_stylist.id_outlet', $row['operator']);
                        }
                    }
                }
            }else{
                $data->where(function ($subquery) use ($post){
                    foreach ($post['conditions'] as $row){
                        if(isset($row['subject'])){
                            if($row['subject'] == 'nickname'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('nickname', $row['parameter']);
                                }else{
                                    $subquery->orWhere('nickname', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'email'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('email', $row['parameter']);
                                }else{
                                    $subquery->orWhere('email', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'phone_number'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('phone_number', $row['parameter']);
                                }else{
                                    $subquery->orWhere('phone_number', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'fullname'){
                                if($row['operator'] == '='){
                                    $subquery->orWhere('fullname', $row['parameter']);
                                }else{
                                    $subquery->orWhere('fullname', 'like', '%'.$row['parameter'].'%');
                                }
                            }

                            if($row['subject'] == 'gender'){
                                $subquery->orWhere('gender', $row['operator']);
                            }

                            if($row['subject'] == 'level'){
                                $subquery->orWhere('level', $row['operator']);
                            }
                            if($row['subject'] == 'outlet'){
                             $subquery->orWhere('user_hair_stylist.id_outlet', $row['operator']);
                            }
                        }
                    }
                });
            }
        }
        $data = $data->select('users.*', 'roles.role_name', 'outlets.outlet_name')->paginate(25);
        return response()->json(MyHelper::checkGet($data));
    }

    public function shift(Request $request){
        $post = $request->all();
        $get_shift = EmployeeOfficeHourShift::join('employee_office_hours','employee_office_hours.id_employee_office_hour', 'employee_office_hour_shift.id_employee_office_hour')
                                            ->join('roles', 'roles.id_employee_office_hour', 'employee_office_hours.id_employee_office_hour')
                                            ->join('users', 'users.id_role', 'roles.id_role')
                                            ->where('users.id', $post['id'])
                                            ->select('employee_office_hour_shift.shift_name')
                                            ->get()->toArray();  
        return response()->json(MyHelper::checkGet($get_shift));

                                            
    }

    public function calender(Request $request){
        $post = $request->all();
        $employee = $request->user()->id;
        $office = $request->user()->id_outlet;
        $time_off_quota = Setting::where('key','quota_employee_time_off')->get('value')->first();
        if(!$time_off_quota){
            return response()->json(['status'   => 'fail', 'messages' => ['Jatah cuti karyawan belum diatur']]); 
        }
        $time_off_quota = $time_off_quota['value'];

        $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee)->first();

        if(empty($type_shift['office_hour_type'])){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Jam kantor tidak ada ']
            ]);
        }

        //holiday
        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
                            ->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                            ->where('id_outlet', $office)
                            ->where(function($p1) use($post) {
                                $p1->whereYear('date_holidays.date', $post['year'])
                                    ->whereMonth('date_holidays.date', $post['month'])
                                    ->orWhere(function($p2) use($post){
                                        $p2->where('holidays.yearly', '1')
                                            ->whereMonth('date_holidays.date', $post['month']);
                                    });
                            })
                            ->select(
                                'holidays.holiday_name',
                                'date_holidays.date'
                            )
                            ->orderBy('date_holidays.date')
                            ->get()->toArray();
        $data_holidays = [];
        foreach($holidays as $h){
            if(isset($data_holidays[$h['holiday_name']])){
                $data_holidays[$h['holiday_name']]['date'][] = MyHelper::dateFormatInd($h['date'], true, false, true);
            }else{
                $h_holidays[$h['holiday_name']]['holiday_name'] = $h['holiday_name'];
                $data_holidays[$h['holiday_name']]['date'][] = MyHelper::dateFormatInd($h['date'], true, false, true);
            }
        }   
        $send_holidays = [];
        $i = 0;
        foreach($data_holidays as $key => $dh){
            $send_holidays[$i]['event_name'] = $key;
            $send_holidays[$i]['date'] = $dh['date'];
            $i++;
        }

        //overtime
        $overtimes = EmployeeOvertime::join('users','users.id','employee_overtime.id_employee')->where('employee_overtime.id_employee', $employee)->whereMonth('date', $post['month'])->whereYear('date', $post['year'])->whereNotNull('approve_by')->whereNull('reject_at')->select('users.name','employee_overtime.*')->orderBy('employee_overtime.date')->get()->toArray();
        $data_overtimes = [];
        foreach($overtimes as $o){
            $array_duration = explode(':',$o['duration']);
            $duration = '';
            if($array_duration[0]>0){       
                $duration = (int)$array_duration[0]. ' Jam'; 
            }

            if($array_duration[1]>0){     
                if($duration!=''){
                    $duration = $duration.' '.(int)$array_duration[1]. ' Menit'; 
                }else{
                    $duration = (int)$array_duration[1]. ' Menit'; 
                }  
            }

            if($type_shift['office_hour_type'] == 'Without Shift'){
                $office_hour_start = date('H:i', strtotime($type_shift['office_hour_start']));
                $office_hour_end = date('H:i', strtotime($type_shift['office_hour_end']));
            }else{
                $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule','employee_schedule_dates.id_employee_schedule')->where('employee_schedules.id',$employee)->whereDate('employee_schedule_dates.date', $o['date'])->first();
                if(empty($schedule_date)){
                    return response()->json([
                        'status'=>'fail',
                        'messages'=>['Jadwal belum dibuat']
                    ]);
                }
                $shift = EmployeeOfficeHourShift::where('id_employee_office_hour', $type_shift['id_employee_office_hour'])->where('shift_name',$schedule_date['shift'])->first();
                $office_hour_start = date('H:i', strtotime($shift['shift_start']));
                $office_hour_end = date('H:i', strtotime($shift['shift_end']));
            }

            if($o['time']=='before'){
                $duration_ovt = strtotime($o['duration']);
                $start = strtotime($office_hour_start);
                $diff = $start - $duration_ovt;
                $hour = floor($diff / (60*60));
                $minute = floor(($diff - ($hour*60*60))/(60));
                $second = floor(($diff - ($hour*60*60))%(60));
                $new_time =  date('H:i', strtotime($hour.':'.$minute.':'.$second));
                $start_ovt = $new_time;
                $end_ovt = $office_hour_start;
            }elseif($o['time']=='after'){
                $secs = strtotime($o['duration'])-strtotime("00:00:00");
                $new_time = date("H:i",strtotime($office_hour_end)+$secs);
                $start_ovt = $office_hour_end;
                $end_ovt = $new_time;
            }else{
                return false;
            }

            $data_overtimes[] = [
                'name' => $o['name'],
                'duration' => $duration,
                'date' => MyHelper::dateFormatInd($o['date'], true, false, true).' '.$start_ovt.' - '.$end_ovt,
                'note' => $o['notes'] ? $o['notes'] : 'Lembur',
            ];
        }

        //timee off quota
        $time_off_this_employee = EmployeeTimeOff::where('id_employee', $employee)->whereNotNull('approve_by')->whereNull('reject_at')->whereYear('date',$post['year'])->where('use_quota_time_off', 1)->count();
        $time_off_quota = $time_off_quota - $time_off_this_employee;


        $time_off = EmployeeTimeOff::join('users','users.id','employee_time_off.id_employee')->where('employee_time_off.id_outlet',$office)->whereNotNull('employee_time_off.approve_by')->whereNull('employee_time_off.reject_at')->whereMonth('employee_time_off.date',$post['month'])->whereYear('employee_time_off.date',$post['year'])->select('users.name','employee_time_off.*')->orderBy('employee_time_off.date')->get()->toArray();
        $data_time_off = [];
        foreach($time_off as $to){
            if(isset($data_time_off[$to['name'].'_'.$to['type']])){
                $data_time_off[$to['name'].'_'.$to['type']]['name_employee'] = $to['name'];
                $data_time_off[$to['name'].'_'.$to['type']]['date'][] = MyHelper::dateFormatInd($to['date'], true, false, true);
                $data_time_off[$to['name'].'_'.$to['type']]['total'] = $data_time_off[$to['name'].'_'.$to['type']]['total'] + 1;
            }else{
                $data_time_off[$to['name'].'_'.$to['type']]['name_employee'] = $to['name'];
                $data_time_off[$to['name'].'_'.$to['type']]['type'] = $to['type'];
                $data_time_off[$to['name'].'_'.$to['type']]['date'][] = MyHelper::dateFormatInd($to['date'], true, false, true);
                $data_time_off[$to['name'].'_'.$to['type']]['total'] = 1; 
            }
        }

        $send_time_off = [];
        $i = 0;
        foreach($data_time_off as $key => $dt){
            $send_time_off[$i] = $dt;
            $i++;
        }

        $data = [
            'quota_time_off' => $time_off_quota,
            'event'          => $send_holidays,
            'time_off'       => $send_time_off,
            'overtime'       => $data_overtimes,
        ];

        return response()->json(MyHelper::checkGet($data));
 

    }

}
