<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistAttendanceLog extends Model
{
    protected $primaryKey = 'id_hairstylist_attendance_log';
    protected $fillable = [
        'id_hairstylist_attendance',
        'type',
        'datetime',
        'latitude',
        'longitude',
        'location_name',
        'photo_path',
        'status',
        'approved_by',
        'notes',
    ];
}