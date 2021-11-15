<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;

use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;

use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;

use App\Lib\MyHelper;
use DB;

class ApiMitra extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
        $this->announcement = "Modules\Recruitment\Http\Controllers\ApiAnnouncement";
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
		$user = $request->user();

		$outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
		if (!$outlet) {
			return [
				'status' => 'fail',
				'messages' => ['Outlet tidak ditemukan']
			];
		}
		$thisMonth = $request->month ?? date('n');
		$thisYear  = $request->year  ?? date('Y');
		$date = $thisYear . '-' . $thisMonth . '-01';
		$end  = $thisYear . '-' . $thisMonth . '-' . date('t', strtotime($date));

		$resDate = [];
		$listDate = [];
		while (strtotime($date) <= strtotime($end)) {
			$listDate[] = [
				'date' => date('Y-m-d', strtotime($date)),
				'day'  => date('l', strtotime($date))
			];

			$resDate[] = MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($date)), 'D  d/m');
			$date = date("Y-m-d", strtotime("+1 day", strtotime($date)));
		}

		$hairstylists = UserHairStylist::where('id_outlet', $user->id_outlet)
						->where('user_hair_stylist_status', 'Active')
						->with([
							'hairstylist_schedules' => function($q) use ($thisMonth, $thisYear, $user){
								$q->where([
									['schedule_month', $thisMonth],
									['schedule_year', $thisYear],
									['id_outlet', $user->id_outlet],
								]);
							},
							'hairstylist_schedules.hairstylist_schedule_dates' => function($q) {
								$q->orderBy('date','asc');
							}
						])
						->get();

		$resHairstylist = [];
		foreach ($hairstylists as $hs) {

			$schedule = $hs['hairstylist_schedules'][0] ?? null;
			$schedule['status'] = $schedule['approve_at'] ? 'approved' : ($schedule['reject_at'] ? 'rejected' : 'pending');
			$schedule_dates = $schedule['hairstylist_schedule_dates'] ?? [];

			$tmpListDate = [];
			foreach ($schedule_dates as $val) {
				$date = date('Y-m-d', strtotime($val['date']));
				$tmpListDate[$date] = $val;
			}
			
			$tmpShift = [];
			foreach ($listDate as $val) {
				$date = date('d', strtotime($val['date']));
				$shift = 0;
				if (!empty($tmpListDate[$val['date']]['shift'])) {
					$shift = $tmpListDate[$val['date']]['shift'] == 'Morning' ? 1 : 2;
				}
				$tmpShift[] = $shift;
			}

			$resHairstylist[] = [
				'id_user_hair_stylist' => $hs['id_user_hair_stylist'],
				'nickname' => $hs['nickname'],
				'fullname' => $hs['fullname'],
				'shift' => $tmpShift
			];
		}

		$outletShift = OutletTimeShift::where('id_outlet', $user->id_outlet)->get()->keyBy('shift');
		$shiftInfo = [];
		$timeStart = date('H:i', strtotime($outletShift['Morning']['shift_time_start'] ?? '09:00'));
		$timeEnd = date('H:i', strtotime($outletShift['Morning']['shift_time_end'] ?? '15:00'));
		$shiftInfo['shift_1'] = [
			'name' => 'Shift Pagi',
			'time' => $timeStart . ' - ' . $timeEnd
		];
		$timeStart = date('H:i', strtotime($outletShift['Evening']['shift_time_start'] ?? '15:00'));
		$timeEnd = date('H:i', strtotime($outletShift['Evening']['shift_time_end'] ?? '21:00'));
		$shiftInfo['shift_2'] = [
			'name' => 'Shift Sore',
			'time' => $timeStart . ' - ' . $timeEnd
		];
		$shiftInfo['shift_0'] = [
			'name' => 'Tidak ada shift',
			'time' => null,
		];

		$month_info = [
			'prev_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime('-1 Month ' . $thisYear . '-' . $thisMonth . '-01'))
			],
			'this_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime($thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime($thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime($thisYear . '-' . $thisMonth . '-01'))
			],
			'next_month' => [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01')), 'F Y'),
				'month' => date('m', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01')),
				'year' => date('Y', strtotime('+1 Month ' . $thisYear . '-' . $thisMonth . '-01'))
			]
		];
		
		$res = [
			'id_outlet' => $outlet['id_outlet'],
			'outlet_name' => $outlet['outlet_name'],
			'month' => $month_info,
			'shift_info' => $shiftInfo,
			'list_date' => $resDate,
			'list_hairstylist' => $resHairstylist
		];
		return MyHelper::checkGet($res);
    }

    public function createSchedule(ScheduleCreateRequest $request)
    {
    	$user = $request->user();
    	$post = $request->json()->all();

    	if ($user->level != 'Supervisor') {
    		return [
				'status' => 'fail',
				'messages' => ['Jadwal hanya dapat dibuat oleh Hairstylist dengan level Supervisor']
			];
    	}

    	$thisMonth = $request->month ?? date('n');
		$thisYear  = $request->year  ?? date('Y');
		$date = $thisYear . '-' . $thisMonth . '-01';
		$end  = $thisYear . '-' . $thisMonth . '-' . date('t', strtotime($date));

		$listDate = [];
		while (strtotime($date) <= strtotime($end)) {
			$listDate[] = [
				'date' => date('Y-m-d', strtotime($date)),
				'day'  => date('l', strtotime($date))
			];

			$date = date("Y-m-d", strtotime("+1 day", strtotime($date)));
		}

    	$hairstylists = UserHairStylist::where('id_outlet', $user->id_outlet)
						->where('user_hair_stylist_status', 'Active')
						->with([
							'hairstylist_schedules' => function($q) use ($request, $user){
								$q->where([
									['schedule_month', $request->month],
									['schedule_year', $request->year],
									['id_outlet', $user->id_outlet],
								]);
							},
							'hairstylist_schedules.hairstylist_schedule_dates' => function($q) {
								$q->orderBy('date','asc');
							}
						])
						->get();

		$newSchedules = [];
		foreach ($post['schedule'] ?? [] as $val) {
			$newSchedules[$val['id_user_hair_stylist']] = $val['shift'];
		}

    	DB::beginTransaction();
		foreach ($hairstylists as $hs) {
			$newSchedule = $newSchedules[$hs['id_user_hair_stylist']] ?? [];
			if (empty($newSchedule)) {
				continue;
			}

			$schedule = $hs['hairstylist_schedules'][0] ?? null;
			$schedule_dates = $schedule['hairstylist_schedule_dates'] ?? [];
			if (!is_array($schedule_dates)) {
				$schedule_dates = $schedule_dates->toArray();
			}

			$tmpListDate = [];
			foreach ($schedule_dates as $val) {
				$date = date('Y-m-d', strtotime($val['date']));
				$tmpListDate[$date] = $val;
			}
			
			$oldSchedule = [];
			foreach ($listDate as $val) {
				$date = date('d', strtotime($val['date']));
				$shift = 0;
				if (!empty($tmpListDate[$val['date']]['shift'])) {
					$shift = $tmpListDate[$val['date']]['shift'] == 'Morning' ? 1 : 2;
				}
				$oldSchedule[] = $shift;
			}

       		if ($oldSchedule == $newSchedule) {
       			continue;
       		}

			if (!$schedule) {
	    		$schedule = HairstylistSchedule::create([
	    			'id_user_hair_stylist' 	=> $hs->id_user_hair_stylist,
					'id_outlet' 			=> $hs->id_outlet,
					'schedule_month' 		=> $request->month,
					'schedule_year' 		=> $request->year,
					'request_at' 			=> date('Y-m-d H:i:s')
	    		]);
			}

    		HairstylistScheduleDate::where('id_hairstylist_schedule', $schedule->id_hairstylist_schedule)->delete();
    		$schedule->update([
    			'approve_at' 		=> null,
    			'approve_by' 		=> null,
    			'reject_at' 		=> null,
    			'last_updated_by' 	=> null
    		]);

	    	$insertData = [];
	    	$request_by = 'Hairstylist';
	    	$created_at = date('Y-m-d H:i:s');
	    	$updated_at = date('Y-m-d H:i:s');

	    	foreach ($newSchedule as $key => $val) {
	    		if (empty($val)) {
	    			continue;
	    		}
	    		$insertData[] = [
	    			'id_hairstylist_schedule' => $schedule->id_hairstylist_schedule,
	        		'date' => $listDate[$key]['date'],
	        		'shift' => $val == 1 ? 'Morning' : 'Evening',
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
					'messages' => ['Gagal membuat jadwal']
				];
	    	}
		}

		DB::commit();
    	return ['status' => 'success'];
    }

    public function announcementList(Request $request)
    {
    	$user = $request->user();
    	$today = date('Y-m-d h:i:s');
    	$anns = HairstylistAnnouncement::select('id_hairstylist_announcement', 'date_start as date', 'content')
    			->with('hairstylist_announcement_rule_parents.rules')
    			->whereDate('date_start','<=',$today)
    			->whereDate('date_end','>',$today)
				->get()
				->toArray();

		$res = [];
		foreach ($anns as $key => $ann) {
			$cons = array();
			$cons['subject'] = 'phone_number';
			$cons['operator'] = '=';
			$cons['parameter'] = $user['phone_number'];

			array_push($ann['hairstylist_announcement_rule_parents'], ['rule' => 'and', 'rule_next' => 'and', 'rules' => [$cons]]);
			$users = app($this->announcement)->hairstylistFilter($ann['hairstylist_announcement_rule_parents']);

			if (empty($users['status']) || $users['status'] != 'success') {
				continue;
			}

			$res[] = [
				'id_hairstylist_announcement' => $ann['id_hairstylist_announcement'],
				'date' => $ann['date'],
				'content' => $ann['content']
			];
		}

    	return [
    		'status' => 'success',
    		'result' => $res
    	];
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

		$level = $user['level'];
        $level = ($level == 'Hairstylist') ? 'Mitra' : (($level == 'Supervisor') ? 'SPV' : null);

    	$res = [
    		'id_user_hair_stylist' => $user['id_user_hair_stylist'],
    		'nickname' => $user['nickname'],
    		'fullname' => $user['fullname'],
    		'name' => $level . ' ' . $user['fullname'],
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
        $todayTime = date('H:i:s', strtotime($today));
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
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal layanan outlet hari ini.";
        	return $status;
        }

        $shift = $this->getNearestShift($schedule);
        $outlet = Outlet::where('id_outlet', $shift->id_outlet)->with(['today'])->first();
		$getTimeShift = app($this->product)->getTimeShift(strtolower($shift['shift']), $shift->id_outlet, $outlet['today']['id_outlet_schedule']);

		if (empty($getTimeShift)) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Jadwal layanan outlet tidak ditemukan.";
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
        $todayTime = date('H:i:s', strtotime($today));
        $isHomeServiceStart = 0;
    	$status = [
    		'is_available' => 0,
    		'is_active' => $isHomeServiceStart,
    		'messages' => []
    	];

    	$schedule = HairstylistScheduleDate::join('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')->where('id_user_hair_stylist', $id_user_hair_stylist)
                ->whereDate('date', date('Y-m-d', strtotime($today)))
                ->get();

        if (empty($schedule)) {
    		$status['is_available'] = 1;
        	return $status;
        }

        $shift = $this->getNearestShift($schedule);
        $outlet = Outlet::where('id_outlet', $shift->id_outlet)->with(['today'])->first();
		$getTimeShift = app($this->product)->getTimeShift(strtolower($shift['shift']), $shift->id_outlet, $outlet['today']['id_outlet_schedule']);

		if (empty($getTimeShift)) {
    		$status['is_available'] = 1;
        	return $status;
        }

        $shiftTimeStart = date('H:i:s', strtotime($getTimeShift['start']));
        $shiftTimeEnd = date('H:i:s', strtotime($getTimeShift['end']));
        if (strtotime($todayTime) > strtotime($shiftTimeStart) && strtotime($todayTime) < strtotime($shiftTimeEnd)) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n karena layanan outlet Anda sedang aktif.";
    		$status['is_active'] = 0;
            return $status;
        }

    	$status['is_available'] = 1;
        return $status;
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
				        	COUNT(DISTINCT user_ratings.id_user) as total_customer
	        			'),
			        )
			        ->first();

        $summary = UserRatingSummary::where('id_user_hair_stylist', $user->id_user_hair_stylist)->get();
        $summaryRating = [];
        $summaryOption = [];
        foreach ($summary as $val) {
        	if ($val['summary_type'] == 'rating_value') {
        		$summaryRating[$val['key']] = $val['value'];
        	} else {
        		$summaryOption[$val['key']] = $val['value'];
        	}
        }

        $settingOptions = RatingOption::select('star','question','options')->where('rating_target', 'hairstylist')->get();
        $options = [];
        foreach ($settingOptions as $val) {
        	$temp = explode(',', $val['options']);
        	$options = array_merge($options, $temp);
        }

        $options = array_keys(array_flip($options));
        $resOption = [];
        foreach ($options as $val) {
        	$resOption[$val] = $summaryOption[$val] ?? 0;
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
        		'5' => (int) ($summaryRating['5'] ?? null),
        		'4' => (int) ($summaryRating['4'] ?? null),
        		'3' => (int) ($summaryRating['3'] ?? null),
        		'2' => (int) ($summaryRating['2'] ?? null),
        		'1' => (int) ($summaryRating['1'] ?? null)
        	],
        	'rating_option' => $resOption
        ];
        
        return MyHelper::checkGet($res);
    }

    public function ratingComment(Request $request)
    {
    	$user = $request->user();
    	$comment = UserRating::where('user_ratings.id_user_hair_stylist', $user->id_user_hair_stylist)
    				->leftJoin('transaction_product_services','user_ratings.id_transaction_product_service','transaction_product_services.id_transaction_product_service')
    				->whereNotNull('suggestion')
    				->select(
    					'transaction_product_services.order_id',
    					'user_ratings.id_user_rating',
    					'user_ratings.suggestion',
    					'user_ratings.created_at'
    				)
    				->paginate(10)
    				->toArray();

		$resData = [];
		foreach ($comment['data'] ?? [] as $val) {
			$val['created_at_indo'] = MyHelper::dateFormatInd($val['created_at'], true, false);
			$resData[] = $val;
		}

		$comment['data'] = $resData;

		return MyHelper::checkGet($comment);
    }
}
