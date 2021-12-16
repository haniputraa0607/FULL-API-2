<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

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
            ->selectRaw('date, min(clock_in) as clock_in_requirement, max(clock_out) as clock_out_requirement')
            ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
            ->whereNotNull('approve_at')
            ->where([
                'schedule_month' => date('m'),
                'schedule_year' => date('y')
            ])
            ->whereDate('date', date('Y-m-d'))
            ->orderBy('clock_in')
            ->first();
        if (!$todaySchedule) {
            return [
                'status' => 'fail',
                'messages' => ['Tidak ada kehadiran dibutuhkan untuk hari ini']
            ];
        }

        $attendance = $hairstylist->attendances()->where('attendance_date', date('Y-m-d'))->first();
        if (!$attendance) {
            $attendance = $hairstylist->attendances()->create([
                'id_hairstylist_schedule_date' => $hairstylist->hairstylist_schedules()
                    ->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                    ->whereNotNull('approve_at')
                    ->where([
                        'schedule_month' => date('m'),
                        'schedule_year' => date('y')
                    ])
                    ->whereDate('date', date('Y-m-d'))
                    ->orderBy('is_overtime')
                    ->first()
                    ->id_hairstylist_schedule_date,
                'attendance_date' => date('Y-m-d'),
                'id_user_hair_stylist' => $hairstylist->id_user_hair_stylist,
                'clock_in_requirement' => $todaySchedule->clock_in_requirement,
                'clock_out_requirement' => $todaySchedule->clock_out_requirement,
                'clock_in_tolerance' => MyHelper::setting('clock_in_tolerance', 'value', 15),
                'clock_out_tolerance' => MyHelper::setting('clock_in_tolerance', 'value', 0),
            ]);
        }

        $logs = [];
        if ($attendance->clock_in) {
            $logs[] = [
                'name' => 'Clock In',
                'value' => MyHelper::adjustTimezone($attendance->clock_in, null, 'H:i', true),
            ];
        }
        if ($attendance->clock_out) {
            $logs[] = [
                'name' => 'Clock Out',
                'value' => MyHelper::adjustTimezone($attendance->clock_out, null, 'H:i', true),
            ];
        }

        $result = [
            'outlet' => $outlet,
            'logs' => $logs,
        ];

        return MyHelper::checkGet($result);
    }

    /**
     * Menampilkan riwayat attendance & attendance requirement
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function histories(Request $request)
    {
        // code...
    }

    /**
     * Clock in / Clock Out
     * @return [type] [description]
     */
    public function storeLiveAttendance()
    {
        // code...
    }
}
