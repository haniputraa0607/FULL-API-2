<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use DB;

class ApiHairStylistScheduleController extends Controller
{
	function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

	public function outlet(Request $request)
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

	public function list(Request $request)
	{
        $post = $request->json()->all();
        $data = HairstylistSchedule::leftJoin('users as approver', 'approver.id', 'hairstylist_schedules.approve_by')
        		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_schedules.id_user_hair_stylist')
        		->join('outlets', 'outlets.id_outlet', 'hairstylist_schedules.id_outlet')
                ->orderBy('request_at', 'desc');

        if (!empty($post['date_start']) && !empty($post['date_end'])) {
            $start_date = date('Y-m-d', strtotime($post['date_start']));
            $end_date = date('Y-m-d', strtotime($post['date_end']));

            $data->whereDate('request_at', '>=', $start_date)->whereDate('request_at', '<=', $end_date);
        }

        if (isset($post['conditions']) && !empty($post['conditions'])) {
            $rule = 'and';
            if (isset($post['rule'])) {
                $rule = $post['rule'];
            }

            if ($rule == 'and') {
                foreach ($post['conditions'] as $row) {
                    if (isset($row['subject'])) {
                        if ($row['subject'] == 'nickname') {
                            if ($row['operator'] == '=') {
                                $data->where('nickname', $row['parameter']);
                            } else {
                                $data->where('nickname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'phone_number') {
                            if ($row['operator'] == '=') {
                                $data->where('phone_number', $row['parameter']);
                            } else {
                                $data->where('phone_number', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'fullname') {
                            if ($row['operator'] == '=') {
                                $data->where('fullname', $row['parameter']);
                            } else {
                                $data->where('fullname', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'id_outlet') {
                                $data->where('hairstylist_schedules.id_outlet', $row['operator']);
                        }

                        if ($row['subject'] == 'status') {
                        	switch ($row['operator']) {
                        		case 'Approved':
                        			$data->whereNotNull('approve_at');
                        			break;

                        		case 'Rejected':
                        			$data->where(function($q) {
                        				$q->whereNotNull('reject_at');
                        				$q->whereNull('approve_at');
                        			});
                        			break;
                        		
                        		default:
                        			$data->where(function($q) {
                        				$q->whereNull('reject_at');
                        				$q->whereNull('approve_at');
                        			});
                        			break;
                        	}
                        }

                        if ($row['subject'] == 'month') {
                            $data->where('schedule_month', $row['operator']);
                        }

                        if ($row['subject'] == 'year') {
                            $data->where('schedule_year', $row['operator']);
                        }
                    }
                }
            } else {
            	$data->where(function ($subquery) use ($post) {
            		foreach ($post['conditions'] as $row) {
            			if (isset($row['subject'])) {
            				if ($row['subject'] == 'nickname') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('nickname', $row['parameter']);
            					} else {
            						$subquery->orWhere('nickname', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'phone_number') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('phone_number', $row['parameter']);
            					} else {
            						$subquery->orWhere('phone_number', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'fullname') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('fullname', $row['parameter']);
            					} else {
            						$subquery->orWhere('fullname', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'id_outlet') {
            						$subquery->orWhere('hairstylist_schedules.id_outlet', $row['operator']);
            				}

            				if($row['subject'] == 'status') {
            					switch ($row['operator']) {
            						case 'Approved':
            							$subquery->orWhereNotNull('approve_at');
            							break;

        							case 'Rejected':
	                        			$data->orWhere(function($q) {
	                        				$q->whereNotNull('reject_at');
	                        				$q->whereNull('approve_at');
	                        			});
	                        			break;

            						default:
            							$subquery->orWhere(function($q) {
            								$q->whereNull('reject_at');
            								$q->whereNull('approve_at');
            							});
            							break;
            					}
            				}

	                        if ($row['subject'] == 'month') {
	                            $data->orWhere('schedule_month', $row['operator']);
	                        }

	                        if ($row['subject'] == 'year') {
	                            $data->orWhere('schedule_year', $row['operator']);
	                        }
            			}
                    }
                });
            }
        }

        $data = $data->select(
		        	'hairstylist_schedules.*',
		        	'user_hair_stylist.*', 
		        	'outlets.outlet_name', 
		        	'outlets.outlet_code', 
		        	'approver.name as approve_by_name'
		        )->paginate(25);

        return response()->json(MyHelper::checkGet($data));
    }

    public function detail(Request $request) {
        $post = $request->json()->all();

        if (empty($post['id_hairstylist_schedule'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        $detail = HairstylistSchedule::join('outlets', 'outlets.id_outlet', 'hairstylist_schedules.id_outlet')
        		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_schedules.id_user_hair_stylist')
        		->leftJoin('users as approver', 'approver.id', 'hairstylist_schedules.approve_by')
        		->leftJoin('users as last_update_user', 'last_update_user.id', 'hairstylist_schedules.last_updated_by')
                ->with('hairstylist_schedule_dates')
                ->where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])
                ->select(
		        	'hairstylist_schedules.*',
		        	'user_hair_stylist.*', 
		        	'outlets.outlet_name', 
		        	'outlets.outlet_code', 
		        	'approver.name as approve_by_name',
		        	'last_update_user.name as last_updated_by_name'
		        )
		        ->first();

        return response()->json(MyHelper::checkGet($detail));
    }

    public function update(Request $request) {
        $post = $request->json()->all();
        if (empty($post['id_hairstylist_schedule'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        if (isset($post['update_type'])) {
        	$autocrmTitle = null;
        	if (($post['update_type'] == 'reject')) {
        		$data = [
					'reject_at' => date('Y-m-d H:i:s')
				];
				$autocrmTitle = 'Reject Hairstylist Schedule';
        	} elseif (($post['update_type'] == 'approve')) {
	            $data = [
	            	'approve_by' => $request->user()->id,
	            	'approve_at' => date('Y-m-d H:i:s'),
					'reject_at' => null
	            ];
				$autocrmTitle = 'Approve Hairstylist Schedule';
        	}

        	$update = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->update($data);

        	if ($update && $autocrmTitle) {
				$schedule = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])
							->with('outlet', 'user_hair_stylist')->first();
	        	app($this->autocrm)->SendAutoCRM($autocrmTitle, $schedule['user_hair_stylist']['phone_number'] ?? null,
	                [
	                    "month" 		=> !empty($schedule['schedule_month']) ? date('F', mktime(0, 0, 0, $schedule['schedule_month'], 10)) : null,
	                    "year"  		=> (string) $schedule['schedule_year'] ?? null,
	                    'outlet_name'   => $schedule['outlet']['outlet_name'] ?? null
	                ], null, false, false, $recipient_type = 'hairstylist', null, true
	            );
        	}
        	return response()->json(MyHelper::checkUpdate($update));
        }

        $schedule = HairstylistScheduleDate::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->get();

        $oldData = [];
        foreach ($schedule as $val) {
        	$date = date('Y-m-j', strtotime($val['date']));
        	if (isset($oldData[$date]) && $oldData[$date] != $val['shift']) {
        		$oldData[$date] = [
        			'request_by' => $val['request_by'],
        			'created_at' => $val['created_at'],
        			'shift' => 'Full'
        		];
        	} else {
        		$oldData[$date] = [
        			'request_by' => $val['request_by'],
        			'created_at' => $val['created_at'],
        			'shift' => $val['shift']
        		];
        	}
        }

        $newData = [];
        foreach ($post['schedule'] as $key => $val) {
        	if (empty($val)) {
        		continue;
        	}
        	$request_by = 'Admin';
        	$created_at = date('Y-m-d H:i:s');
        	$updated_at = date('Y-m-d H:i:s');
        	if (isset($oldData[$key]) && $oldData[$key]['shift'] == $val) {
        		$request_by = $oldData[$key]['request_by'];
        		$created_at = $oldData[$key]['created_at'];
        	}
        	if ($val == 'Full') {
        		$newData[] = [
	        		'id_hairstylist_schedule' => $post['id_hairstylist_schedule'],
	        		'date' => $key,
	        		'shift' => 'Morning',
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];

	        	$newData[] = [
	        		'id_hairstylist_schedule' => $post['id_hairstylist_schedule'],
	        		'date' => $key,
	        		'shift' => 'Evening',
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];
        	} else {
	        	$newData[] = [
	        		'id_hairstylist_schedule' => $post['id_hairstylist_schedule'],
	        		'date' => $key,
	        		'shift' => $val,
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];
        	}
        }

        DB::beginTransaction();

        $update = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->update(['last_updated_by' => $request->user()->id]);
        $delete = HairstylistScheduleDate::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->delete();
        $save 	= HairstylistScheduleDate::insert($newData);

        if ($save) {
        	DB::commit();
        } else {
        	DB::rollback();
        }

        return response()->json(MyHelper::checkUpdate($save));
    }

    public function getScheduleYear()
    {
        $data = HairstylistSchedule::groupBy('schedule_year')->get()->pluck('schedule_year');

        return MyHelper::checkGet($data);
    }
}
