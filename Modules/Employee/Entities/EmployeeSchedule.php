<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
use App\Http\Models\Province;


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
		'id_office_hour_shift',
		'approve_at',
		'reject_at'
	];

	public function employee_schedule_dates()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeScheduleDate::class, 'id_employee_schedule');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function user_employee()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id');
	}

	public function refreshTimeShift($id_employee_office_hour)
	{
		$prov = Province::join('cities', 'cities.id_province', 'provinces.id_province')
						->join('outlets', 'outlets.id_city', 'cities.id_city')
						->where('outlets.id_outlet', $this->id_outlet)
						->select('provinces.*')
						->first();
		$timeShift = EmployeeOfficeHourShift::join('employee_office_hours','employee_office_hours.id_employee_office_hour','employee_office_hour_shift.id_employee_office_hour')->where('employee_office_hours.id_employee_office_hour', $id_employee_office_hour)->get();
		$schedules = [];
		$timeShift->each(function ($item) use (&$schedules) {
			$schedules[$item->shift_name] = [
				'time_start' => $item->shift_start,
				'time_end' => $item->shift_end,
			];
		});
		
		$this->employee_schedule_dates->each(function ($item) use ($schedules, $prov) {
			$item->update([
				'time_start' => MyHelper::reverseAdjustTimezone($schedules[$item->shift]['time_start'] ?? '00:00:00', $prov['time_zone_utc'], 'H:i', true),
				'time_end' => MyHelper::reverseAdjustTimezone($schedules[$item->shift]['time_end'] ?? '00:00:00', $prov['time_zone_utc'], 'H:i', true),
			]);
		});

		return true;
	}
}
