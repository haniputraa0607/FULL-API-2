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
		
		$id_user = $this->id;

		$this->employee_schedule_dates->each(function ($item) use ($schedules, $prov, $id_user) {

			$new_start_shift = $schedules[$item->shift]['time_start'] ?? '00:00:00';
			$new_end_shift = $schedules[$item->shift]['time_end'] ?? '00:00:00';

			if($item->is_overtime == 1){
				$overtime = EmployeeOvertime::where('id_employee',$id_user)->whereDate('date',$item->date)->whereNotNull('approve_by')->whereNull('reject_at')->first();

				$duration = $overtime['duration'];
				if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
					$duration_rest = $this->getDuration($overtime['rest_after'],$overtime['rest_before']);
					$secs = strtotime($duration_rest)-strtotime("00:00:00");
					$duration = date("H:i:s",strtotime($duration)+$secs);
				}

				if($overtime['time']=='after'){
					$new_end_shift = $this->getDuration2($new_end_shift,$duration);
					if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
						$duration_rest_before = $this->getDuration($item->time_end,$overtime['rest_before']);
						$duration_rest_after = $this->getDuration($item->time_end,$overtime['rest_after']);
						$new_rest_before = $this->getDuration($new_end_shift,$duration_rest_before);
						$new_rest_after = $this->getDuration($new_end_shift,$duration_rest_after);
					}
				}elseif($overtime['time']=='before'){
					$new_start_shift = $this->getDuration($new_start_shift,$duration);
					if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
						$duration_rest_before = $this->getDuration($overtime['rest_before'],$item->time_start);
						$duration_rest_after = $this->getDuration($overtime['rest_after'],$item->time_start);
						$new_rest_before = $this->getDuration2($new_start_shift,$duration_rest_before);
						$new_rest_after = $this->getDuration2($new_start_shift,$duration_rest_after);
					}
				}

			}

			$item->update([
				'time_start' => $new_start_shift,
				'time_end' => $new_end_shift,
			]);

			if(isset($item->is_overtime) && $item->is_overtime==1){
				if(isset($overtime['rest_before']) && isset($overtime['rest_after'])){
					$update_ovt = EmployeeOvertime::where('id_employee',$id_user)->whereDate('date',$item->date)->whereNotNull('approve_by')->whereNull('reject_at')->update([
						'rest_before' => date('H:i:s',strtotime($new_rest_before)),
						'rest_after' => date('H:i:s',strtotime($new_rest_after)),
					]);
				}
			}
		});

		return true;
	}

	public function getDuration($start_time, $end_time){
        $duration = strtotime($end_time);
        $start = strtotime($start_time);
        $diff = $start - $duration;
        $hour = floor($diff / (60*60));
        $minute = floor(($diff - ($hour*60*60))/(60));
        $second = floor(($diff - ($hour*60*60))%(60));
        return $new_time =  date('H:i:s', strtotime($hour.':'.$minute.':'.$second));
    }

    public function getDuration2($start_time,$end_time){
        $secs = strtotime($end_time)-strtotime("00:00:00");
        return $new_time = date("H:i:s",strtotime($start_time)+$secs);
    }
}
