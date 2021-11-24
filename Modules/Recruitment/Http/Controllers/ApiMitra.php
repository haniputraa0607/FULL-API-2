<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;

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
use DateTime;
use DateTimeZone;

class ApiMitra extends Controller
{
    public function __construct() {
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
        $this->announcement = "Modules\Recruitment\Http\Controllers\ApiAnnouncement";
        $this->outlet = "Modules\Outlet\Http\Controllers\ApiOutletController";
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

			$resDate[] = [
				'date'	=> date('Y-m-d', strtotime($date)),
				'day'	=> MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($date)), 'l'),
				'date_string'	=> MyHelper::indonesian_date_v2(date('Y-m-d', strtotime($date)), 'D  d/m')
			];
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
					$shift = $tmpListDate[$val['date']]['shift'] == 'Morning' ? 1 : ($tmpListDate[$val['date']]['shift'] == 'Middle' ? 2 : 3);
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

		$outletSchedule = OutletSchedule::where('id_outlet', $user->id_outlet)->with('time_shift')->get();
		$arrShift = ['Morning' => 1, 'Middle' => 2, 'Evening' => 3];
		$shiftInfo = [];
		foreach ($outletSchedule as $sch) {
			$shiftInfo[$sch['day']] = [];
			foreach ($sch['time_shift'] as $shift) {
				$timeStart 	= date('H:i', strtotime($shift['shift_time_start']));
				$timeEnd 	= date('H:i', strtotime($shift['shift_time_end']));
				$shiftInfo[$sch['day']][] = [
					'shift' => $shift['shift'],
					'value' => $arrShift[$shift['shift']],
					'time' => $timeStart . ' - ' . $timeEnd
				];
			}
		}

		$monthInfo = [
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
			],
			'create_schedule' => null
		];

		if (strtotime($thisYear . '-' . $thisMonth . '-01') == strtotime(date('Y-n-01'))) {
			$monthInfo['next_month'] = null;
		}

		if ($user->level == 'Supervisor') {
			$monthInfo['create_schedule'] = [
				'name' => MyHelper::indonesian_date_v2(date('F Y', strtotime('+1 Month ' . date('Y-m-01'))), 'F Y'),
				'month' => date('m', strtotime('+1 Month ' . date('Y-m-01'))),
				'year' => date('Y', strtotime('+1 Month ' . date('Y-m-01')))
			];
		}
		
		$res = [
			'id_outlet' => $outlet['id_outlet'],
			'outlet_name' => $outlet['outlet_name'],
			'month' => $monthInfo,
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

	    	$arrShift = [1 => 'Morning', 2 => 'Middle', 3 => 'Evening'];
	    	foreach ($newSchedule as $key => $val) {
	    		if (empty($val) || empty($arrShift[$val]) || empty($listDate[$key]['date'])) {
	    			continue;
	    		}

	    		$insertData[] = [
	    			'id_hairstylist_schedule' => $schedule->id_hairstylist_schedule,
	        		'date' => $listDate[$key]['date'],
	        		'shift' => $arrShift[$val],
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
    	$this->setTimezone();
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
    		'user_hair_stylist_code' => $user['user_hair_stylist_code'],
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

    	if(!empty($request->latitude) && !empty($request->longitude)){
            UserHairStylist::where('id_user_hair_stylist', $user['id_user_hair_stylist'])->update([
                'latitude' => $request->latitude,
                'longitude' => $request->longitude
            ]);
        }

    	return MyHelper::checkGet($res);
    }

    public function outletServiceScheduleStatus($id_user_hair_stylist, $date = null)
    {
    	$today = $date ?? date('Y-m-d H:i:s');
        $curTime = date('H:i:s', strtotime($today));
    	$day = MyHelper::indonesian_date_v2($date, 'l');
    	$status = [
    		'is_available' => 0,
    		'is_active' => 0,
    		'messages' => []
    	];

    	$hs = UserHairStylist::find($id_user_hair_stylist);
    	$outletSchedule = OutletSchedule::where('id_outlet', $hs->id_outlet)->where('day', $day)->first();
    	if (!$outletSchedule) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Outlet tidak memiliki jadwal buka hari ini.";
        	return $status;
        }

        if ($outletSchedule->is_closed) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Outlet tutup.";
        	return $status;
        }

        $isHoliday = app($this->outlet)->isHoliday($hs->id_outlet);
        if ($isHoliday['status']) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Outlet libur \" " . $isHoliday['holiday'] . "\".";
        	return $status;
        }

        $outletShift = OutletTimeShift::where('id_outlet_schedule', $outletSchedule->id_outlet_schedule)
        				->where(function($q) use ($curTime) {
        					$q->where(function($q2) use ($curTime) {
        						$q2->whereColumn('shift_time_start', '<', 'shift_time_end')
        							->where('shift_time_start', '<', $curTime)
        							->where('shift_time_end', '>', $curTime);
        					})->orWhere(function($q2) use ($curTime) {
        						$q2->whereColumn('shift_time_start', '>', 'shift_time_end')
        							->where(function($q3) use ($curTime) {
        								$q3->where('shift_time_start', '<', $curTime)
        									->orWhere('shift_time_end', '>', $curTime);	
        							});
        					});
        				})->first()['shift'] ?? null;

		if (!$outletShift) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Outlet tidak memiliki jadwal shift pada jam ini.";
        	return $status;
        }

    	$mitraSchedule = HairstylistScheduleDate::join('hairstylist_schedules', 'hairstylist_schedules.id_hairstylist_schedule', 'hairstylist_schedule_dates.id_hairstylist_schedule')
                ->whereNotNull('approve_at')->where('id_user_hair_stylist', $id_user_hair_stylist)
                ->whereDate('date', date('Y-m-d', strtotime($today)))
                ->first();

        if (!$mitraSchedule) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal layanan outlet hari ini.";
        	return $status;
        }

        if ($mitraSchedule->shift != $outletShift) {
        	$status['messages'][] = "Layanan tidak bisa diaktifkan.\n Anda tidak memiliki jadwal layanan outlet pada jam ini.";
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

    public function homeServiceScheduleStatus($id_user_hair_stylist, $date = null)
    {

        $isHomeServiceStart = UserHairStylist::find($id_user_hair_stylist)->home_service_status;
    	$status = [
    		'is_available' => 0,
    		'is_active' => $isHomeServiceStart,
    		'messages' => []
    	];

    	$outletService = $this->outletServiceScheduleStatus($id_user_hair_stylist, $date);

    	if ($outletService['is_available']) {
    		$status['messages'][] = "Layanan tidak bisa diaktifkan.\n karena layanan outlet Anda sedang aktif.";
    		$status['is_active'] = 0;
            return $status;
    	}
    	
    	$status['is_available'] = 1;
        return $status;
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
        	$resOption[] = [
        		"name" => $val,
        		"value" => $summaryOption[$val] ?? 0
        	];
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
        	'total_rating' => (float) ($ratingHs['total_rating'] ?? null),
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
    				->where('suggestion', '!=', "")
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

    public function getOutletShift($id_outlet, $dateTime = null)
    {
    	$outlet = Outlet::find($id_outlet);
    	$timezone = $outlet->city->province->time_zone_utc;
    	$dateTime = $dateTime ?? date('Y-m-d H:i:s');
        $curTime = date('H:i:s', strtotime($dateTime));
    	$day = MyHelper::indonesian_date_v2($dateTime, 'l');

    	$res = null;
    	$outletSchedule = OutletSchedule::where('id_outlet', $id_outlet)->where('day', $day)->first();
    	if (!$outletSchedule || $outletSchedule->is_closed) {
        	return $res;
        }

        $isHoliday = app($this->outlet)->isHoliday($id_outlet);
        if ($isHoliday['status']) {
        	return $res;
        }

    	$outletShift = OutletTimeShift::where('id_outlet_schedule', $outletSchedule->id_outlet_schedule)
        				->where(function($q) use ($curTime) {
        					$q->where(function($q2) use ($curTime) {
        						$q2->whereColumn('shift_time_start', '<', 'shift_time_end')
        							->where('shift_time_start', '<', $curTime)
        							->where('shift_time_end', '>', $curTime);
        					})->orWhere(function($q2) use ($curTime) {
        						$q2->whereColumn('shift_time_start', '>', 'shift_time_end')
        							->where(function($q3) use ($curTime) {
        								$q3->where('shift_time_start', '<', $curTime)
        									->orWhere('shift_time_end', '>', $curTime);	
        							});
        					});
        				})
        				->first();

		if (!$outletShift) {
			return $res;
		}

		return $outletShift['shift'] ?? $res;
    }

    public function setTimezone()
    {
    	return MyHelper::setTimezone(request()->user()->outlet->city->province->time_zone_utc);
    }

    public function convertTimezoneMitra($date = null, $format = 'Y-m-d H:i:s')
    {
    	$timestamp = $date ? strtotime($date) : time();
    	$arrTz = [7 => 'Asia/Jakarta', 8 => 'Asia/Ujung_Pandang', 9 => 'Asia/Jayapura'];

    	$utc = request()->user()->outlet->city->province->time_zone_utc;
    	$tz = $arrTz[$utc] ?? 'Asia/Jakarta';

    	$dt = new DateTime();
		$dt->setTimezone(new DateTimeZone($tz));
		$dt->setTimestamp($timestamp);
		
		return $dt->format($format);

    }
}
