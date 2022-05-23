<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeOutletAttendance extends Model
{
    protected $table = 'employee_outlet_attendances';
    protected $primaryKey = 'id_employee_outlet_attendance';
    protected $fillable = [
        'id_employee_schedule_date',
        'id',
        'id_outlet',
        'attendance_date',
        'clock_in',
        'clock_out',
    ];

    public function logs()
    {
        return $this->hasMany(EmployeeOutletAttendanceLog::class, 'id_employee_outlet_attendance');
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

        $this->update([
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);
    }
}
