<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use App\Http\Models\User;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Holiday;
use Modules\Employee\Entities\EmployeeSchedule;
use Modules\Employee\Entities\EmployeeAttendance;
use Modules\Employee\Entities\EmployeeAttendanceLog;
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use Modules\Users\Entities\Role;
use App\Http\Models\Setting;


use DB;
use Modules\Employee\Entities\EmployeeOfficeHour;

class ApiEmployeeAttendanceController extends Controller
{
    public function liveAttendance(Request $request)
    {
        $today = date('Y-m-d');
        $employee = $request->user();
        $outlet = $employee->outlet()->select('outlet_name', 'outlet_latitude', 'outlet_longitude')->first();
        $shift = false;
        $outlet->setHidden(['call', 'url']);
        // get current schedule
        $todaySchedule = $employee->employee_schedules()
            ->selectRaw('date, min(time_start) as clock_in_requirement, max(time_end) as clock_out_requirement, shift')
            ->join('employee_schedule_dates', 'employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule');
        
        if($employee->role->office_hour['office_hour_type'] == 'Use Shift'){
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

        $result = [
            'clock_in_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_in_requirement, null, 'H:i', true),
            'clock_out_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_out_requirement, null, 'H:i', true),
            'shift_name' => $todaySchedule->shift ?? null,
            'outlet' => $outlet,
            'logs' => $attendance->logs()->get()->transform(function($item) {
                return [
                    'location_name' => $item->location_name,
                    'latitude' => $item->latitude,
                    'longitude' => $item->longitude,
                    'longitude' => $item->longitude,
                    'type' => ucwords(str_replace('_', ' ',$item->type)),
                    'photo' => $item->photo_path ? config('url.storage_url_api') . $item->photo_path : null,
                    'date' => MyHelper::adjustTimezone($item->datetime, null, 'l, d F Y', true),
                    'time' => MyHelper::adjustTimezone($item->datetime, null, 'H:i'),
                    'notes' => $item->notes ?: '',
                ];
            }),
        ];

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
        $shift = false;
        if($employee->role->office_hour['office_hour_type'] == 'Use Shift'){
            $shift = true;
        }else{
            $shift = false;
        }
        $outlet = $employee->outlet;
        $attendance = $employee->getAttendanceByDate(date('Y-m-d'), $shift);

        if ($request->type == 'clock_out' && !$attendance->logs()->where('type', 'clock_in')->exists()) {
            return [
                'status' => 'fail',
                'messages' => ['Tidak bisa melakukan Clock Out sebelum melakukan Clock In'],
            ];
        }

        $maximumRadius = MyHelper::setting('employee_attendance_max_radius', 'value', 50);
        $distance = MyHelper::getDistance($request->latitude, $request->longitude, $outlet->outlet_latitude, $outlet->outlet_longitude);
        $outsideRadius = $distance > $maximumRadius;

        if ($outsideRadius && !$request->radius_confirmation) {
            return MyHelper::checkGet([
                'need_confirmation' => true,
                'message' => 'Waktu Jam Masuk/Keluar Anda akan diproses sebagai permintaan kehadiran dan memerlukan persetujuan dari atasan Anda.',
            ]);
        }

        $photoPath = null;
        $upload = MyHelper::uploadPhoto($request->photo, 'upload/employee/attendances/');
        if ($upload['status'] == 'success') {
            $photoPath = $upload['path'];
        }

        $attendance->storeClock([
            'type' => $request->type,
            'datetime' => date('Y-m-d H:i:s'),
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'location_name' => $request->location_name ?: '',
            'photo_path' => $photoPath,
            'status' => $outsideRadius ? 'Pending' : 'Approved',
            'approved_by' => null,
            'notes' => $request->notes,
        ]);

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
        $scheduleMonth = $employee->employee_schedules()
            ->where('schedule_year', $request->year)
            ->where('schedule_month', $request->month)
            ->first();
        // $schedules = $scheduleMonth->employee_schedule_dates()->leftJoin('employee_attendances', 'employee_attendances.id_employee_attendance', 'employee_schedule_dates.id_employee_attendance')->orderBy('is_overtime')->get();
        $schedules = $scheduleMonth->employee_schedule_dates()
            ->leftJoin('employee_attendances', 'employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
            ->get();
        $numOfDays = cal_days_in_month(CAL_GREGORIAN, $request->month, $request->year);
        
        $histories = [];
        for ($i = 1; $i <= $numOfDays; $i++) { 
            $date = "{$request->year}-{$request->month}-$i";
            $histories[$i] = [
                'date' => MyHelper::adjustTimezone($date, null, 'd M', true),
                'clock_in' => null,
                'clock_out' => null,
                'is_holiday' => true,
                'breakdown' => [],
            ];
        }

        foreach ($schedules as $schedule) {
            $history = &$histories[(int)date('d', strtotime($schedule->date))];
            $history['clock_in'] = $schedule->clock_in ? MyHelper::adjustTimezone($schedule->clock_in, null, 'H:i') : null;
            $history['clock_out'] = $schedule->clock_out ? MyHelper::adjustTimezone($schedule->clock_out, null, 'H:i') : null;
            $history['is_holiday'] = false;
            if ($schedule->is_overtime) {
                $history['breakdown'][] = [
                    'name' => 'Lembur',
                    'time_start' => MyHelper::adjustTimezone($schedule->time_start, null, 'H:i'),
                    'time_end' => MyHelper::adjustTimezone($schedule->time_end, null, 'H:i'),
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
            $subjects=['name', 'id_role', 'id_outlet'];
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
            'status' => 'string|in:Approved,Rejected',
        ]);
        $log = EmployeeAttendanceLog::find($request->id_employee_attendance_log);
        if (!$log) {
            return [
                'status' => 'fail',
                'messages' => ['Selected pending attendance not found']
            ];
        }
        $log->update(['status' => $request->status]);
        $log->employee_attendance->recalculate();
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success ' . ($request->status == 'Approved' ? 'approve' : 'reject') . ' pending attendance'
            ],
        ];
    }

}
