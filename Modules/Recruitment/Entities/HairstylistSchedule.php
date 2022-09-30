<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 14 Sep 2021 10:44:38 +0700.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Outlet\Entities\OutletTimeShift;

/**
 * Class HairstylistSchedule
 * 
 * @property int $id_hairstylist_schedule
 * @property int $id_user_hair_stylist
 * @property int $id_outlet
 * @property int $approve_by
 * @property \Carbon\Carbon $request_at
 * @property \Carbon\Carbon $approve_at
 * @property \Carbon\Carbon $reject_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @package Modules\Recruitment\Entities
 */
class HairstylistSchedule extends Model
{
	protected $primaryKey = 'id_hairstylist_schedule';

	protected $casts = [
		'id_user_hair_stylist' => 'int',
		'id_outlet' => 'int',
		'approve_by' => 'int'
	];

	protected $dates = [
		'request_at',
		'approve_at',
		'reject_at'
	];

	protected $fillable = [
		'id_user_hair_stylist',
		'id_outlet',
		'approve_by',
		'last_updated_by',
		'schedule_month',
		'schedule_year',
		'request_at',
		'approve_at',
		'reject_at'
	];

	public function hairstylist_schedule_dates()
	{
		return $this->hasMany(\Modules\Recruitment\Entities\HairstylistScheduleDate::class, 'id_hairstylist_schedule');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}

	public function user_hair_stylist()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
	}

	public function refreshTimeShift()
	{
		$timeShift = OutletTimeShift::where('outlet_time_shift.id_outlet', $this->id_outlet)->join('outlet_schedules', 'outlet_schedules.id_outlet_schedule', 'outlet_time_shift.id_outlet_schedule')->get();
		$schedules = [];
		$oneDay = [
			'senin' => '01',
			'selasa' => '02',
			'rabu' => '03',
			'kamis' => '04',
			'jumat' => '05',
			'jum\'at' => '05',
			'sabtu' => '06',
			'minggu' => '07',
			'monday' => '01',
			'tuesday' => '02',
			'wednesday' => '03',
			'thursday' => '04',
			'friday' => '05',
			'saturday' => '06',
			'sunday' => '07',
		];
		$timeShift->each(function ($item) use (&$schedules, $oneDay) {
			$daycode = $oneDay[strtolower($item->day)] ?? $item->day;
			if (!isset($schedules[$daycode])) {
				$schedules[$daycode] = [];
			}
			$schedules[$daycode][$item->shift] = [
				'time_start' => $item->shift_time_start,
				'time_end' => $item->shift_time_end,
			];
		});

		$id_user = $this->id_user_hair_stylist;
		$this->hairstylist_schedule_dates->each(function ($item) use ($schedules, $oneDay, $id_user) {
			$daycode = $oneDay[strtolower(date('l', strtotime($item->date)))];

			$new_start_shift = $schedules[$daycode][$item->shift]['time_start'] ?? '00:00:00';
			$new_end_shift = $schedules[$daycode][$item->shift]['time_end'] ?? '00:00:00';

			if($item->is_overtime == 1){
				$overtime = HairstylistOverTime::where('id_user_hair_stylist',$id_user)->whereDate('date',$item->date)->whereNotNull('approve_by')->whereNull('reject_at')->first();

				$duration = $overtime['duration'];
				if($overtime['time']=='after'){
					$new_end_shift = $this->getDuration2($new_end_shift,$duration);
				}elseif($overtime['time']=='before'){
					$new_start_shift = $this->getDuration($new_start_shift,$duration);
				}

			}

			$item->update([
				'time_start' => $new_start_shift,
				'time_end' => $new_end_shift,
			]);
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
