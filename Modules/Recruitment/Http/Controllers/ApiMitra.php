<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;

use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;

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

    public function schedule(Request $request)
    {
		$thisMonth = $request->month ?? date('n');
		$thisYear  = $request->year  ?? date('Y');
		$user = $request->user();

		$schedule = HairstylistSchedule::where([
			['schedule_month', $thisMonth],
			['schedule_year', $thisYear],
			['id_user_hair_stylist', $user->id_user_hair_stylist],
		])->first();

		$morning = [];
		$evening = [];
		if ($schedule) {

			$schedule['status'] = $schedule['approve_at'] ? 'approved' : ($schedule['reject_at'] ? 'rejected' : 'pending');

			$schedule_dates = HairstylistScheduleDate::where('id_hairstylist_schedule', $schedule->id_hairstylist_schedule)
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

    public function createSchedule(ScheduleCreateRequest $request)
    {
    	$user = $request->user();
		$schedule = HairstylistSchedule::where([
			['schedule_month', $request->month],
			['schedule_year', $request->year],
			['id_user_hair_stylist', $user->id_user_hair_stylist],
		])->first();

    	DB::beginTransaction();
    	if ($schedule) {
    		if ($schedule->approve_at) {
    			return [
					'status' => 'fail',
					'messages' => ['Schedule has been approved']
				];
    		}
    		HairstylistScheduleDate::where('id_hairstylist_schedule', $schedule->id_hairstylist_schedule)->delete();
    		$schedule->update(['reject_at' => null]);
    	} else {
    		$schedule = HairstylistSchedule::create([
    			'id_user_hair_stylist' => $user->id_user_hair_stylist,
				'id_outlet' 		=> $user->id_outlet,
				'schedule_month' 	=> $request->month,
				'schedule_year' 	=> $request->year,
				'request_at' 		=> date('Y-m-d H:i:s')
    		]);
    	}

		if (!$schedule) {
			return [
				'status' => 'fail',
				'messages' => ['Failed to create schedule']
			];
		}

    	$insertData = [];
    	$request_by = 'Hairstylist';
    	$created_at = date('Y-m-d H:i:s');
    	$updated_at = date('Y-m-d H:i:s');

    	foreach ($request->morning as $val) {
    		$insertData[] = [
    			'id_hairstylist_schedule' => $schedule->id_hairstylist_schedule,
        		'date' => $val,
        		'shift' => 'Morning',
        		'request_by' => $request_by,
        		'created_at' => $created_at,
        		'updated_at' => $updated_at
    		];
    	}

    	foreach ($request->evening as $val) {
    		$insertData[] = [
    			'id_hairstylist_schedule' => $schedule->id_hairstylist_schedule,
        		'date' => $val,
        		'shift' => 'Evening',
        		'request_by' => $request_by,
        		'created_at' => $created_at,
        		'updated_at' => $updated_at
    		];
    	}

    	$insert = HairstylistScheduleDate::insert($insertData);

    	if (!$insert) {
    		DB::rollback();
    		return [
				'status' => 'fail',
				'messages' => ['Failed to create schedule']
			];
    	}

		DB::commit();
    	return ['status' => 'success'];
    }

    public function announcementList(Request $request)
    {
    	$today = date('Y-m-d h:i:s');
    	$res = HairstylistAnnouncement::select('id_hairstylist_announcement', 'date_start as date', 'content')
				->where([
					['date_start','<=',$today],
					['date_end','>',$today]
				])
				->get();
    	return MyHelper::checkGet($res);
    }
}
