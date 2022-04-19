<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeSchedule extends Model
{
    protected $table = 'employee_schedules';

    protected $primaryKey = 'id_employee_schedule';
    
    protected $casts = [
		'id' => 'int',
		'id_outlet' => 'int',
		'approve_by' => 'int'
	];

	protected $dates = [
		'request_at',
		'approve_at',
		'reject_at'
	];

	protected $fillable = [
		'id',
		'id_outlet',
		'approve_by',
		'last_updated_by',
		'schedule_month',
		'schedule_year',
		'request_at',
		'approve_at',
		'reject_at'
	];

	public function employee_schedule_dates()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeScheduleDate::class, 'id_hairstylist_schedule');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function user_employee()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id');
	}
}
