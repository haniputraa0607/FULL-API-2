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

        $detail = HairstylistSchedule::leftJoin('users as approver', 'approver.id', 'hairstylist_schedules.approve_by')
        		->join('user_hair_stylist', 'user_hair_stylist.id_user_hair_stylist', 'hairstylist_schedules.id_user_hair_stylist')
        		->join('outlets', 'outlets.id_outlet', 'hairstylist_schedules.id_outlet')
                ->with('hairstylist_schedule_dates')
                ->where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])
                ->select(
		        	'hairstylist_schedules.*',
		        	'user_hair_stylist.*', 
		        	'outlets.outlet_name', 
		        	'outlets.outlet_code', 
		        	'approver.name as approve_by_name'
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

        if (isset($post['update_type']) && $post['update_type'] == 'reject') {
        	$update = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])
        				->update(['reject_at' => date('Y-m-d H:i:s')]);

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

        if (isset($post['update_type']) && $post['update_type'] == 'approve') {
            unset($post['update_type']);
            $data = [
            	'approve_by' => $request->user()->id,
            	'approve_at' => date('Y-m-d H:i:s')
            ];
            $update = HairstylistSchedule::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->update($data);
        }


        $delete = HairstylistScheduleDate::where('id_hairstylist_schedule', $post['id_hairstylist_schedule'])->delete();
        $save 	= HairstylistScheduleDate::insert($newData);

        if ($save) {
        	DB::commit();
        }

        return response()->json(MyHelper::checkUpdate($save));
    }
}
