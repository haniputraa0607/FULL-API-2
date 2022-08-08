<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeOutletAttendanceLog extends Model
{
    protected $table = 'employee_outlet_attendance_logs';
    protected $primaryKey = 'id_employee_outlet_attendance_log';
    protected $fillable = [
        'id_employee_outlet_attendance',
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

    public function employee_outlet_attendance()
    {
        return $this->belongsTo(EmployeeOutletAttendance::class, 'id_employee_outlet_attendance');
    }
}
