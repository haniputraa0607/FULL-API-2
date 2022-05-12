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
use Modules\Employee\Entities\EmployeeTimeOffImage;
use Modules\Employee\Entities\EmployeeOverTime;
use Modules\Employee\Entities\EmployeeNotAvailable;
use App\Http\Models\Holiday;
use App\Http\Models\Setting;
use Modules\Employee\Http\Requests\EmployeeTimeOffCreate;

class ApiEmployeeTimeOffOvertimeController extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->time_off = "img/employee/time_off/";
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
        
        return MyHelper::checkGet($time_off);

    }

    public function createTimeOffEmployee(Request $request){
        $post = $request->all(); 
        $type = Setting::where('key','employee_time_off_type')->get('value_text')->first();
        $type_time_off = [];
        if($type){
            $type_time_off = json_decode($type['value_text']??'' , true);
        }
        return MyHelper::checkGet($type_time_off);
    }

    public function storeTimeOffEmployee(EmployeeTimeOffCreate $request){
        $post = $request->all();
        $employee = $request->user()->id;
        $office = $request->user()->id_outlet;
        $array_date = explode('-',$post['date']);
        $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee)->first()['office_hour_type'];

        //cek date
        if(date('Y-m-d', strtotime($post['date'])) < date('Y-m-d')){
            return response()->json(['status' => 'fail', 'messages' => ['Minimal tanggal pengajuan cuti adalah hari ini']]);
        }

        //cek_time_off
        $time_off = EmployeeTimeOff::where('id_employee',$employee)->where('id_outlet',$office)->whereDate('date', $post['date'])->get()->toArray();
        if($time_off){
            //disetujui tdk bisa lagi mengajukan
            foreach($time_off as $tf){
                if(isset($tf['approve_by']) && !isset($tf['reject_at'])){
                    return response()->json(['status' => 'fail', 'messages' => ['Sudah ada pengajuan cuti yang disetujui pada tanggal ini']]);
                }
            }
            //pending
            foreach($time_off as $tf){
                if(!isset($tf['approve_by']) && !isset($tf['reject_at'])){
                    return response()->json(['status' => 'fail', 'messages' => ['Sudah ada pengajuan cuti yang dan sedang menunggu persetujuan']]);
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

        $day = date('l, F j Y', strtotime($post['date']));
        $hari = MyHelper::indonesian_date_v2($post['date'], 'l');
        $hari = str_replace('Jum\'at', 'Jumat', $hari);
        
        if($outletSchedule[$hari]['is_closed'] == 1){
            return response()->json(['status' => 'fail', 'messages' => ['Kantor tutup pada tanggal ini']]);
        }

        //holiday
        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
                            ->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
                            ->where('id_outlet', $office)
                            ->where(function($p1) use($post, $array_date) {
                                $p1->whereDate('date_holidays.date', $post['date'])
                                    ->orWhere(function($p2) use($array_date){
                                        $p2->where('holidays.yearly', '1')
                                            ->whereDay('date_holidays.date', $array_date[2])
                                            ->whereMonth('date_holidays.date', $array_date[1]);
                                    });
                            })
                            ->get()->toArray();
        if($holidays){
            return response()->json(['status' => 'fail', 'messages' => ['Kantor libur pada tanggal ini']]);
        }

        //employee with shift
        if($type_shift == 'Use Shift'){
            $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                    ->join('users','users.id','employee_schedules.id')
                                                    ->where('users.id', $employee)
                                                    ->where('employee_schedules.schedule_month', $array_date[1])
                                                    ->where('employee_schedules.schedule_year', $array_date[0])
                                                    ->whereDate('employee_schedule_dates.date', $post['date'])
                                                    ->first();
            if(!$schedule_date){
                return response()->json(['status' => 'fail', 'messages' => ['Jadwal karyawan pada tanggal ini belum dibuat']]);
            }
        }

        $data_time_off = [
            'id_employee' => $employee,
            'id_outlet'   => $office,
            'type'        => $post['type'],
            'request_by'  => $employee,
            'date'        => date('Y-m-d 00:00:00', strtotime($post['date'])),
            'request_at'  => date('Y-m-d'),
            'notes'       => $post['notes']
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
        $employees = User::where('id_outlet', $office)->whereNotNull('id_role')->select('id', 'name')->get()->toArray();

        return MyHelper::checkGet($employees);

    }

    public function checkOvertimeEmployee(Request $request){
        $post = $request->all(); 
        $employee = $post['id_employee'];
        $office = $request->user()->id_outlet;
        $array_date = explode('-',$post['date']);
        $date = date('Y-m-d', strtotime($post['date']));

        $check = $this->checkDateOvertime($office, $post);
        if(isset($check['status']) && $check['status'] == 'success'){
            $send = [
                'shift' => null,
                'schedule_in' => $check['date']['time_start'] ?? null,
                'schedule_out' => $check['date']['time_end'] ?? null,
            ];
            $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee)->first()['office_hour_type'];

            //employee with shift
            if($type_shift == 'Use Shift'){
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

                $send['shift'] = $schedule_date['shift'];
                $send['schedule_in'] = date('H:i', strtotime($schedule_date['time_start']));
                $send['schedule_out'] = date('H:i', strtotime($schedule_date['time_end']));
                
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
        $array_date = explode('-',$post['date']);
        $date = date('Y-m-d', strtotime($post['date']));

        $check = $this->checkDateOvertime($office, $post);
        if(isset($check['status']) && $check['status'] == 'success'){
            $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee)->first()['office_hour_type'];

            //employee with shift
            if($type_shift == 'Use Shift'){
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
                'id_outlet'   => $office,
                'request_by'  => $request->user()->id,
                'date'        => $date,
                'time'        => $post['time'],
                'duration'    => $new_time,
                'rest_before' => date('H:i:s', strtotime($post['start_rest'])),
                'rest_after' => date('H:i:s', strtotime($post['end_rest'])),
                'request_at'  => date('Y-m-d'),
                'notes'       => $post['notes']
            ];

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
        $notAvail = EmployeeNotAvailable::join('employee_time_off', 'employee_time_off.id_employee_time_off', 'employee_not_available.id_employee_time_off')->where('employee_not_available.id_employee',$employee)->where('employee_not_available.id_outlet', $office)->whereDate('employee_time_off.date', $date)->first();
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

}
