<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;

use Modules\Franchise\Entities\TransactionProduct;
use Modules\Outlet\Entities\OutletTimeShift;

use Modules\Recruitment\Entities\HairstylistLogBalance;
use Modules\Recruitment\Entities\OutletCash;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\HairstylistAnnouncement;
use Modules\Recruitment\Entities\HairstylistInbox;

use Modules\Transaction\Entities\TransactionPaymentCash;
use Modules\UserRating\Entities\UserRating;
use Modules\UserRating\Entities\RatingOption;
use Modules\UserRating\Entities\UserRatingLog;
use Modules\UserRating\Entities\UserRatingSummary;
use App\Http\Models\Transaction;

use Modules\Recruitment\Http\Requests\ScheduleCreateRequest;
use Modules\Recruitment\Entities\OutletCashAttachment;

use App\Lib\MyHelper;
use DB;
use DateTime;
use DateTimeZone;
use PharIo\Manifest\EmailTest;

class ApiMitra extends Controller
{
    public function __construct() {
        $this->product = "Modules\Product\Http\Controllers\ApiProductController";
        $this->announcement = "Modules\Recruitment\Http\Controllers\ApiAnnouncement";
        $this->outlet = "Modules\Outlet\Http\Controllers\ApiOutletController";
        $this->mitra_log_balance = "Modules\Recruitment\Http\Controllers\MitraLogBalance";
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
	    	$schedule->refreshTimeShift();

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

        if ($request->device_id) {
        	$user->devices()->updateOrCreate([
        		'device_id' => $request->device_id
        	], [
		        'device_type' => $request->device_type,
		        'device_token' => $request->device_token,
        	]);
        }

    	return MyHelper::checkGet($res);
    }

    public function logout(Request $request)
    {
    	$user = $request->user();
    	$user->devices()->where('device_id', $request->device_id)->delete();
    	return [
    		'status' => 'success'
    	];
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
	        			')
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
    	$res = null;
    	$outlet = Outlet::find($id_outlet);
    	if (!$outlet) {
    		return $res;
    	}

    	$timezone = $outlet->city->province->time_zone_utc;
    	$dateTime = $dateTime ?? date('Y-m-d H:i:s');
        $curTime = date('H:i:s', strtotime($dateTime));
    	$day = MyHelper::indonesian_date_v2($dateTime, 'l');

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

    public function getTodayShift($id_user_hair_stylist)
    {
    	$todayShift = null;
    	$hs = UserHairStylist::find($id_user_hair_stylist);

    	if (!$hs) {
    		return  $todayShift;
    	}

    	$shift = $this->getOutletShift($hs->id_outlet);

		$todayShift = HairstylistSchedule::join(
					'hairstylist_schedule_dates', 
					'hairstylist_schedules.id_hairstylist_schedule', 
					'hairstylist_schedule_dates.id_hairstylist_schedule'
				)
		 		->where('id_user_hair_stylist', $hs->id_user_hair_stylist)
		 		->where('date', date('Y-m-d'))
		 		->where('shift', $shift)
		 		->first();

		return $todayShift;
	}

    public function balanceDetail(Request $request){
        $user = $request->user();
        $outletName = Outlet::where('id_outlet', $user->id_outlet)->first()['outlet_name']??'';

        $dataMitra = [
            'id_user_hair_stylist' => $user->id_user_hair_stylist,
            'id_mitra' => $user->user_hair_stylist_code,
            'name' => $user->fullname,
            'outlet_name' => $outletName,
            'current_balance' => $user->total_balance,
            'currency' => 'Rp'
        ];

        return ['status' => 'success', 'result' => $dataMitra];
    }

    public function balanceHistory(Request $request){
        $user = $request->user();
            $history = HairstylistLogBalance::leftJoin('transactions', 'hairstylist_log_balances.id_reference', 'transactions.id_transaction')
                    ->leftJoin('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                    ->where('id_user_hair_stylist', $user->id_user_hair_stylist)
                    ->select('hairstylist_log_balances.id_hairstylist_log_balance', 'hairstylist_log_balances.balance', 'hairstylist_log_balances.source',
                        'transactions.transaction_receipt_number', 'outlets.outlet_name')
                    ->get()->toArray();

        return ['status' => 'success', 'result' => $history];
    }

    public function transferCashDetail(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['date'])){
            return ['status' => 'fail', 'messages' => ['Date can not be empty']];
        }
        $date = date('Y-m-d', strtotime($post['date']));

        $listTransaction = Transaction::join('hairstylist_log_balances', 'hairstylist_log_balances.id_reference', 'transactions.id_transaction')
                            ->whereDate('hairstylist_log_balances.created_at', $date)
                            ->where('source', 'Receive Payment')
                            ->where('id_user_hair_stylist', $user->id_user_hair_stylist)
                            ->where('transfer_status', 0)
                            ->where('id_outlet', $user->id_outlet)
                            ->select('hairstylist_log_balances.created_at as date_receive_cash', 'transactions.id_transaction', 'transactions.transaction_receipt_number',
                                'hairstylist_log_balances.*', 'id_user')
                            ->with('user')->get()->toArray();

        $res = [];
        foreach ($listTransaction as $transaction){
            $products = TransactionProduct::join('products', 'products.id_product', 'transaction_products.id_product')
                        ->where('id_transaction', $transaction['id_transaction'])->pluck('product_name')->toArray();

            $productName = $products[0].(count($products) > 1?' + '.(count($products)-1).' lainnya':'');
            $res[] = [
                'id_transaction' => $transaction['id_transaction'],
                'time' => date('H:i', strtotime($transaction['date_receive_cash'])),
                'customer_name' => $transaction['user']['name'],
                'transaction_receipt_number' => $transaction['transaction_receipt_number'],
                'transaction_grandtotal' => $transaction['balance'],
                'product' => $productName,
                'currency' => 'Rp'
            ];
        }

        return ['status' => 'success', 'result' => $res];
    }

    public function transferCashCreate(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['date'])){
            return ['status' => 'fail', 'messages' => ['Date can not be empty']];
        }
        $date = date('Y-m-d', strtotime($post['date']));

        $listCash = HairstylistLogBalance::join('transactions', 'hairstylist_log_balances.id_reference', 'transactions.id_transaction')
                        ->whereDate('hairstylist_log_balances.created_at', $date)
                        ->where('source', 'Receive Payment')
                        ->where('id_user_hair_stylist', $user->id_user_hair_stylist)
                        ->where('transfer_status', 0)
                        ->where('id_outlet', $user->id_outlet)
                        ->select('id_hairstylist_log_balance', 'balance', 'id_reference', 'id_user')->get()->toArray();

        $idTransaction = array_column($listCash, 'id_reference');
        $idLogBalance = array_column($listCash, 'id_hairstylist_log_balance');
        $totalWillTransfer = array_column($listCash, 'balance');
        $totalWillTransfer = array_sum($totalWillTransfer);
        if(empty($totalWillTransfer)){
            return ['status' => 'fail', 'messages' => ['All cash already transfer']];
        }

        $update = HairstylistLogBalance::whereIn('id_hairstylist_log_balance', $idLogBalance)->update(['transfer_status' => 1]);
        if($update){
            $transferPayment = OutletCash::create([
                'id_user_hair_stylist' => $user->id_user_hair_stylist,
                'id_outlet' => $user->id_outlet,
                'outlet_cash_type' => 'Transfer To Supervisor',
                'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
                'outlet_cash_amount' => abs($totalWillTransfer)
            ]);
            if($transferPayment){
                $update = TransactionPaymentCash::whereIn('id_transaction', $idTransaction)->update(['id_outlet_cash' => $transferPayment['id_outlet_cash']]);

                if($update){
                    $dt = [
                        'id_user_hair_stylist'    => $user->id_user_hair_stylist,
                        'balance'                 => -$totalWillTransfer,
                        'source'                  => 'Transfer To Supervisor',
                        'id_reference'            => $transferPayment['id_outlet_cash']
                    ];

                    $update = app($this->mitra_log_balance)->insertLogBalance($dt);

                    if($user->level == 'Supervisor'){
                        $update = OutletCash::where('id_outlet_cash', $transferPayment['id_outlet_cash'])
                            ->update(['outlet_cash_status' => 'Confirm', 'confirm_at' => date('Y-m-d H:i:s'), 'confirm_by' => $user->id_user_hair_stylist]);
                        if($update){
                            $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
                            $update = Outlet::where('id_outlet', $user->id_outlet)->update(['total_current_cash' => $outlet['total_current_cash'] + $transferPayment['outlet_cash_amount']]);
                        }
                    }
                }
            }else{
                $update = false;
            }
        }

        return MyHelper::checkUpdate($update);
    }

    public function transferCashHistory(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['month']) && empty($post['year'])){
            return ['status' => 'fail', 'messages' => ['Month and Year can not be empty']];
        }

        $list = OutletCash::whereYear('created_at', '=', $post['year'])
                ->whereMonth('created_at', '=', $post['month'])
                ->where('id_outlet', $user->id_outlet)->where('id_user_hair_stylist', $user->id_user_hair_stylist)
                ->get()->toArray();

        $res = [];
        foreach ($list as $value){
            $date = MyHelper::dateFormatInd(date('Y-m-d', strtotime($value['created_at'])), false, false);
            $res[] = [
                'date' => str_replace(' '.$post['year'], '', $date),
                'time' => date('H:i', strtotime($value['created_at'])),
                'outlet_cash_code' => $value['outlet_cash_code'],
                'outlet_cash_amount' => $value['outlet_cash_amount']
            ];
        }

        return ['status' => 'success', 'result' => $res];
    }

    public function incomeDetail(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['date'])){
            return ['status' => 'fail', 'messages' => ['Date can not be empty']];
        }

        if($user->level != 'Supervisor'){
            return ['status' => 'fail', 'messages' => ['Your level not available for this detail']];
        }

        $date = date('Y-m-d', strtotime($post['date']));
        $currency = 'Rp';
        $listHS = UserHairStylist::where('id_outlet', $user->id_outlet)
                    ->where('user_hair_stylist_status', 'Active')->select('id_user_hair_stylist', 'fullname as name')->get()->toArray();

        $projection = Transaction::join('transaction_payment_cash', 'transaction_payment_cash.id_transaction', 'transactions.id_transaction')
                    ->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_payment_cash.cash_received_by')
                    ->whereDate('transactions.transaction_date', $date)
                    ->where('transaction_payment_status', 'Completed')
                    ->where('transactions.id_outlet', $user->id_outlet)
                    ->select('transaction_grandtotal', 'transactions.id_transaction', 'transactions.transaction_receipt_number', 'transaction_payment_cash.*', 'user_hair_stylist.fullname');

        $acceptance = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
                    ->where('outlet_cash.id_outlet', $user->id_outlet)
                    ->whereDate('outlet_cash.created_at', $date)
                    ->where('outlet_cash_status', 'Pending')
                    ->where('outlet_cash_type', 'Transfer To Supervisor')
                    ->select('id_outlet_cash', DB::raw('DATE_FORMAT(outlet_cash.created_at, "%H:%i") as time'), 'fullname as hair_stylist_name',
                        'outlet_cash_status', 'outlet_cash_code', 'outlet_cash_amount as amount');

        $history = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
            ->join('user_hair_stylist as confirm', 'confirm.id_user_hair_stylist', 'outlet_cash.confirm_by')
            ->where('outlet_cash.id_outlet', $user->id_outlet)
            ->whereDate('outlet_cash.confirm_at', $date)
            ->where('outlet_cash_status', 'Confirm')
            ->where('outlet_cash_type', 'Transfer To Supervisor')
            ->select('id_outlet_cash', DB::raw('DATE_FORMAT(outlet_cash.created_at, "%H:%i") as time'), 'user_hair_stylist.fullname as hair_stylist_name',
                'outlet_cash_status', 'outlet_cash_code', 'outlet_cash_amount as amount', 'confirm.fullname as confirm_by_name');

        if(!empty($post['id_user_hair_stylist'])){
            $projection = $projection->where('id_user_hair_stylist', $post['id_user_hair_stylist']);
            $acceptance = $acceptance->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
            $history = $history->where('outlet_cash.id_user_hair_stylist', $post['id_user_hair_stylist']);
        }

        $projection = $projection->orderBy('transaction_date', 'desc')->get()->toArray();
        $acceptance = $acceptance->orderBy('outlet_cash.created_at', 'desc')->get()->toArray();
        $history = $history->orderBy('outlet_cash.confirm_at', 'desc')->get()->toArray();

        $resProjection = [];
        foreach ($projection as $value){
            $resProjection[] = [
                'id_transaction' => $value['id_transaction'],
                'time' => date('H:i', strtotime($value['updated_at'])),
                'hair_stylist_name' => $value['fullname'],
                'receipt_number' => $value['transaction_receipt_number'],
                'amount' => $value['transaction_grandtotal']
            ];
        }

        $totalProjection = array_sum(array_column($resProjection, 'amount'));
        $totalAcceptance = array_sum(array_column($history, 'amount'));
        $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();

        $spvProjection = Transaction::join('transaction_payment_cash', 'transaction_payment_cash.id_transaction', 'transactions.id_transaction')
            ->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'transaction_payment_cash.cash_received_by')
            ->whereDate('transactions.transaction_date', $date)
            ->where('transaction_payment_status', 'Completed')
            ->where('cash_received_by', $user->id_user_hair_stylist)->sum('cash_nominal');

        $spvAcceptance = OutletCash::where('outlet_cash.id_outlet', $user->id_outlet)
                        ->where('id_user_hair_stylist', $user->id_user_hair_stylist)
                        ->where('outlet_cash_type', 'Transfer To Supervisor')
                        ->where('outlet_cash_status', 'Confirm')
                        ->whereDate('outlet_cash.created_at', $date)->sum('outlet_cash_amount');

        $result = [
            'total_current_cash_outlet' => $outlet['total_current_cash'],
            'total_projection' => $totalProjection,
            'total_reception' => $totalAcceptance,
            'currency' => $currency,
            'spv_cash_projection' => (int)$spvProjection,
            'spv_cash_acceptance' => (int)$spvAcceptance,
            'list_hair_stylist' => $listHS,
            'projection' => $resProjection,
            'acceptance' => $acceptance,
            'history' => $history
        ];
        return ['status' => 'success', 'result' => $result];
    }

    public function acceptanceDetail(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['id_outlet_cash'])){
            return ['status' => 'fail', 'messages' => ['ID can not be empty']];
        }

        $detail = OutletCash::join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'outlet_cash.id_user_hair_stylist')
                    ->leftJoin('user_hair_stylist as confirm', 'confirm.id_user_hair_stylist', 'outlet_cash.confirm_by')
                    ->where('id_outlet_cash', $post['id_outlet_cash'])
                    ->select('outlet_cash.*', 'user_hair_stylist.fullname', 'user_hair_stylist.user_hair_stylist_code', 'confirm.fullname as confirm_by_name')->first();

        if(empty($detail)){
            return ['status' => 'fail', 'messages' => ['Data not found']];
        }

        if($user->id_outlet != $detail['id_outlet']){
            return ['status' => 'fail', 'messages' => ['You are not available for this transaction']];
        }

        $listTransaction = OutletCash::join('transaction_payment_cash', 'transaction_payment_cash.id_outlet_cash', 'outlet_cash.id_outlet_cash')
                            ->join('transactions', 'transactions.id_transaction', 'transaction_payment_cash.id_transaction')
                            ->where('transaction_payment_cash.id_outlet_cash', $post['id_outlet_cash'])
                            ->select('transactions.transaction_receipt_number', 'transaction_payment_cash.cash_nominal as amount')->get()->toArray();

        $result = [
            'id_outlet_cash' => $detail['id_outlet_cash'],
            'date' => MyHelper::dateFormatInd($detail['created_at'], true, false),
            'time' => date('H:i', strtotime($detail['created_at'])),
            'hair_stylist_name' => $detail['fullname'],
            'hair_stylist_code' => $detail['user_hair_stylist_code'],
            'outlet_cash_code' => $detail['outlet_cash_code'],
            'status' => $detail['outlet_cash_status'],
            'amount' => $detail['outlet_cash_amount'],
            'currency' => 'Rp',
            'confirm_at' => (!empty($detail['confirm_at'])? MyHelper::dateFormatInd($detail['confirm_at'], true): null),
            'confirm_by_name' => $detail['confirm_by_name'],
            'list_transaction' => $listTransaction
        ];

        return ['status' => 'success', 'result' => $result];
    }

    public function acceptanceConfirm(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['id_outlet_cash'])){
            return ['status' => 'fail', 'messages' => ['ID can not be empty']];
        }

        $detail = OutletCash::where('id_outlet_cash', $post['id_outlet_cash'])->first();

        if(empty($detail)){
            return ['status' => 'fail', 'messages' => ['Data not found']];
        }

        if($detail['transfer_status'] == 'Confirm'){
            return ['status' => 'fail', 'messages' => ['This transaction already confirm']];
        }

        if($user->id_outlet != $detail['id_outlet']){
            return ['status' => 'fail', 'messages' => ['You are not available for this transaction']];
        }

        $update = OutletCash::where('id_outlet_cash', $post['id_outlet_cash'])
            ->update(['outlet_cash_status' => 'Confirm', 'confirm_at' => date('Y-m-d H:i:s'), 'confirm_by' => $user->id_user_hair_stylist]);
        if($update){
            $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
            $update = Outlet::where('id_outlet', $user->id_outlet)->update(['total_current_cash' => $outlet['total_current_cash'] + $detail['outlet_cash_amount']]);
        }

        return MyHelper::checkUpdate($update);
    }

    public function cashOutletTransfer(Request $request){
        $user = $request->user();
        $post = $request->all();

        if(!empty($post['amount']) && !empty($post['attachment'])){
            $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
            if($outlet['total_current_cash'] < $post['amount']){
                return ['status' => 'fail', 'messages' => ['Outlet balance is not sufficient']];
            }

            $save = OutletCash::create([
                'id_user_hair_stylist' => $user->id_user_hair_stylist,
                'id_outlet' => $user->id_outlet,
                'outlet_cash_type' => 'Transfer Supervisor To Central',
                'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
                'outlet_cash_amount' => $post['amount'],
                'outlet_cash_description' => $post['description']??null,
                'outlet_cash_status' => 'Confirm',
                'confirm_at' => date('Y-m-d H:i:s'),
                'confirm_by' => $user->id_user_hair_stylist
            ]);

            if($save){
                if(!empty($request->file('attachment'))){
                    $encode = base64_encode(fread(fopen($request->file('attachment'), "r"), filesize($request->file('attachment'))));
                    $originalName = $request->file('attachment')->getClientOriginalName();
                    $name = pathinfo($originalName, PATHINFO_FILENAME);
                    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    $upload = MyHelper::uploadFile($encode, 'files/transfer_to_central/',$ext, date('YmdHis').'_'.$name);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $fileName = $upload['path'];
                        OutletCashAttachment::create([
                            'id_outlet_cash' => $save['id_outlet_cash'],
                            'outlet_cash_attachment' => $fileName,
                            'outlet_cash_attachment_name' => $name.'.'.$ext
                        ]);
                    }
                }

                $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
                $save = Outlet::where('id_outlet', $user->id_outlet)->update(['total_current_cash' => $outlet['total_current_cash'] - $post['amount']]);
            }

            return MyHelper::checkUpdate($save);
        }else{
            return ['status' => 'fail', 'messages' => ['Transfer amount or attachment can not be empty']];
        }
    }

    public function outletIncomeCreate(Request $request){
        $user = $request->user();
        $post = $request->all();

        if(!empty($post['amount']) && !empty($post['attachment'])){
            $save = OutletCash::create([
                'id_user_hair_stylist' => $user->id_user_hair_stylist,
                'id_outlet' => $user->id_outlet,
                'outlet_cash_type' => 'Income From Central',
                'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
                'outlet_cash_amount' => $post['amount'],
                'outlet_cash_description' => $post['description']??null,
                'outlet_cash_status' => 'Confirm',
                'confirm_at' => date('Y-m-d H:i:s'),
                'confirm_by' => $user->id_user_hair_stylist
            ]);

            if($save){
                if(!empty($request->file('attachment'))){
                    $encode = base64_encode(fread(fopen($request->file('attachment'), "r"), filesize($request->file('attachment'))));
                    $originalName = $request->file('attachment')->getClientOriginalName();
                    $name = pathinfo($originalName, PATHINFO_FILENAME);
                    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    $upload = MyHelper::uploadFile($encode, 'files/income_from_central/',$ext, date('YmdHis').'_'.$name);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $fileName = $upload['path'];
                        OutletCashAttachment::create([
                            'id_outlet_cash' => $save['id_outlet_cash'],
                            'outlet_cash_attachment' => $fileName,
                            'outlet_cash_attachment_name' => $name.'.'.$ext
                        ]);
                    }
                }

                $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
                $save = Outlet::where('id_outlet', $user->id_outlet)->update(['total_cash_from_central' => $outlet['total_cash_from_central'] + $post['amount']]);
            }

            return MyHelper::checkUpdate($save);
        }else{
            return ['status' => 'fail', 'messages' => ['Transfer amount or attachment can not be empty']];
        }
    }

    public function cashOutletHistory(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['month']) && empty($post['year'])){
            return ['status' => 'fail', 'messages' => ['Month and Year can not be empty']];
        }

        $list = OutletCash::where('id_outlet', $user->id_outlet)
                ->whereYear('created_at', '=', $post['year'])
                ->whereMonth('created_at', '=', $post['month'])
                ->whereIn('outlet_cash_type', ['Transfer Supervisor To Central', 'Income From Central'])
                ->orderBy('updated_at', 'desc')
                ->get()->toArray();

        $res = [];
        foreach ($list as $value){
            $type = strtok($value['outlet_cash_type'], " ");
            $att = OutletCashAttachment::where('id_outlet_cash', $value['id_outlet_cash'])->select('outlet_cash_attachment', 'outlet_cash_attachment_name')->get()->toArray();
            $res[] = [
                'id_outlet_cash' => $value['id_outlet_cash'],
                'id_user_hair_stylist' => $value['id_user_hair_stylist'],
                'outlet_cash_type' => ($type == 'Income' ? 'Kas Outlet' : 'Transfer'),
                'outlet_cash_amount' => $value['outlet_cash_amount'],
                'outlet_cash_description' => $value['outlet_cash_description'],
                'date' => MyHelper::dateFormatInd($value['created_at'], true, false),
                'outlet_cash_attachment' => $att
            ];
        }

        $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();

        $result = [
            'info' => [
                'total_current_cash_outlet' => $outlet['total_current_cash'],
                'total_cash_from_central' => $outlet['total_cash_from_central'],
                'va_number' => '0000000000'
            ],
            'data' => $res
        ];
        return ['status' => 'success', 'result' => $result];
    }

    public function expenseOutletCreate(Request $request){
        $user = $request->user();
        $post = $request->all();

        if(!empty($post['amount']) && !empty($post['total_attachment'])){
            $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
            if($outlet['total_cash_from_central'] < $post['amount']){
                return ['status' => 'fail', 'messages' => ['Your balance is not enough']];
            }

            if($post['total_attachment'] > 3){
                return ['status' => 'fail', 'messages' => ['You can upload maximum 3 file']];
            }
            $files = [];
            for($i=0;$i<$post['total_attachment'];$i++){
                if(!empty($request->file('attachment_'.$i))){
                    $encode = base64_encode(fread(fopen($request->file('attachment_'.$i), "r"), filesize($request->file('attachment_'.$i))));
                    $originalName = $request->file('attachment_'.$i)->getClientOriginalName();
                    $name = pathinfo($originalName, PATHINFO_FILENAME);
                    $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                    $upload = MyHelper::uploadFile($encode, 'files/outlet_expense/',$ext, date('YmdHis').'_'.$name);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $files[] = [
                            "outlet_cash_attachment" => $upload['path'],
                            "outlet_cash_attachment_name" => $name.'.'.$ext
                        ];
                    }
                }
            }

            $save = OutletCash::create([
                'id_user_hair_stylist' => $user->id_user_hair_stylist,
                'id_outlet' => $user->id_outlet,
                'outlet_cash_type' => 'Expense Outlet',
                'outlet_cash_code' => 'TSPV-'.MyHelper::createrandom(4,'Angka').$user->id_user_hair_stylist.$user->id_outlet,
                'outlet_cash_amount' => $post['amount'],
                'outlet_cash_description' => $post['description']??null,
                'outlet_cash_status' => 'Confirm',
                'confirm_at' => date('Y-m-d H:i:s'),
                'confirm_by' => $user->id_user_hair_stylist
            ]);

            if($save){
                $insertattachment = [];
                foreach ($files??[] as $file){
                    $insertattachment[] = [
                        'id_outlet_cash' => $save['id_outlet_cash'],
                        'outlet_cash_attachment' => $file['outlet_cash_attachment'],
                        'outlet_cash_attachment_name' => $file['outlet_cash_attachment_name'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                if(!empty($insertattachment)){
                    OutletCashAttachment::insert($insertattachment);
                }

                $save = Outlet::where('id_outlet', $user->id_outlet)->update(['total_cash_from_central' => $outlet['total_cash_from_central'] - $post['amount']]);
            }

            return MyHelper::checkUpdate($save);
        }else{
            return ['status' => 'fail', 'messages' => ['Transfer amount or attachment can not be empty']];
        }
    }

    public function expenseOutletHistory(Request $request){
        $user = $request->user();
        $post = $request->json()->all();
        if(empty($post['month']) && empty($post['year'])){
            return ['status' => 'fail', 'messages' => ['Month and Year can not be empty']];
        }

        $list = OutletCash::where('id_outlet', $user->id_outlet)
            ->whereYear('created_at', '=', $post['year'])
            ->whereMonth('created_at', '=', $post['month'])
            ->whereIn('outlet_cash_type', ['Expense Outlet'])
            ->orderBy('updated_at', 'desc')
            ->get()->toArray();

        $res = [];
        foreach ($list as $value){
            $att = OutletCashAttachment::where('id_outlet_cash', $value['id_outlet_cash'])->select('outlet_cash_attachment', 'outlet_cash_attachment_name')->get()->toArray();
            $res[] = [
                'id_outlet_cash' => $value['id_outlet_cash'],
                'id_user_hair_stylist' => $value['id_user_hair_stylist'],
                'outlet_cash_amount' => $value['outlet_cash_amount'],
                'outlet_cash_description' => $value['outlet_cash_description'],
                'date' => MyHelper::dateFormatInd($value['created_at'], true, false),
                'outlet_cash_attachment' => $att
            ];
        }

        $outlet = Outlet::where('id_outlet', $user->id_outlet)->first();
        $totalExpense = array_column($res, 'outlet_cash_amount');
        $result = [
            'total_cash_from_central' => $outlet['total_cash_from_central'],
            'total_expense' => array_sum($totalExpense),
            'data' => $res
        ];
        return ['status' => 'success', 'result' => $result];
    }
}
