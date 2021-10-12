<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;

use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;

use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;

use App\Lib\MyHelper;
use DB;

class ApiMitra extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
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

    public function home(Request $request)
    {
    	$user = $request->user();
    	$today = date('Y-m-d H:i:s');

    	$user->load('outlet.brands');
    	$outlet = [
			'id_outlet' => $user['outlet']['id_outlet'],
			'outlet_code' => $user['outlet']['outlet_code'],
			'outlet_name' => $user['outlet']['outlet_name'],
			'outlet_latitude' => $user['outlet']['outlet_latitude'],
			'outlet_longitude' => $user['outlet']['outlet_longitude']
		];

		$brand = [
			'id_brand' => $user['outlet']['brands'][0]['id_brand'],
			'brand_code' => $user['outlet']['brands'][0]['code_brand'],
			'brand_name' => $user['outlet']['brands'][0]['name_brand'],
			'brand_logo' => $user['outlet']['brands'][0]['logo_brand']
		];

    	$res = [
    		'id_user_hair_stylist' => $user['id_user_hair_stylist'],
    		'nickname' => $user['nickname'],
    		'fullname' => $user['fullname'],
    		'email' => $user['email'],
    		'phone_number' => $user['phone_number'],
    		'level' => $user['level'],
    		'gender' => $user['gender'],
    		'recent_address' => $user['recent_address'],
    		'total_rating' => $user['total_rating'],
    		'total_balance' => $user['total_balance'],
    		'today' => $today,
    		'outlet' => $outlet,
    		'brand' => $brand,
    		'outlet_service' => $this->outletServiceScheduleStatus($user->id_user_hair_stylist),
    		'home_service' => $this->homeServiceScheduleStatus($user->id_user_hair_stylist, $today)
    	];

    	return MyHelper::checkGet($res);
    }

    public function outletServiceScheduleStatus($id_user_hair_stylist)
    {
    	$today = date('Y-m-d H:i:s');
        $timeToday = date('H:i:s', strtotime($today));
    	$status = [
    		'is_available' => 0,
    		'is_active' => 0,
    		'messages' => []
    	];

    	$schedule = HairstylistScheduleDate::join('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')->where('id_user_hair_stylist', $id_user_hair_stylist)
                ->whereDate('date', date('Y-m-d', strtotime($today)))
                ->get();


        if (empty($schedule)) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal outlet service hari ini.";
        	return $status;
        }

        $shift = $this->getNearestShift($schedule)['shift'] ?? null;
		$getTimeShift = app($this->product)->getTimeShift(strtolower($shift));

		if (empty($getTimeShift)) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Jadwal outlet service tidak ditemukan.";
        	return $status;
        }

        $shiftTimeStart = date('H:i:s', strtotime($getTimeShift['start']));
        if ( strtotime($timeToday) < strtotime($shiftTimeStart) ) {
        	$status['messages'][] = "Layanan belum bisa diaktifkan.\n Shift outlet service belum dimulai.";
            return $status;
        }

        $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
        if ( strtotime($timeToday) > strtotime($shiftTimeEnd) ) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal outlet service hari ini.";
            return $status;
        }

        if((strtotime($timeToday) > strtotime($shiftTimeStart) && strtotime($timeToday) < strtotime($shiftTimeEnd)) === false){
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda berada di luar shift outlet service hari ini.";
            return $status;
        }

    	$isClockIn = true;
        if(!$isClockIn){
        	$status['is_available'] = 1;
        	$status['messages'][] = 'Silakan lakukan absensi terlebih dahulu untuk memulai layanan outlet';
            return $status;
        }

    	$status['is_available'] = 1;
    	$status['is_active'] = 1;
        return $status;
    }

    public function homeServiceScheduleStatus($id_user_hair_stylist, $date)
    {
    	$today = date('Y-m-d H:i:s');
        $timeToday = date('H:i:s', strtotime($today));
    	$status = [
    		'is_available' => 0,
    		'is_active' => 0,
    		'messages' => []
    	];

    	$schedule = [];
        if (empty($schedule)) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal home service hari ini.";
        	return $status;
        }
    }

    public function getNearestShift($schedule)
    {
    	$res = null;
    	$shiftNow = $this->timeToShift(date('H:i:s'));
    	foreach ($schedule ?? [] as $val) {
    		if ($val['shift'] == $shiftNow) {
    			$res = $val;
    			break;
    		}
    	}

    	return $res ?? $schedule[0] ?? [];
    }

    public function timeToShift($time)
    {
        $time = date('H:i:s', strtotime($time));
        $morningShiftStart = date('H:i:s', strtotime('09:00'));
        $morningShiftEnd = date('H:i:s', strtotime('15:00'));
        $eveningShiftStart = date('H:i:s', strtotime('15:00'));
        $eveningShiftEnd = date('H:i:s', strtotime('21:00'));

    	if ( strtotime($time) >= strtotime($morningShiftStart) && strtotime($time) < strtotime($morningShiftEnd) ) {
            return 'Morning';
        } elseif ( strtotime($time) >= strtotime($eveningShiftStart) && strtotime($time) < strtotime($eveningShiftEnd) ) {
            return 'Evening';
        }

        return null;
    }

    public function ratingSummary(Request $request)
    {
    	$user = $request->user();
        $ratingHs = UserHairStylist::where('user_hair_stylist.id_user_hair_stylist',$user->id_user_hair_stylist)
			        ->leftJoin('user_ratings','user_ratings.id_user_hair_stylist','user_hair_stylist.id_user_hair_stylist')
			        ->select(
			        	DB::raw('
			        		user_hair_stylist.id_user_hair_stylist,
				        	user_hair_stylist.phone_number,
				        	user_hair_stylist.nickname,
				        	user_hair_stylist.fullname,
				        	user_hair_stylist.level,
				        	user_hair_stylist.total_rating,
				        	COUNT(DISTINCT user_ratings.id_user) as total_customer,
	        				SUM(
								CASE WHEN user_ratings.rating_value = 1 THEN 1 ELSE 0 END
							) AS rating1,
							SUM(
								CASE WHEN user_ratings.rating_value = 2 THEN 1 ELSE 0 END
							) AS rating2,
							SUM(
								CASE WHEN user_ratings.rating_value = 3 THEN 1 ELSE 0 END
							) AS rating3,
							SUM(
								CASE WHEN user_ratings.rating_value = 4 THEN 1 ELSE 0 END
							) AS rating4,
							SUM(
								CASE WHEN user_ratings.rating_value = 5 THEN 1 ELSE 0 END
							) AS rating5
	        			'),
			        )
			        ->first();

        $settingOptions = RatingOption::select('star','question','options')->where('rating_target', 'hairstylist')->get();
        $options = [];
        foreach ($settingOptions as $val) {
        	$temp = explode(',', $val['options']);
        	$options = array_merge($options, $temp);
        }

        $options = array_keys(array_flip($options));
        $optionSummary = [];
        foreach ($options as $val) {
        	$optionSummary[$val] = UserRating::where('id_user_hair_stylist',$user->id_user_hair_stylist)
    							->where('option_value', 'like', '%' . $val . '%')
    							->count();
        }

        $level = $ratingHs['level'] ?? null;
        $level = ($level == 'Hairstylist') ? 'Mitra' : (($level == 'Supervisor') ? 'SPV' : null);
        $res = [
        	'nickname' => $ratingHs['nickname'] ?? null,
        	'fullname' => $ratingHs['fullname'] ?? null,
        	'name' => $level . ' ' . $ratingHs['fullname'] ?? null,
        	'phone_number' => $ratingHs['phone_number'] ?? null,
        	'level' => $ratingHs['level'] ?? null,
        	'total_customer' => (int) ($ratingHs['total_customer'] ?? null),
        	'total_rating' => (int) ($ratingHs['total_rating'] ?? null),
        	'rating_value' => [
        		'5' => (int) ($ratingHs['rating5'] ?? null),
        		'4' => (int) ($ratingHs['rating4'] ?? null),
        		'3' => (int) ($ratingHs['rating3'] ?? null),
        		'2' => (int) ($ratingHs['rating2'] ?? null),
        		'1' => (int) ($ratingHs['rating1'] ?? null)
        	],
        	'rating_option' => $optionSummary
        ];
        
        return MyHelper::checkGet($res);
    }
}
