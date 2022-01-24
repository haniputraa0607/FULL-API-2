<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistAttendanceLog;

class ApiHairstylistAttendanceController extends Controller
{
    /**
     * Menampilkan info clock in / clock out hari ini & informasi yg akan tampil saat akan clock in clock out
     * @return [type] [description]
     */
    public function liveAttendance(Request $request)
    {
        $today = date('Y-m-d');
        $hairstylist = $request->user();
        $outlet = $hairstylist->outlet()->select('outlet_name', 'outlet_latitude', 'outlet_longitude')->first();
        $outlet->setHidden(['call', 'url']);
        // get current schedule
        $todaySchedule = $hairstylist->hairstylist_schedules()
            ->selectRaw('date, min(time_start) as clock_in_requirement, max(time_end) as clock_out_requirement, shift')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
            ->whereNotNull('approve_at')
            ->where([
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

        $attendance = $hairstylist->getAttendanceByDate($todaySchedule);

        // $logs = [];
        // $clock_in = $attendance->clock_in ?: $attendance->logs()->where('type', 'clock_in')->where('status', '<>', 'Rejected')->min('datetime');
        // $clock_out = $attendance->clock_out ?: $attendance->logs()->where('type', 'clock_out')->where('status', '<>', 'Rejected')->max('datetime');
        // if ($clock_in) {
        //     $logs[] = [
        //         'name' => 'Clock In',
        //         'value' => MyHelper::adjustTimezone($clock_in, null, 'H:i', true),
        //     ];
        // }
        // if ($clock_out) {
        //     $logs[] = [
        //         'name' => 'Clock Out',
        //         'value' => MyHelper::adjustTimezone($clock_out, null, 'H:i', true),
        //     ];
        // }

        $shiftNameMap = [
            'Morning' => 'Pagi',
            'Middle' => 'Tengah',
            'Evening' => 'Sore',
        ];

        $result = [
            'clock_in_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_in_requirement, null, 'H:i', true),
            'clock_out_requirement' => MyHelper::adjustTimezone($todaySchedule->clock_out_requirement, null, 'H:i', true),
            'shift_name' => $shiftNameMap[$todaySchedule->shift] ?? $todaySchedule->shift,
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

    /**
     * Clock in / Clock Out
     * @return [type] [description]
     */
    public function storeLiveAttendance(Request $request)
    {
        $request->validate([
            'type' => 'string|required|in:clock_in,clock_out',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'location_name' => 'string|nullable|sometimes',
            'photo' => 'string|required',
        ]);
        $hairstylist = $request->user();
        $outlet = $hairstylist->outlet;
        $attendance = $hairstylist->getAttendanceByDate(date('Y-m-d'));

        $maximumRadius = MyHelper::setting('hairstylist_attendance_max_radius', 'value', 50);
        $distance = MyHelper::getDistance($request->latitude, $request->longitude, $outlet->outlet_latitude, $outlet->outlet_longitude);
        $outsideRadius = $distance > $maximumRadius;

        if ($outsideRadius && !$request->radius_confirmation) {
            return MyHelper::checkGet([
                'need_confirmation' => true,
                'message' => 'Waktu Jam Masuk/Keluar Anda akan diproses sebagai permintaan kehadiran dan memerlukan persetujuan dari atasan Anda.',
            ]);
        }

        $photoPath = null;
        $upload = MyHelper::uploadPhoto($request->photo, 'upload/attendances/');
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

    /**
     * Menampilkan riwayat attendance & attendance requirement
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function histories(Request $request)
    {
        $request->validate([
            'month' => 'numeric|min:1|max:12|required',
            'year' => 'numeric|min:2020|max:3000',
        ]);
        $hairstylist = $request->user();
        $scheduleMonth = $hairstylist->hairstylist_schedules()
            ->where('schedule_year', $request->year)
            ->where('schedule_month', $request->month)
            ->first();
        // $schedules = $scheduleMonth->hairstylist_schedule_dates()->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_attendance', 'hairstylist_schedule_dates.id_hairstylist_attendance')->orderBy('is_overtime')->get();
        $schedules = $scheduleMonth->hairstylist_schedule_dates()
            ->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
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

    public function list(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_schedules', 'hairstylist_schedules.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule', 'hairstylist_schedules.id_hairstylist_schedule')
            ->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date');
        $countTotal = null;
        $result->groupBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterList($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'user_hair_stylist_code',
                'fullname',
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

        $result->selectRaw('user_hair_stylist.id_user_hair_stylist, user_hair_stylist_code, fullname, count(user_hair_stylist.id_user_hair_stylist) as total_schedule, SUM(CASE WHEN hairstylist_attendances.is_on_time = 1 THEN 1 ELSE 0 END) as total_ontime, SUM(CASE WHEN hairstylist_attendances.is_on_time = 0 AND (hairstylist_attendances.clock_in IS NOT NULL OR hairstylist_attendances.clock_out IS NOT NULL) THEN 1 ELSE 0 END) as total_late, SUM(CASE WHEN (hairstylist_attendances.clock_in IS NULL and hairstylist_attendances.clock_out IS NULL) THEN 1 ELSE 0 END) as total_absent');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

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
            $subjects=['fullname', 'user_hair_stylist_code', 'level', 'id_outlet'];
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
                    $query2->{$where . 'In'}('user_hair_stylist.id_outlet', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
    }


    public function detail(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_schedules', 'hairstylist_schedules.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule', 'hairstylist_schedules.id_hairstylist_schedule')
            ->leftJoin('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_schedule_date', 'hairstylist_schedule_dates.id_hairstylist_schedule_date')
            ->with(['attendance_logs' => function ($query) { $query->where('status', 'Approved')->selectRaw('*, null as photo_url');}]);
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

        $result->selectRaw('*, (CASE WHEN (hairstylist_attendances.clock_in IS NULL AND hairstylist_attendances.clock_out IS NULL) THEN "Absent" WHEN is_on_time = 1 THEN "On Time" WHEN is_on_time = 0 THEN "Late" ELSE "" END) as status');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

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
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id_user_hair_stylist'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('user_hair_stylist.id_user_hair_stylist', $rul[0], $rul[1]);
            }
        }
    }

    public function listPending(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_attendances', 'hairstylist_attendances.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_attendance_logs', function($join) {
                $join->on('hairstylist_attendance_logs.id_hairstylist_attendance', 'hairstylist_attendances.id_hairstylist_attendance')
                    ->where('hairstylist_attendance_logs.status', 'Pending');
            });
        $countTotal = null;
        $result->groupBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListPending($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'user_hair_stylist_code',
                'fullname',
                'total_pending',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('user_hair_stylist.id_user_hair_stylist, user_hair_stylist_code, fullname, count(*) as total_pending');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

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
            $subjects=['fullname', 'user_hair_stylist_code', 'level', 'id_outlet'];
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
                    $query2->{$where . 'In'}('user_hair_stylist.id_outlet', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
    }


    public function detailPending(Request $request)
    {
        $result = HairstylistAttendanceLog::selectRaw('*, null as photo_url')
            ->join('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_attendance', 'hairstylist_attendance_logs.id_hairstylist_attendance')
            ->where('hairstylist_attendance_logs.status', 'Pending')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule_date', 'hairstylist_attendances.id_hairstylist_schedule_date');
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
        $result->orderBy('hairstylist_attendance_logs.id_hairstylist_attendance_log');

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
                $query->whereDate('hairstylist_attendance_logs.datetime', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id_user_hair_stylist'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('hairstylist_attendances.id_user_hair_stylist', $rul[0], $rul[1]);
            }
        }
    }

    public function updatePending(Request $request)
    {
        $request->validate([
            'status' => 'string|in:Approved,Rejected',
        ]);
        $log = HairstylistAttendanceLog::find($request->id_hairstylist_attendance_log);
        if (!$log) {
            return [
                'status' => 'fail',
                'messages' => ['Selected pending attendance not found']
            ];
        }
        $log->update(['status' => $request->status]);
        $log->hairstylist_attendance->recalculate();
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success ' . ($request->status == 'Approved' ? 'approve' : 'reject') . ' pending attendance'
            ],
        ];
    }

    public function listRequest(Request $request)
    {
        $result = UserHairStylist::join('hairstylist_attendance_requests', 'hairstylist_attendance_requests.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule_date', 'hairstylist_attendance_requests.id_hairstylist_schedule_date');
        $countTotal = null;
        $result->groupBy('user_hair_stylist.id_user_hair_stylist');

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListRequest($result, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'user_hair_stylist_code',
                'fullname',
                'total_request',
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $result->orderBy($colname, $column['dir']);
                }
            }
        }

        $result->selectRaw('user_hair_stylist.id_user_hair_stylist, user_hair_stylist_code, fullname, count(*) as total_request');
        $result->orderBy('user_hair_stylist.id_user_hair_stylist');

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

    public function filterListRequest($query,$rules,$operator='and'){
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
            $subjects=['fullname', 'user_hair_stylist_code', 'level', 'id_outlet'];
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
                    $query2->{$where . 'In'}('user_hair_stylist.id_outlet', $rule[1]);
                }
            }
        });

        if ($rules = $newRule['transaction_date'] ?? false) {
            foreach ($rules as $rul) {
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
    }


    public function detailRequest(Request $request)
    {
        $result = HairstylistAttendanceLog::selectRaw('*, null as photo_url')
            ->join('hairstylist_attendances', 'hairstylist_attendances.id_hairstylist_attendance', 'hairstylist_attendance_logs.id_hairstylist_attendance')
            ->where('hairstylist_attendance_logs.status', 'Request')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedule_dates.id_hairstylist_schedule_date', 'hairstylist_attendances.id_hairstylist_schedule_date');
        $countTotal = null;

        if ($request->rule) {
            $countTotal = $result->count();
            $this->filterListDetailRequest($result, $request->rule, $request->operator ?: 'and');
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
        $result->orderBy('hairstylist_attendance_logs.id_hairstylist_attendance_log');

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
                $query->whereDate('hairstylist_schedule_dates.date', $rul[0], $rul[1]);
            }
        }
        if ($rules = $newRule['id_user_hair_stylist'] ?? false) {
            foreach ($rules as $rul) {
                $query->where('hairstylist_attendances.id_user_hair_stylist', $rul[0], $rul[1]);
            }
        }
    }

    public function updateRequest(Request $request)
    {
        $request->validate([
            'status' => 'string|in:Approved,Rejected',
        ]);
        $log = HairstylistAttendanceLog::find($request->id_hairstylist_attendance_log);
        if (!$log) {
            return [
                'status' => 'fail',
                'messages' => ['Selected request attendance not found']
            ];
        }
        $log->update(['status' => $request->status]);
        $log->hairstylist_attendance->recalculate();
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success ' . ($request->status == 'Approved' ? 'approve' : 'reject') . ' request attendance'
            ],
        ];
    }
}
