<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeAttendanceLog extends Model
{
    protected $table = 'employee_attendance_logs';
    protected $primaryKey = 'id_employee_attendance_log';
    protected $fillable = [
        'id_employee_attendance',
        'type',
        'datetime',
        'latitude',
        'longitude',
        'location_name',
        'photo_path',
        'status',
        'approved_by',
        'notes',
        'approve_notes',
        'read'
    ];

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path ? config('url.storage_url_api') . $this->photo_path : null;
    }

    public function employee_attendance()
    {
        return $this->belongsTo(EmployeeAttendance::class, 'id_employee_attendance');
    }
}
