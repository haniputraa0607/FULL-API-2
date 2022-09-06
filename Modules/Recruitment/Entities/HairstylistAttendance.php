<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
use App\Http\Models\OutletSchedule;
use Modules\Outlet\Entities\OutletTimeShift;

class HairstylistAttendance extends Model
{
    protected $primaryKey = 'id_hairstylist_attendance';
    protected $fillable = [
        'id_hairstylist_schedule_date',
        'attendance_date',
        'id_user_hair_stylist',
        'clock_in',
        'clock_out',
        'clock_in_requirement',
        'clock_out_requirement',
        'clock_in_tolerance',
        'clock_out_tolerance',
        'is_on_time',
        'id_outlet',
    ];

    public function logs()
    {
        return $this->hasMany(HairstylistAttendanceLog::class, 'id_hairstylist_attendance');
    }

    public function hairstylistScheduleDate()
    {
        return $this->belongsTo(HairstylistScheduleDate::class, 'id_hairstylist_schedule_date');
    }

    public function storeClock($data)
    {
        $clock = $this->logs()->updateOrCreate([
            'type' => $data['type'],
            'datetime' => $data['datetime'],
        ], $data);
        $this->recalculate();
    }

    public function recalculate()
    {
        $day = MyHelper::indonesian_date_v2($this->attendance_date, 'l');
        $day = str_replace('Jum\'at', 'Jumat', $day);
        $outlet_sch = OutletSchedule::join('outlet_time_shift','outlet_time_shift.id_outlet_schedule','outlet_schedules.id_outlet_schedule')->where('outlet_schedules.id_outlet',$this->id_outlet)->where('outlet_schedules.day',$day)->where('outlet_time_shift.shift',$this->hairstylistScheduleDate->shift)->first();
        $clockIn = $this->logs()->where('type', 'clock_in')->where('status', 'Approved')->min('datetime');
        if ($clockIn) {
            $clockIn = date('H:i', strtotime($clockIn));
        }
        $clockOut = $this->logs()->where('type', 'clock_out')->where('status', 'Approved')->max('datetime');
        if ($clockOut) {
            $clockOut = date('H:i', strtotime($clockOut));
            $this->hairstylistScheduleDate()->update(['id_outlet_box' => null]);
        }
        $isOnTime = strtotime($clockIn) <= (strtotime($outlet_sch['shift_time_start']) + ($this->clock_in_tolerance * 60))
            && strtotime($clockOut) >= (strtotime($outlet_sch['shift_time_end']) - ($this->clock_out_tolerance * 60));

        $this->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'is_on_time' => $isOnTime ? 1 : 0,
        ]);
    }
}
