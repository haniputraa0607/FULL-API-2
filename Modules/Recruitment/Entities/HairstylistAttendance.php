<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

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
    ];
}
