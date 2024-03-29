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
use Modules\Employee\Entities\EmployeeAttendanceRequest;
use Modules\Employee\Entities\EmployeeAttendanceLog;
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use Modules\Users\Entities\Role;
use App\Http\Models\Setting;


use DB;
use Modules\Employee\Entities\EmployeeOfficeHour;

class ApiEmployeeAttendanceController extends Controller
{
    public function __construct()
    {
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
    }

    public function liveAttendance(Request $request)
    {
        $today = date('Y-m-d');
        $employee = $request->user();
        $outlet = $employee->outlet()->select('outlet_name', 'outlet_latitude', 'outlet_longitude', 'id_city')->first();
        $shift = false;
        $outlet->setHidden(['call', 'url']);
        $schedule_month = EmployeeSchedule::where('id',$employee->id)->where('schedule_month',date('m'))->where('schedule_year',date('Y'))->first();
        // get current schedule
        $todaySchedule = $employee->employee_schedules()
//            ->selectRaw('date, min(time_start) as clock_in_requirement, max(time_end) as clock_out_requirement, shift')
            ->selectRaw('date, time_start as clock_in_requirement, time_end as clock_out_requirement, shift')
            ->join('employee_schedule_dates', 'employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule');
        
        if($employee->role->office_hour['office_hour_type'] == 'Use Shift' || isset($schedule_month['id_office_hour_shift'])){
            $todaySchedule = $todaySchedule->whereNotNull('approve_at');
            $shift = true;
        }else{
            $shift = false;
        }

        $todaySchedule = $todaySchedule->where([
                'schedule_month' => date('m'),
                'schedule_year' => date('Y')
            ])
            ->whereDate('date', date('Y-m-d'))
            ->first();
        if (!$todaySchedule || !$todaySchedule->date) {
            return [
                'status' => 'fail',
                'messages' => ['Tidak ada kehadiran dibutuhkan untuk hari ini']
            ];
        }
        
        $attendance = $employee->getAttendanceByDate($todaySchedule, $shift);

        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        
        $default = Setting::where('key', 'employee_office_hour_default')->first()['value'] ?? null;
        $office_hour_name = isset($employee->role->office_hour['office_hour_name']) ? $employee->role->office_hour['office_hour_name'] : (isset($schedule_month['id_office_hour_shift']) ? EmployeeOfficeHour::where('id_employee_office_hour',$schedule_month['id_office_hour_shift'])->first()['office_hour_name'] : EmployeeOfficeHour::where('id_employee_office_hour',$default)->first()['office_hour_name']);
        
        $result = [
            'timezone' => $timeZone,
            'clock_in_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_in_requirement, $timeZone, 'H:i', true),
            'clock_out_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_out_requirement, $timeZone, 'H:i', true),
            'shift_name' => $todaySchedule->shift ? $office_hour_name.' ('.$todaySchedule->shift.')' : $office_hour_name,
            'outlet' => $outlet,
            'clock_in_button' => true,
            'clock_out_button' => false,
            'logs' => $attendance->logs()->get()->transform(function($item) use($timeZone) {
                return [
                    'location_name' => $item->location_name,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'longitude' => $item->longitude,
                    'type' => ucwords(str_replace('_', ' ',$item->type)),
                    'photo' => $item->photo_path ? config('url.storage_url_api') . $item->photo_path : null,
                    'date' => MyHelper::adjustTimezone($item->datetime, $timeZone, 'l, d F Y', true),
                    'time' => MyHelper::adjustTimezone($item->datetime, $timeZone, 'H:i'),
                    'notes' => $item->notes ?: '',
                ];
            }),
        ];

        foreach($result['logs'] ?? [] as $log){
            if($log['type'] == 'Clock In'){
                $result['clock_in_button'] = false;
                $result['clock_out_button'] = true;
            }

            if($log['type'] == 'Clock Out'){
                $result['clock_in_button'] = false;
                $result['clock_out_button'] = false;
            }
        }

        return MyHelper::checkGet($result);
    }
    public function storeLiveAttendance(Request $request)
    {
        $request->validate([
            'type' => 'string|required|in:clock_in,clock_out',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_name' => 'string|nullable|sometimes',
            'photo' => 'string|required',
        ]);
        $employee = $request->user();
        $schedule_month = EmployeeSchedule::where('id',$employee->id)->where('schedule_month',date('m'))->where('schedule_year',date('Y'))->first();
        $shift = false;
        if($employee->role->office_hour['office_hour_type'] == 'Use Shift' || isset($schedule_month['id_office_hour_shift'])){
            $shift = true;
        }else{
            $shift = false;
        }
        $outlet = $employee->outlet;
        $role = $employee->role;

        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $date_time_now = MyHelper::adjustTimezone(date('Y-m-d H:i:s'), $timeZone, 'Y-m-d H:i:s', true);
        $attendance = $employee->getAttendanceByDate(date('Y-m-d'), $shift);
		
        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];

        $maximumRadius = MyHelper::setting('employee_attendance_max_radius', 'value', 50);
        $distance = MyHelper::getDistance($request->latitude, $request->longitude, $outlet->outlet_latitude, $outlet->outlet_longitude);
        $outsideRadius = $distance > $maximumRadius;
		
        if ($outsideRadius && !$request->radius_confirmation) {
            return MyHelper::checkGet([
                'need_confirmation' => true,
                'message' => 'Anda diluar dari area yang telah ditentukan, akan diproses sebagai permintaan kehadiran dan memerlukan persetujuan dari atasan Anda.',
            ]);
        }
		$clockinRadius = MyHelper::setting('employee_clock_in_tolerance', 'value', 50);
		$clockoutRadius = MyHelper::setting('employee_clock_out_tolerance', 'value', 50);
		$clockins = strtotime("+".$clockinRadius."minutes", strtotime($attendance['clock_in_requirement']));
		$clockouts = strtotime("-".$clockoutRadius."minutes", strtotime($attendance['clock_out_requirement']));
        $nows = strtotime(date('H:i:s'));
		if($request->type == 'clock_in'){
			$outsideRadius = $clockins <= $nows;
			 if ($outsideRadius && !$request->radius_confirmation) {
				return MyHelper::checkGet([
					'need_confirmation' => true,
					'message' => 'Waktu Jam Masuk Anda akan diproses sebagai permintaan kehadiran dan memerlukan persetujuan dari atasan Anda.',
				]);
			}
		}
		if($request->type == 'clock_out'){
			$outsideRadius = $clockouts >= $nows;
			 if ($outsideRadius && !$request->radius_confirmation) {
				return MyHelper::checkGet([
					'need_confirmation' => true,
					'message' => 'Waktu Jam Keluar Anda akan diproses sebagai permintaan kehadiran dan memerlukan persetujuan dari atasan Anda.',
				]);
			}
		}
        $photoPath = null;
        $upload = MyHelper::uploadPhoto($request->photo, 'upload/employee/attendances/');
        if ($upload['status'] == 'success') {
            $photoPath = $upload['path'];
        }

        $attendance->storeClock([
            'type' => $request->type,
            'datetime' => MyHelper::reverseAdjustTimezone($date_time_now, $timeZone, 'Y-m-d H:i:s', true),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_name' => $request->location_name ?: '',
            'photo_path' => $photoPath,
            'status' => $outsideRadius ? 'Pending' : 'Approved',
            'approved_by' => null,
            'notes' => $request->notes,
        ]);
        $logs = $attendance->logs->where('type',$request->type)->first();
        if($outsideRadius){
            $user_sends = User::join('roles_features','roles_features.id_role', 'users.id_role')->where('id_feature',
            497)->get()->toArray();
            foreach($user_sends ?? [] as $user_send){
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Employee Attendance Pending',
                    $user_send['phone'],
                    [
                        'name_employee' => $employee['name'],
                        'phone_employee' => $employee['phone'],
                        'name_office' => $outlet['outlet_name'],
                        'time_attendance' => date('d F Y', strtotime($date_time_now)),
                        'role' => $role['role_name'],
                        'id_attendance' => $logs['id_employee_attendance_log'],
                        'category' => 'Attendance'
                    ], null, false, false, 'employee'
                );
            }
        }

        return MyHelper::checkGet([
            'need_confirmation' => false,
            'message' => 'Berhasil',
        ]);
    }
    public function histories(Request $request)
    {
        $request->validate([
            'month' => 'numeric|min:1|max:12|required',
            'year' => 'numeric|min:2020|max:3000',
        ]);
        $employee = $request->user();

        $outlet = $employee->outlet;
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        
        $scheduleMonth = $employee->employee_schedules()
            ->where('schedule_year', $request->year)
            ->where('schedule_month', $request->month)
            ->first() ?? null;
        
        $schedules = [];
        if($scheduleMonth){
            $schedules = $scheduleMonth->employee_schedule_dates()
            ->leftJoin('employee_attendances', 'employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
            ->get() ?? null;
        }
       
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $request->month, $request->year);
        
        $office_sch = OutletSchedule::where('id_outlet', $outlet['id_outlet'])->get()->toArray();
        $outletSchedule = [];
        foreach ($office_sch as $s) {
        	$outletSchedule[$s['day']] = [
        		'is_closed' => $s['is_closed'],
        		'time_start' => $s['open'],
        		'time_end' => $s['close'],
        	];
        }
        
        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
        ->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
        ->where('id_outlet', $outlet['id_outlet'])
        ->whereMonth('date_holidays.date', $request['month'])
        ->where(function($q) use ($request) {
            $q->whereYear('date_holidays.date', $request['year'])
                ->orWhere('yearly', '1');
        })
        ->get()
        ->keyBy('date');

        $histories = [];
        for ($i = 1; $i <= $numOfDays; $i++) { 
            $date = "{$request->year}-{$request->month}-$i";
            $hari = MyHelper::indonesian_date_v2($date, 'l');
        	$hari = str_replace('Jum\'at', 'Jumat', $hari);
            $isClosed = $outletSchedule[$hari]['is_closed'] ?? '1';
            if (isset($holidays[$date]) && isset($outletSchedule[$hari])) {
        		$isClosed = '1';
        	}

            $histories[$i] = [
                'date' => MyHelper::adjustTimezone($date, null, 'd M', true),
                'clock_in' => null,
                'clock_out' => null,
                'is_holiday' => $isClosed == '1' ? true : false,
                'breakdown' => [],
            ];
        }

        foreach ($schedules ?? [] as $schedule) {
            $history = &$histories[(int)date('d', strtotime($schedule->date))];
            $history['clock_in'] = $schedule->clock_in ? MyHelper::adjustTimezone($schedule->clock_in, $timeZone, 'H:i') : null;
            $history['clock_out'] = $schedule->clock_out ? MyHelper::adjustTimezone($schedule->clock_out, $timeZone, 'H:i') : null;
            if ($schedule->is_overtime) {
                $history['breakdown'][] = [
                    'name' => 'Lembur',
                    'time_start' => MyHelper::adjustTimezone($schedule->time_start, $timeZone, 'H:i'),
                    'time_end' => MyHelper::adjustTimezone($schedule->time_end, $timeZone, 'H:i'),
                ];
            }
        }
        
        return MyHelper::checkGet([
            'histories' => array_values($histories)
        ]);
    }

    public function setting(Request $request){
        $post = $request->json()->all();

        if(empty($post)){
            $clockin = Setting::where('key', 'employee_clock_in_tolerance')->first()['value']??null;
            $clockout = Setting::where('key', 'employee_clock_out_tolerance')->first()['value']??null;
            $radius = Setting::where('key', 'employee_attendance_max_radius')->first()['value']??null;

            $result = [
                'employee_clock_in_tolerance' => $clockin,
                'employee_clock_out_tolerance' => $clockout,
                'employee_attendance_max_radius' => $radius
            ];

            return response()->json(MyHelper::checkGet($result));
        }else{
            Setting::updateOrCreate(['key' => 'employee_clock_in_tolerance'], ['value' => $post['employee_clock_in_tolerance']]);
            Setting::updateOrCreate(['key' => 'employee_clock_out_tolerance'], ['value' => $post['employee_clock_out_tolerance']]);
            Setting::updateOrCreate(['key' => 'employee_attendance_max_radius'], ['value' => $post['employee_attendance_max_radius']]);

            return response()->json(MyHelper::checkUpdate(true));
        }
    }

    public function list(Request $request)
    {
        $result = User::join('employee_schedules', 'employee_schedules.id', 'users.id')
            ->join('roles', 'roles.id_role', 'users.id_role')
            ->join('employee_schedule_dates', 'employee_schedule_dates.id_employee_schedule', 'employee_schedules.id_employee_schedule')
            ->leftJoin('employee_attendances', 'employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date');
        $countTotal = null;
        $result->groupBy('users.id');

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'name',
                'role_name',
                'total_schedule',
                'total_ontime',
                'total_late',
                'total_absent',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('users.id, users.name, role_name, count(users.id) as total_schedule, SUM(CASE WHEN employee_attendances.is_on_time = 1 THEN 1 ELSE 0 END) as total_ontime, SUM(CASE WHEN employee_attendances.is_on_time = 0 AND (employee_attendances.clock_in IS NOT NULL OR employee_attendances.clock_out IS NOT NULL) THEN 1 ELSE 0 END) as total_late, SUM(CASE WHEN (employee_attendances.clock_in IS NULL and employee_attendances.clock_out IS NULL) THEN 1 ELSE 0 END) as total_absent');
        $result->orderBy('users.id');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['name'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'id_outlets';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('users.id_outlet', $rule[1]);
                }
            }

            $subject = 'id_role';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->$where('users.id_role', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('employee_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
    }

    public function detail(Request $request)
    {
        $result = User::join('employee_schedules', 'employee_schedules.id', 'users.id')
            ->join('employee_schedule_dates', 'employee_schedule_dates.id_employee_schedule', 'employee_schedules.id_employee_schedule')
            ->leftJoin('employee_attendances', 'employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
            ->with(['outlet', 'attendance_logs' => function ($query) { $query->where('status', 'Approved')->selectRaw('*, null as photo_url');}]);
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetail($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'date',
                'shift',
                'clock_in',
                'clock_out',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('*, COALESCE(employee_attendances.id_outlet, employee_schedules.id_outlet, users.id_outlet) AS id_outlet, (CASE WHEN (employee_attendances.clock_in IS NULL AND employee_attendances.clock_out IS NULL) THEN "Absent" WHEN is_on_time = 1 THEN "On Time" WHEN is_on_time = 0 THEN "Late" ELSE "" END) as status');
        $result->orderBy('users.id');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }
        foreach($result['data'] ?? [] as $r => $data){
            $outlet = Outlet::where('id_outlet',$data['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('cities.id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
            $result['data'][$r]['clock_in'] =  $data['clock_in'] ? MyHelper::adjustTimezone($data['clock_in'], $timeZone, 'H:i:s', true) : null;
            $result['data'][$r]['clock_out'] = $data['clock_out'] ? MyHelper::adjustTimezone($data['clock_out'], $timeZone, 'H:i:s', true) : null;
            $result['data'][$r]['clock_in_requirement'] = $data['clock_in_requirement'] ? MyHelper::adjustTimezone($data['clock_in_requirement'], $timeZone, 'H:i:s', true) : null;
            $result['data'][$r]['clock_out_requirement'] =  $data['clock_out_requirement'] ? MyHelper::adjustTimezone($data['clock_out_requirement'], $timeZone, 'H:i:s', true) : null;
        }
        return MyHelper::checkGet($result);
    }

    public function filterListDetail($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['shift'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'attendance_status';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    switch ($rule[1]) {
                        case 'ontime':
                            $query2->$where('is_on_time', 1);
                            break;

                        case 'late':
                            $query2->$where('is_on_time', 0);
                            break;

                        case 'absent':
                            $query2->{$where . 'Null'}('is_on_time');
                            break;
                    }
                }
            }

        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('employee_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('users.id', $rul[0], $rul[1]);
            }
        }
    }

    public function listPending(Request $request)
    {
        $result = User::join('employee_attendances', 'employee_attendances.id', 'users.id')
            ->join('roles', 'roles.id_role', 'users.id_role')
            ->join('employee_attendance_logs', function($join) {
                $join->on('employee_attendance_logs.id_employee_attendance', 'employee_attendances.id_employee_attendance')
                    ->where('employee_attendance_logs.status', 'Pending');
            });
        $countTotal = null;
        $result->groupBy('users.id');
            
        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListPending($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'name',
                'role_name',
                'total_pending',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }
        
        $result->selectRaw('users.id, name, role_name, count(*) as total_pending');
        $result->orderBy('users.id');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterListPending($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['name'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'id_outlets';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('users.id_outlet', $rule[1]);
                }
            }

            $subject = 'id_role';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->$where('users.id_role', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('employee_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
    }

    public function detailPending(Request $request)
    {
        $result = EmployeeAttendanceLog::selectRaw('*, null as photo_url')
            ->join('employee_attendances', 'employee_attendances.id_employee_attendance', 'employee_attendance_logs.id_employee_attendance')
            ->where('employee_attendance_logs.status', 'Pending')
            ->join('employee_schedule_dates', 'employee_schedule_dates.id_employee_schedule_date', 'employee_attendances.id_employee_schedule_date');
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetailPending($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'datetime',
                'shift',
                'clock_in',
                'clock_out',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        // $result->selectRaw('*, ');
        $result->orderBy('employee_attendance_logs.id_employee_attendance_log');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        foreach($result['data'] ?? [] as $r => $data){
            $outlet = Outlet::where('id_outlet',$data['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('cities.id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
            $result['data'][$r]['datetime'] =  $data['datetime'] ? MyHelper::adjustTimezone($data['datetime'], $timeZone, 'Y-m-d H:i:s', true) : null;
            $result['data'][$r]['clock_in_requirement'] = $data['clock_in_requirement'] ? MyHelper::adjustTimezone($data['clock_in_requirement'], $timeZone, 'H:i:s', true) : null;
            $result['data'][$r]['clock_out_requirement'] =  $data['clock_out_requirement'] ? MyHelper::adjustTimezone($data['clock_out_requirement'], $timeZone, 'H:i:s', true) : null;
        }

        return MyHelper::checkGet($result);
    }

    public function filterListDetailPending($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['shift', 'type'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('employee_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('employee_attendances.id', $rul[0], $rul[1]);
            }
        }
    }

    public function updatePending(Request $request)
    {
        $request->validate([
            'status' => 'string|in:Approved,Rejected,Approve,Reject',
        ]);

        if($request->status=='Approve'){
            $request->status = 'Approved';
        }
        if($request->status=='Reject'){
            $request->status = 'Rejected';

        }
        
        $log = EmployeeAttendanceLog::find($request->id_employee_attendance_log);
        if (!$log) {
            return [
                'status' => 'fail',
                'messages' => ['Selected pending attendance not found']
            ];
        }
        $update = [
            'status' => $request->status
        ];
        if(isset($request->approve_notes) && !empty($request->approve_notes)){
            $update['approve_notes'] = $request->approve_notes;
        }
        $log->update($update);
        $log->employee_attendance->recalculate();

        $user_attendance = User::join('employee_attendances', 'employee_attendances.id', 'users.id')->join('employee_attendance_logs','employee_attendance_logs.id_employee_attendance','employee_attendances.id_employee_attendance')->where('employee_attendance_logs.id_employee_attendance_log', $request->id_employee_attendance_log)->first();
        $outlet = Outlet::where('id_outlet',$user_attendance['id_outlet'])->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        $date_time_now = MyHelper::adjustTimezone($user_attendance['datetime'], $timeZone, 'd F Y', true);
        $role = Role::where('id_role',$user_attendance['id_role'])->first();

        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT',
        ];

        if($request->status=='Approved'){
            $keyAutocrm = 'Employee Attendance Pending Approve';
        }
        if($request->status=='Rejected'){
            $keyAutocrm = 'Employee Attendance Pending Reject';

        }
        $autocrm = app($this->autocrm)->SendAutoCRM(
            $keyAutocrm,
            $user_attendance['phone'],
            [
                'name_employee' => $user_attendance['name'],
                'phone_employee' => $user_attendance['phone'],
                'name_office' => $outlet['name_outlet'],
                'time_attendance' => $date_time_now,
                'role' => $role['role_name'],
                'user_update' => $request->user_update ?? $request->user()->name,
                'category' => 'Attendance'
            ], null, false, false, 'employee'
        );
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success ' . ($request->status == 'Approved' ? 'approve' : 'reject') . ' pending attendance'
            ],
        ];
    }

    public function checkCurrentTime($data,$timeZone = 7){
        $check = true;
        if(isset($data['date']) && !empty($data['date'])){
            if(date('Y-m-d') < date('Y-m-d',strtotime($data['date']))){
                $check = false;
            }elseif(date('Y-m-d') == date('Y-m-d',strtotime($data['date']))){
                if(isset($data['clock_in']) && !empty($data['clock_in'])){
                    $clock_in = MyHelper::reverseAdjustTimezone($data['clock_in'], $timeZone, 'H:i', true);
                    if(date('H:i') < date('H:i',strtotime($clock_in))){
                        $check = false;
                    }
                }
                if(isset($data['clock_out']) && !empty($data['clock_out'])){
                    $clock_out = MyHelper::reverseAdjustTimezone($data['clock_out'], $timeZone, 'H:i', true);
                    if(date('H:i') < date('H:i',strtotime($clock_out))){
                        $check = false;
                    }
                }
            }
        }
        return $check;
    }

    public function checkDateRequest(Request $request){
        $post = $request->all();
        $employee = $request->user();
        
        $check_date = $this->checkCurrentTime($post);
        if(!$check_date){
            return [
                'status'=>'fail',
                'messages'=>['Request Absen maksimal adalah sekarang']
            ];
        }

        $outlet = $employee->outlet()->select('outlet_name', 'outlet_latitude', 'outlet_longitude', 'id_city')->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

        $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee['id'])->first();
        $array_date = explode('-',$post['date']);
        $schedule_month = EmployeeSchedule::where('id',$employee['id'])->where('schedule_month',$array_date[1])->where('schedule_year',$array_date[0])->first();
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
        $data = [
           'shift' => null,
           'schedule_in' => $type_shift['office_hour_start'] ?? null,
           'schedule_out' => $type_shift['office_hour_end'] ?? null,
        ];

        if($type_shift['office_hour_type'] == 'Use Shift' || isset($schedule_month['id_office_hour_shift'])){
            $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                        ->join('users','users.id','employee_schedules.id')
                                                        ->where('users.id', $employee['id'])
                                                        ->whereDate('employee_schedule_dates.date', $post['date'])
                                                        ->first();
            if(!$schedule_date){
                return response()->json(['status' => 'fail', 'messages' => ['Jadwal karyawan pada tanggal ini belum dibuat']]);
            }

            $data['shift'] = $schedule_date['shift'] ?? null;
            $data['schedule_in'] = $schedule_date['time_start'] ? MyHelper::adjustTimezone(date('H:i', strtotime($schedule_date['time_start'])), $timeZone, 'H:i', true) : null;
            $data['schedule_out'] = $schedule_date['time_end'] ? MyHelper::adjustTimezone(date('H:i', strtotime($schedule_date['time_end'])), $timeZone, 'H:i', true) : null;

        }

        return MyHelper::checkGet($data);
    }

    public function storeRequest(Request $request){
        $post = $request->all();
        if(!isset($post['notes']) && empty($post['notes'])){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Mohon mengisi keterangan dengan jelas.']
            ]);
        }

        $employee = $request->user();
        $outlet = $employee->outlet()->select('id_outlet','outlet_name', 'id_city')->first();
        $role = $employee->role;
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

        $check_date = $this->checkCurrentTime($post,$timeZone);
        if(!$check_date){
            return [
                'status'=>'fail',
                'messages'=>['Request Absen maksimal adalah sekarang']
            ];
        }

        $time_zone = [
            '7' => 'WIB',
            '8' => 'WITA',
            '9' => 'WIT'
        ];
        
        $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee['id'])->first();
        $array_date = explode('-',$post['date']);
        $schedule_month = EmployeeSchedule::where('id',$employee['id'])->where('schedule_month',$array_date[1])->where('schedule_year',$array_date[0])->first();
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

        if($type_shift['office_hour_type'] == 'Use Shift' || isset($schedule_month['id_office_hour_shift'])){
            $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                                        ->join('users','users.id','employee_schedules.id')
                                                        ->where('users.id', $employee['id'])
                                                        ->whereDate('employee_schedule_dates.date', $post['date'])
                                                        ->first();
            if(!$schedule_date){
                return response()->json(['status' => 'fail', 'messages' => ['Jadwal karyawan pada tanggal ini belum dibuat']]);
            }
        }

        DB::beginTransaction();

        $store = EmployeeAttendanceRequest::create([
            'id' => $employee['id'],
            'id_outlet' => $outlet['id_outlet'],
            'attendance_date' => $post['date'],
            'clock_in' => $post['clock_in'] ? MyHelper::reverseAdjustTimezone(date('H:i:s', strtotime($post['clock_in'])), $timeZone, 'Y-m-d H:i:s', true) : null,
            'clock_out' => $post['clock_out'] ? MyHelper::reverseAdjustTimezone(date('H:i:s', strtotime($post['clock_out'])), $timeZone, 'Y-m-d H:i:s', true) : null,
            'notes' => $post['notes'],
        ]);
        if(!$store){
            DB::rollBack();
            return response()->json([
                'status' => 'fail', 
                'messages' => ['Gagal mengajukan permintaan presensi']
            ]);
        }
        $user_sends = User::join('roles_features','roles_features.id_role', 'users.id_role')->where('id_feature',
            500)->get()->toArray();
        foreach($user_sends ?? [] as $user_send){
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Employee Attendance Request',
                $user_send['phone'],
                [
                    'name_employee' => $employee['name'],
                    'phone_employee' => $employee['phone'],
                    'name_office' => $outlet['outlet_name'],
                    'time_attendance' => date('d F Y',strtotime($post['date'])),
                    'role' => $role['role_name'],
                    'id_attendance' => $store['id_employee_attendance_request'],
                    'category' => 'Attendance'
                ], null, false, false, 'employee'
            );
        }

        DB::commit();
        return response()->json(['status' => 'success', 'messages' => ['Berhasil mengajukan permintaan presensi, silahkan menunggu persetujuan']]);
    }

    public function historiesRequest(Request $request)
    {
        $employee = $request->user();
        $outlet = $employee->outlet;
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        
        $histories = EmployeeAttendanceRequest::where('id', $employee['id'])->select('attendance_date', 'clock_in', 'clock_out', 'status', 'notes')->orderBy('attendance_date','asc')->paginate(10)->toArray();
        $data = [];
        foreach($histories['data'] ?? [] as $val){
            if(isset($val['clock_in'])){
                $data[] = [
                    'date' => MyHelper::dateFormatInd($val['attendance_date'], true, false, false),
                    'request' => 'In '.MyHelper::adjustTimezone($val['clock_in'], $timeZone, 'H:i'),
                    'status' => $val['status'],
                    'notes' => $val['notes']   
                ];
            }
            if(isset($val['clock_out'])){
                $data[] = [
                    'date' => MyHelper::dateFormatInd($val['attendance_date'], true, false, false),
                    'request' => 'Out '.MyHelper::adjustTimezone($val['clock_out'], $timeZone, 'H:i'),
                    'status' => $val['status'],
                    'notes' => $val['notes']   
                ];
            }
        }
        $histories['data'] = $data;
        return MyHelper::checkGet($histories);
    }

    public function listRequest(Request $request)
    {
        $result = EmployeeAttendanceRequest::join('users','users.id', 'employee_attendance_requests.id')
                ->join('roles', 'roles.id_role', 'users.id_role')
                ->join('outlets', 'outlets.id_outlet', 'users.id_outlet')
                ->where('employee_attendance_requests.status', 'Pending');
        $countTotal = null;
        $result->groupBy('employee_attendance_requests.id');
            
        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterlistRequest($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'name',
                'outlet_name',
                'role_name',
                'total_pending',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }
        
        $result->selectRaw('users.id, name, outlet_name, role_name, count(*) as total_pending');
        $result->orderBy('users.id');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        return MyHelper::checkGet($result);
    }

    public function filterlistRequest($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['name'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

            $subject = 'id_outlets';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('users.id_outlet', $rule[1]);
                }
            }

            $subject = 'id_role';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->$where('users.id_role',  $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('employee_attendance_requests.created_at', $rul[0], $rul[1]);
            }
        }
    }

    public function detailRequest(Request $request)
    {
        $result = EmployeeAttendanceRequest::selectRaw('*, null as photo_url')
            ->where('employee_attendance_requests.status', 'Pending');
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetailRequest($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'attendance_date',
                'clock_in',
                'clock_out',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        // $result->selectRaw('*, ');
        $result->orderBy('employee_attendance_requests.id_employee_attendance_request');

        if ($request->page) {
            $result = $result->paginate($request->length ?: 15)->toArray();
            if (is_null($countTotal)) {
                $countTotal = $result['total'];
            }
            // needed by datatables
            $result['recordsTotal'] = $countTotal;
        } else {
            $result = $result->get();
        }

        foreach($result['data'] ?? [] as $r => $data){
            $outlet = Outlet::where('id_outlet',$data['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('cities.id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
            $result['data'][$r]['clock_in'] =  $data['clock_in'] ? MyHelper::adjustTimezone($data['clock_in'], $timeZone, 'H:i', true) : null;
            $result['data'][$r]['clock_out'] = $data['clock_out'] ? MyHelper::adjustTimezone($data['clock_out'], $timeZone, 'H:i', true) : null;
            $result['data'][$r]['attendance_date'] =  $data['attendance_date'] ? MyHelper::adjustTimezone($data['attendance_date'], $timeZone, 'Y-m-d', true) : null;
            $result['data'][$r]['shift'] =  null;

            $clock_in_requirement = null;
            $clock_out_requirement =  null;
            $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$data['id'])->first();
            if(empty($type_shift['office_hour_type'])){
                $setting_default = Setting::where('key', 'employee_office_hour_default')->first();
                if($setting_default){
                    $type_shift = EmployeeOfficeHour::where('id_employee_office_hour',$setting_default['value'])->first();
                    if(empty($type_shift)){
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Shift schedule has not been created']
                        ]);
                    }
                }
            }
            if(isset($type_shift['office_hour_type'])){
                $array_date = explode('-',$data['attendance_date']);
                $schedule_month = EmployeeSchedule::where('id',$data['id'])->where('schedule_month',$array_date[1])->where('schedule_year',$array_date[0])->first();
                $clock_in_requirement = $type_shift['office_hour_start'];
                $clock_out_requirement = $type_shift['office_hour_end'];
                if($type_shift['office_hour_type']=='Use Shift' || isset($schedule_month['id_office_hour_shift'])){
                    $schedule_date = EmployeeScheduleDate::join('employee_schedules','employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
                                        ->join('users','users.id','employee_schedules.id')
                                        ->where('users.id', $data['id'])
                                        ->whereDate('employee_schedule_dates.date', $data['attendance_date'])
                                        ->first();
                    if(isset($schedule_date)){
                        $result['data'][$r]['shift'] = $schedule_date['shift'] ?? null;
                        $clock_in_requirement = $schedule_date['time_start'] ? MyHelper::adjustTimezone(date('H:i', strtotime($schedule_date['time_start'])), $timeZone, 'H:i', true) : null;
                        $clock_out_requirement = $schedule_date['time_end'] ? MyHelper::adjustTimezone(date('H:i', strtotime($schedule_date['time_end'])), $timeZone, 'H:i', true) : null;
                    }
                }
            }
            $result['data'][$r]['clock_in_requirement'] = $clock_in_requirement;
            $result['data'][$r]['clock_out_requirement'] = $clock_out_requirement;

        }

        return MyHelper::checkGet($result);
    }

    public function filterListDetailRequest($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            if (!($var['operator']?? false) && !($var['parameter']?? false)) continue;
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }

        $query->where(function($query2) use ($operator, $newRule) {
            $where=$operator=='and'?'where':'orWhere';
            $subjects=['shift', 'type'];
            foreach ($subjects as $subject) {
                if($rules2=$newRule[$subject]??false){
                    foreach ($rules2 as $rule) {
                        $query2->$where($subject,$rule[0],$rule[1]);
                    }
                }
            }

        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('employee_attendance_requests.created_at', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('employee_attendance_requests.id', $rul[0], $rul[1]);
            }
        }
    }

    public function updateRequest(Request $request){
        $request->validate([
            'status' => 'string|in:Accepted,Rejected,Approve,Reject',
        ]);
        if($request->status=='Approve'){
            $request->status = 'Accepted';
        }
        if($request->status=='Reject'){
            $request->status = 'Rejected';
        }

        $log_req = EmployeeAttendanceRequest::find($request->id_employee_attendance_request);
        if (!$log_req) {
            return [
                'status' => 'fail',
                'messages' => ['Selected request attendance not found']
            ];
        }
        DB::beginTransaction();
        $update = [
            'status' => $request->status
        ];
        if(isset($request->approve_notes) && !empty($request->approve_notes)){
            $update['approve_notes'] = $request->approve_notes;
        }
        $log_req->update($update);
        $final = true;
        if($request->status == 'Accepted'){

            $outlet = Outlet::where('id_outlet', $log_req['id_outlet'])->first();

            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

            $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$log_req['id'])->first();

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

            //schedule_date
            $array_date = explode('-',$log_req['attendance_date']);
            $schedule = EmployeeSchedule::where('id', $log_req['id'])->where('id_outlet', $log_req['id_outlet'])->where('schedule_month', $array_date[1])->where('schedule_year', $array_date[0])->first();
            if(!$schedule && $type_shift['office_hour_type'] == 'Use Shift'){
                DB::rollBack();
                return response()->json([
                    'status'=>'fail',
                    'messages'=>['Schedule has not been created']
                ]);
            }elseif(!$schedule && $type_shift['office_hour_type'] == 'Without Shift'){
                $schedule = EmployeeSchedule::create([
                    'id' => $log_req['id'],
                    'id_outlet' => $log_req['id_outlet'],
                    'schedule_month' => $array_date[1],
                    'schedule_year' => $array_date[0],
                    'request_at' => date('Y-m-d H:i:s')
                ]);
            }
            $schedule_date = EmployeeScheduleDate::where('id_employee_schedule', $schedule['id_employee_schedule'])->whereDate('date', $log_req['attendance_date'])->first();
            if(!$schedule_date && $type_shift['office_hour_type'] == 'Use Shift'){
                DB::rollBack();
                return response()->json([
                    'status'=>'fail',
                    'messages'=>['Schedule date has not been created']
                ]);
            }elseif(!$schedule_date && $type_shift['office_hour_type'] == 'Without Shift'){
                $schedule_date = EmployeeScheduleDate::create([
                    'id_employee_schedule' => $schedule['id_employee_schedule'],
                    'date' => $log_req['attendance_date'],
                    'is_overtime' => 0,
                    'time_start' => MyHelper::reverseAdjustTimezone($type_shift['office_hour_start'] ?? null, $timeZone, 'H:i:s', true),
                    'time_end' => MyHelper::reverseAdjustTimezone($type_shift['office_hour_end'] ?? null, $timeZone, 'H:i:s', true),
                ]);
            }

            $attendance = EmployeeAttendance::where('id', $log_req['id'])->where('id_outlet', $log_req['id_outlet'])->where('id_employee_schedule_date', $schedule_date['id_employee_schedule_date'])->whereDate('attendance_date', $log_req['attendance_date'])->first();
            if(!$attendance){
                $attendance = EmployeeAttendance::create([
                    'id_employee_schedule_date' => $schedule_date['id_employee_schedule_date'],
                    'id_outlet' => $log_req['id_outlet'],
                    'attendance_date' => $log_req['attendance_date'],
                    'id' => $log_req['id'],
                    'clock_in_requirement' => $schedule_date->time_start,
                    'clock_out_requirement' => $schedule_date->time_end,
                    'clock_in_tolerance' => MyHelper::setting('employee_clock_in_tolerance', 'value', 15),
                    'clock_out_tolerance' => MyHelper::setting('employee_clock_out_tolerance', 'value', 0),
                ]);
            }
            
            if(isset($log_req['clock_in'])){
                $clock_in = EmployeeAttendance::find($attendance['id_employee_attendance']);
                $clock_in->storeClock([
                    'type' => 'clock_in',
                    'datetime' => date('Y-m-d H:i:s', strtotime($log_req['attendance_date'].' '.$log_req['clock_in'])),
                    'latitude' => 0,
                    'longitude' => 0,
                    'status' => 'Approved',
                    'approved_by' =>  $request->id ?? $request->user()->id,
                    'notes' => $log_req['notes'],
                ]);
                if(!$clock_in){
                    $final = false;
                }
            }

            if(isset($log_req['clock_out'])){
                $clock_out = EmployeeAttendance::find($attendance['id_employee_attendance']);
                $clock_out->storeClock([
                    'type' => 'clock_out',
                    'datetime' => date('Y-m-d H:i:s', strtotime($log_req['attendance_date'].' '.$log_req['clock_out'])),
                    'latitude' => 0,
                    'longitude' => 0,
                    'status' => 'Approved',
                    'approved_by' =>  $request->id ?? $request->user()->id,
                    'notes' => $log_req['notes'],
                ]);
                if(!$clock_out){
                    $final = false;
                }
            }
        }elseif($request->status == 'Rejected'){
            DB::commit();
            $user_attendance = User::join('employee_attendance_requests', 'employee_attendance_requests.id', 'users.id')->where('employee_attendance_requests.id_employee_attendance_request', $request->id_employee_attendance_request)->first();
            $outlet = Outlet::where('id_outlet',$user_attendance['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
            $date_time_now = MyHelper::adjustTimezone($user_attendance['attendance_date'], $timeZone, 'd F Y', true);
            $role = Role::where('id_role',$user_attendance['id_role'])->first();
    
            $time_zone = [
                '7' => 'WIB',
                '8' => 'WITA',
                '9' => 'WIT',
            ];
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Employee Attendance Request Reject',
                $user_attendance['phone'],
                [
                    'name_employee' => $user_attendance['name'],
                    'phone_employee' => $user_attendance['phone'],
                    'name_office' => $outlet['outlet_name'],
                    'time_attendance' => $date_time_now,
                    'role' => $role['role_name'],
                    'user_update' => $request->user_update ?? $request->user()->name,
                    'category' => 'Attendance'
                ], null, false, false, 'employee'
            );
            return [
                'status' => 'success',
                'messages' => ['Success to reject request attendance'],
            ];
        }
        if($final){
            DB::commit();
            $user_attendance = User::join('employee_attendance_requests', 'employee_attendance_requests.id', 'users.id')->where('employee_attendance_requests.id_employee_attendance_request', $request->id_employee_attendance_request)->first();
            $outlet = Outlet::where('id_outlet',$user_attendance['id_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
            $date_time_now = MyHelper::adjustTimezone($user_attendance['attendance_date'], $timeZone, 'd F Y', true);
            $role = Role::where('id_role',$user_attendance['id_role'])->first();
    
            $time_zone = [
                '7' => 'WIB',
                '8' => 'WITA',
                '9' => 'WIT',
            ];
            $autocrm = app($this->autocrm)->SendAutoCRM(
                'Employee Attendance Request Approve',
                $user_attendance['phone'],
                [
                    'name_employee' => $user_attendance['name'],
                    'phone_employee' => $user_attendance['phone'],
                    'name_office' => $outlet['outlet_name'],
                    'time_attendance' => $date_time_now,
                    'role' => $role['role_name'],
                    'user_update' => $request->user_update ?? $request->user()->name,
                    'category' => 'Attendance'
                ], null, false, false, 'employee'
            );
            return [
                'status' => 'success',
                'messages' => ['Success to approve request attendance'],
            ];
        }else{
            DB::rollBack();
            return [
                'status' => 'fail',
                'messages' => ['Failed to approve reequest attendance'],
            ];
        }
    }

    public function delete(Request $request){
        $post = $request->all();
        $employee = EmployeeAttendance::find($post['id_employee_attendance']);
        if(!$employee){
            return [
                'status' => 'fail',
                'messages' => ['Failed to delete attendance'],
            ];
        }
        $request_att = EmployeeAttendanceRequest::where('id',$employee['id'])->where('id_outlet', $employee['id_outlet'])->whereDate('attendance_date', $employee['attendance_date'])->where('status', 'Accepted')->first();
        if($request_att){
            $delete_req = EmployeeAttendanceRequest::where('id_employee_attendance_request', $request_att['id_employee_attendance_request'])->delete();
        }
        $employee->delete();

        return MyHelper::checkDelete($employee);
    }
}
