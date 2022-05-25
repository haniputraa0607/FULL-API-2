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
use Modules\Employee\Entities\EmployeeAttendanceLog;
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use Modules\Users\Entities\Role;
use App\Http\Models\Setting;


use DB;
use Modules\Employee\Entities\EmployeeOfficeHour;
use Modules\Employee\Entities\EmployeeOutletAttendance;
use Modules\Employee\Entities\EmployeeOutletAttendanceRequest;
use Modules\Employee\Entities\EmployeeOutletAttendanceLog;

class ApiEmployeeAttendaceOutletController extends Controller
{

    public function listOutlet(Request $request){
        $post = $request->all();
        if(empty($post['latitude']) && empty($post['longitude'])){
            return response()->json(['status' => 'fail', 'messages' => ['Latitude and Longitude can not be empty']]);
        }

        $outlet = Outlet::selectRaw('outlets.id_outlet, outlets.outlet_name,
                    (111.111 * DEGREES(ACOS(LEAST(1.0, COS(RADIANS(outlets.outlet_latitude))
                         * COS(RADIANS('.$post['latitude'].'))
                         * COS(RADIANS(outlets.outlet_longitude - '.$post['longitude'].'))
                         + SIN(RADIANS(outlets.outlet_latitude))
                         * SIN(RADIANS('.$post['latitude'].')))))) AS distance_in_km' )
            ->where('outlets.outlet_status', 'Active')
            ->where('outlets.type', 'Outlet')
            ->whereNotNull('outlets.outlet_latitude')
            ->whereNotNull('outlets.outlet_longitude')
            ->orderBy('distance_in_km', 'asc')
            ->get()->toArray();
        $outlet = array_map(function($value){
            unset($value['distance_in_km']);
            unset($value['call']);
            unset($value['url']);
            return $value;
        },$outlet);

        return MyHelper::checkGet($outlet);

    }

    public function liveAttendance(Request $request)
    {
        $post = $request->all();
        $today = date('Y-m-d');
        $employee = $request->user();
        $outlet = Outlet::select('outlet_name', 'outlet_latitude', 'outlet_longitude', 'id_city')->where('id_outlet', $post['id_outlet'])->first();
        $shift = false;
        $outlet->setHidden(['call', 'url']);
        // get current schedule
        $todaySchedule = $employee->employee_schedules()
            ->selectRaw('date, min(time_start) as start_shift, max(time_end) as end_shift, shift')
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

        $attendance = $employee->getAttendanceByDateOutlet($post['id_outlet'], $todaySchedule, $shift);

        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

        $result = [
            'start_shift' => MyHelper::adjustTimezone($todaySchedule->start_shift, $timeZone, 'H:i', true),
            'end_shift' => MyHelper::adjustTimezone($todaySchedule->end_shift, $timeZone, 'H:i', true),
            'shift_name' => $todaySchedule->shift ?? null,
            'outlet' => $outlet,
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
        
        $outlet = Outlet::where('id_outlet', $request['id_outlet'])->first();
        
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        
        $attendance = $employee->getAttendanceByDateOutlet($outlet['id_outlet'], date('Y-m-d'), $shift);

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
        $upload = MyHelper::uploadPhoto($request->photo, 'upload/employee/attendances-outlet/');
        if ($upload['status'] == 'success') {
            $photoPath = $upload['path'];
        }

        $attendance->storeClock([
            'type' => $request->type,
            'datetime' => MyHelper::reverseAdjustTimezone(date('Y-m-d H:i:s'), $timeZone, 'Y-m-d H:i:s', true),
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

        $outlet = Outlet::where('id_outlet', $request['id_outlet'])->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
        
        $scheduleMonth = $employee->employee_schedules()
            ->where('schedule_year', $request->year)
            ->where('schedule_month', $request->month)
            ->first() ?? null;
        if(!$scheduleMonth){
            return [
                'status' => 'fail',
                'messages' => ['Tidak ada riwayat absensi pada bulan ini'],
            ];
        }
        // $schedules = $scheduleMonth->employee_schedule_dates()->leftJoin('employee_outlet_attendances', 'employee_outlet_attendances.id_employee_attendance', 'employee_schedule_dates.id_employee_attendance')->orderBy('is_overtime')->get();
        $schedules = $scheduleMonth->employee_schedule_dates()
            ->leftJoin('employee_outlet_attendances', 'employee_outlet_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
            ->get() ?? null;
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

        foreach ($schedules ?? [] as $schedule) {
            $history = &$histories[(int)date('d', strtotime($schedule->date))];
            $history['clock_in'] = $schedule->clock_in ? MyHelper::adjustTimezone($schedule->clock_in, $timeZone, 'H:i') : null;
            $history['clock_out'] = $schedule->clock_out ? MyHelper::adjustTimezone($schedule->clock_out, $timeZone, 'H:i') : null;
            $history['is_holiday'] = false;
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

    public function list(Request $request)
    {
        $result = User::join('employee_schedules', 'employee_schedules.id', 'users.id')
            ->join('roles', 'roles.id_role', 'users.id_role')
            ->join('employee_schedule_dates', 'employee_schedule_dates.id_employee_schedule', 'employee_schedules.id_employee_schedule')
            ->join('outlets','outlets.id_outlet','users.id_outlet')
            ->leftJoin('employee_outlet_attendances', 'employee_outlet_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date');
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
                'outlet_name',
                'total_attendance',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('users.id, users.name, role_name, outlets.outlet_name, SUM(CASE WHEN employee_outlet_attendances.id_employee_outlet_attendance IS NOT NULL THEN 1 ELSE 0 END) as total_attendance');
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

            $subject = 'id_office';
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
            ->leftJoin('employee_outlet_attendances', 'employee_outlet_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')
            ->leftJoin('outlets as outlet_att','outlet_att.id_outlet','employee_outlet_attendances.id_outlet')
            ->leftJoin('outlets as office','office.id_outlet','users.id_outlet')
            ->with(['outlet_attendance_logs' => function ($query) { $query->where('status', 'Approved')->selectRaw('*, null as photo_url');}]);
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetail($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'date',
                'outlet',
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

        $result->selectRaw('*, COALESCE(employee_outlet_attendances.id_outlet, employee_schedules.id_outlet, users.id_outlet) AS id_outlet, outlet_att.outlet_name as in_outlet, office.outlet_name as office, outlet_att.id_outlet as id_in_outlet');
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
            $outlet = Outlet::where('id_outlet',$data['id_in_outlet'])->first();
            $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
            ->where('cities.id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
            $result['data'][$r]['clock_in'] =  $data['clock_in'] ? MyHelper::adjustTimezone($data['clock_in'], $timeZone, 'H:i:s', true) : null;
            $result['data'][$r]['clock_out'] = $data['clock_out'] ? MyHelper::adjustTimezone($data['clock_out'], $timeZone, 'H:i:s', true) : null;
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

            $subject = 'id_outlet';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('outlet_att.id_outlet', $rule[1]);
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
        $result = User::join('employee_outlet_attendances', 'employee_outlet_attendances.id', 'users.id')
            ->join('roles', 'roles.id_role', 'users.id_role')
            ->join('employee_outlet_attendance_logs', function($join) {
                $join->on('employee_outlet_attendance_logs.id_employee_outlet_attendance', 'employee_outlet_attendances.id_employee_outlet_attendance')
                    ->where('employee_outlet_attendance_logs.status', 'Pending');
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
                $query->whereDate('employee_outlet_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
    }

    public function detailPending(Request $request)
    {
        $result = EmployeeOutletAttendanceLog::selectRaw('*, null as photo_url')
            ->join('employee_outlet_attendances', 'employee_outlet_attendances.id_employee_outlet_attendance', 'employee_outlet_attendance_logs.id_employee_outlet_attendance')
            ->where('employee_outlet_attendance_logs.status', 'Pending')
            ->join('employee_schedule_dates', 'employee_schedule_dates.id_employee_schedule_date', 'employee_outlet_attendances.id_employee_schedule_date')
            ->join('outlets as outlet_att','outlet_att.id_outlet','employee_outlet_attendances.id_outlet');
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetailPending($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'datetime',
                'shift',
                'outlet',
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
        $result->orderBy('employee_outlet_attendance_logs.id_employee_outlet_attendance_log');

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

            $subject = 'id_outlets';
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    $query2->{$where . 'In'}('outlet_att.id_outlet', $rule[1]);
                }
            }

        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('employee_outlet_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('employee_outlet_attendances.id', $rul[0], $rul[1]);
            }
        }
    }

    public function updatePending(Request $request)
    {
        $request->validate([
            'status' => 'string|in:Approved,Rejected',
        ]);
        $log = EmployeeOutletAttendanceLog::find($request->id_employee_outlet_attendance_log);
        if (!$log) {
            return [
                'status' => 'fail',
                'messages' => ['Selected pending attendance outlet not found']
            ];
        }
        $log->update(['status' => $request->status]);
        $log->employee_outlet_attendance->recalculate();
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success ' . ($request->status == 'Approved' ? 'approve' : 'reject') . ' pending attendance'
            ],
        ];
    }

    public function checkDateRequest(Request $request){
        $post = $request->all();
        $employee = $request->user();
        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->select('id_outlet','outlet_name', 'id_city')->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

        $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee['id'])->first();

        if(empty($type_shift['office_hour_type'])){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Jam kantor tidak ada ']
            ]);
        }
        $data = [
           'shift' => null,
           'schedule_in' => $type_shift['office_hour_start'] ?? null,
           'schedule_out' => $type_shift['office_hour_end'] ?? null,
        ];

        if($type_shift['office_hour_type'] == 'Use Shift'){
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
        $employee = $request->user();
        $outlet = Outlet::where('id_outlet', $post['id_outlet'])->select('id_outlet','outlet_name', 'id_city')->first();
        $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
        ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;

        $type_shift = User::join('roles','roles.id_role','users.id_role')->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')->where('id',$employee['id'])->first();

        if(empty($type_shift['office_hour_type'])){
            return response()->json([
                'status'=>'fail',
                'messages'=>['Jam kantor tidak ada ']
            ]);
        }

        if($type_shift['office_hour_type'] == 'Use Shift'){
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

        $store = EmployeeOutletAttendanceRequest::create([
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
                'messages' => ['Gagal mengajukan permintaan presensi outlet']
            ]);
        }

        DB::commit();
        return response()->json(['status' => 'success', 'messages' => ['Berhasil mengajukan permintaan presensi outlet, silahkan menunggu persetujuan']]);
    }

}
