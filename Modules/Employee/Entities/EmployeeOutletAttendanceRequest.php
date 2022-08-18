<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeOutletAttendanceRequest extends Model
{
    protected $table = 'employee_outlet_attendance_requests';
    protected $primaryKey = 'id_employee_outlet_attendance_request';
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
