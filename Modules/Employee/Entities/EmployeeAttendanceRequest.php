<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceRequest extends Model
{
    protected $table = 'employee_attendance_requests';
    protected $fillable = [
        'id',
        'id_employee_schedule_date',
        'clock_in',
        'clock_out',
        'notes',
        'status',
        'id_outlet',
    ];
}
