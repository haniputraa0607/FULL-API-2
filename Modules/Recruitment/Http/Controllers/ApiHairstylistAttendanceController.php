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

        $attendance = $hairstylist->getAttendanceByDate($todaySchedule);

        $logs = [];
        $clock_in = $attendance->clock_in ?: $attendance->logs()->where('type', 'clock_in')->where('status', '<>', 'Rejected')->min('datetime');
        $clock_out = $attendance->clock_out ?: $attendance->logs()->where('type', 'clock_out')->where('status', '<>', 'Rejected')->max('datetime');
        if ($clock_in) {
            $logs[] = [
                'name' => 'Clock In',
                'value' => MyHelper::adjustTimezone($clock_in, null, 'H:i', true),
            ];
        }
        if ($clock_out) {
            $logs[] = [
                'name' => 'Clock Out',
                'value' => MyHelper::adjustTimezone($clock_out, null, 'H:i', true),
            ];
        }

        $result = [
            'outlet' => $outlet,
            'logs' => $logs,
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
            'status' => $outsideRadius ? 'Pending' : '',
            'approved_by' => null,
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
        // code...
    }

}
