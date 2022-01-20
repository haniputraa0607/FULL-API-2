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
            'location_name' => 'string',
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
            'location_name' => $request->location_name,
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

}
