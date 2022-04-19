<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeScheduleDate extends Model
{
    protected $table = 'employee_schedule_dates';

    protected $primaryKey = 'id_employee_schedule_date';
    
	protected $casts = [
		'id_employee_schedule' => 'int'
	];

    protected $dates = [
	    'date'
	];

	protected $fillable = [
		'id_employee_schedule',
		'date',
		'shift',
		'request_by',
		'id_employee_attendance',
		'is_overtime',
		'time_start',
		'time_end',
		'notes',
	];

	public function employee_schedule()
	{
		return $this->belongsTo(\Modules\Employee\Entities\EmployeeSchedule::class, 'id_hairstylist_schedule');
	}
}
