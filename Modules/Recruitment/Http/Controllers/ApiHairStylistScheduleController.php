<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;

class ApiHairStylistScheduleController extends Controller
{
	public function list(Request $request)
	{
		$thisMonth = date('m');
		$thisYear  = date('Y');
		$firstDate = date('Y-m-d', strtotime(date($thisYear . $thisMonth . '01')));
		$schedules = HairstylistSchedule::join('user_hair_stylist', 'hairstylist_schedules.id_user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist')
					->join('hairstylist_schedule_dates', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
					->where([
						['hairstylist_schedules.id_outlet', $request->id_outlet],
						['hairstylist_schedule_dates.date', '>=', $firstDate]
					])
					->whereNull('hairstylist_schedules.reject_at')
					->select(
						'user_hair_stylist.nickname',
						'user_hair_stylist.fullname',
						'user_hair_stylist.phone_number',
						'hairstylist_schedules.*',
						'hairstylist_schedule_dates.*'
					)
					->orderBy('date','desc')
					->get();

		$res = [];
		foreach ($schedules as $schedule) {
			$year   = date('Y', strtotime($schedule['date']));
			$month  = date('m', strtotime($schedule['date']));
			$date 	= date('j', strtotime($schedule['date']));
			$res[$year][$month][$date][] = $schedule;
		}

		return MyHelper::checkGet($res);
	}
}
