<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceRequest extends Model
{
    protected $table = 'employee_attendance_requests';
    protected $primaryKey = 'id_employee_attendance_request';
    protected $fillable = [
        'id',
        'attendance_date',
        'clock_in',
        'clock_out',
        'notes',
        'approve_notes',
        'status',
        'id_outlet',
        'read'
    ];
}
