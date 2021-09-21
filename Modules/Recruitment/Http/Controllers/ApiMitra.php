<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;

use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;

use App\Lib\MyHelper;
use DB;

class ApiMitra extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function splash(Request $request){
    	$getSetting = Setting::whereIn('key',[
			    		'default_splash_screen_mitra_apps', 
			    		'default_splash_screen_mitra_apps_duration'
			    	])->get()->keyBy('key');

        $splash = $getSetting['default_splash_screen_mitra_apps']['value'] ?? null;
        $duration = $getSetting['default_splash_screen_mitra_apps_duration']['value'] ?? 5;

        if (!empty($splash)) {
            $splash = config('url.storage_url_api').$splash;
        } else {
            $splash = null;
        }
        
        $ext = explode('.', $splash);
        $result = [
            'status' => 'success',
            'result' => [
                'splash_screen_url' => $splash."?update=".time(),
                'splash_screen_duration' => $duration,
                'splash_screen_ext' => '.'.end($ext)
            ]
        ];
        return $result;
    }

    public function scheduleDate(Request $request)
    {
		$thisMonth = $request->month ?? date('m');
		$thisYear  = $request->year ?? date('Y');
		$firstDate = date('Y-m-d', strtotime(date($thisYear . '-' . $thisMonth . '-01')));
		$lastDate  = date('Y-m-t', strtotime(date($thisYear . '-' . $thisMonth . '-01')));
		$user = $request->user();

		$schedule = HairstylistSchedule::where('id_user_hair_stylist', $user->id_user_hair_stylist)
					->whereHas('hairstylist_schedule_dates', function($q) use ($firstDate, $lastDate){
						$q->where([
							['date', '>=', $firstDate],
							['date', '<=', $lastDate]
						]);
					})
					->first();

		$morning = [];
		$evening = [];
		if ($schedule) {
			$schedule_dates = HairstylistScheduleDate::where([
								['id_hairstylist_schedule', $schedule->id_hairstylist_schedule],
								['hairstylist_schedule_dates.date', '>=', $firstDate],
								['hairstylist_schedule_dates.date', '<=', $lastDate]
							])
							->select(
								'hairstylist_schedule_dates.date',
								'hairstylist_schedule_dates.shift'
							)
							->orderBy('date','asc')
							->get();
			foreach ($schedule_dates as $val) {
				$tempDate = date('Y-m-d', strtotime($val['date']));
				if ($val['shift'] == 'Morning') {
					$morning[] = $tempDate;
				} else {
					$evening[] = $tempDate;
				}
			}
		}

		$res = [
			'detail'  => $schedule,
			'morning' => $morning,
			'evening' => $evening
		];

		return MyHelper::checkGet($res);
    }
}
