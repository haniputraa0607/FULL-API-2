<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendance extends Model
{
    protected $table = 'employee_attendances';
    protected $primaryKey = 'id_employee_attendance';
    protected $fillable = [
        'id_employee_schedule_date',
        'attendance_date',
        'id',
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
        return $this->hasMany(EmployeeAttendanceLog::class, 'id_employee_attendance');
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
        $clockIn = $this->logs()->where('type', 'clock_in')->where('status', 'Approved')->min('datetime');
        if ($clockIn) {
            $clockIn = date('H:i', strtotime($clockIn));
        }
        $clockOut = $this->logs()->where('type', 'clock_out')->where('status', 'Approved')->max('datetime');
        if ($clockOut) {
            $clockOut = date('H:i', strtotime($clockOut));
        }
        $isOnTime = strtotime($clockIn) <= (strtotime($this->clock_in_requirement) + ($this->clock_in_tolerance * 60))
            && strtotime($clockOut) >= (strtotime($this->clock_out_requirement) - ($this->clock_out_tolerance * 60));

        $this->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'is_on_time' => $isOnTime ? 1 : 0,
        ]);
    }
}
