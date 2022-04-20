<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;

use App\Http\Models\User;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Holiday;
use Modules\Employee\Entities\EmployeeSchedule;
use Modules\Employee\Entities\EmployeeAttendance;
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Entities\EmployeeOfficeHourShift;
use Modules\Users\Entities\Role;

use DB;

class ApiEmployeeScheduleController extends Controller
{
    public function cronEmployeeScheduleNonShit(){
        $log = MyHelper::logCron('Generate Employee Schedule Date Without Shift');
        try{
            DB::beginTransaction();
            //get user employe with role id non shift office hours
            $list_employees = User::join('roles', 'roles.id_role', '=', 'users.id_role')
                                    ->join('employee_office_hours', 'employee_office_hours.id_employee_office_hour', '=', 'roles.id_employee_office_hour')
                                    ->whereNotNull('users.id_role')
                                    ->where('employee_office_hours.office_hour_type', 'Without Shift')
                                    ->get()->toArray();
            
            foreach($list_employees ?? [] as $employee){
                //create master shedule
                $schedule = EmployeeSchedule::where('id',$employee['id'])->where('schedule_month', date('m'))->where('schedule_year', date('Y'))->first();
                if(!$schedule){
                    $schedule = EmployeeSchedule::create([
                        'id' => $employee['id'],
                        'id_outlet' => $employee['id_outlet'],
                        'schedule_month' => date('m'),
                        'schedule_year' => date('Y'),
                        'request_at' => date('Y-m-d H:i:s')
                    ]);
                }
                // check day in outlet schedule
                $day = date('D');
                switch($day){
                    case 'Sun':
                        $day = "Minggu";
                    break;
            
                    case 'Mon':			
                        $day = "Senin";
                    break;
            
                    case 'Tue':
                        $day = "Selasa";
                    break;
            
                    case 'Wed':
                        $day = "Rabu";
                    break;
            
                    case 'Thu':
                        $day = "Kamis";
                    break;
            
                    case 'Fri':
                        $day = "Jumat";
                    break;
            
                    case 'Sat':
                        $day = "Sabtu";
                    break;
                    
                    default:
                        $day = "Undefined";		
                    break;
                }

                $office_sch = OutletSchedule::where('id_outlet', $employee['id_outlet'])->where('day', $day)->where('is_closed', 0)->first();
                $holiday = Holiday::join('outlet_holidays', 'outlet_holidays.id_holiday', '=', 'holidays.id_holiday')
                                    ->join('date_holidays', 'date_holidays.id_holiday', '=', 'holidays.id_holiday')
                                    ->where('outlet_holidays.id_outlet', $employee['id_outlet'])
                                    ->where('date_holidays.date', date('Y-m-d'))
                                    ->get()->toArray();

                if($office_sch && !$holiday){
                //create schedule date 
                    $create_schedule_date = EmployeeScheduleDate::updateOrCreate([
                        'id_employee_schedule' => $schedule['id_employee_schedule'],
                        'date' => date('Y-m-d'),
                        'is_overtime' => 0,
                        'time_start' => $employee['office_hour_start'],
                        'time_end' => $employee['office_hour_end'],
                    ],[]);
                }  
            }
            DB::commit();

            $log->success('success');
            return response()->json(['status' => 'success']);


        }catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        } 
        
    }

    public function cronEmployeeScheduleShit(){
        $log = MyHelper::logCron('Generate Employee Schedule Date Use Shift');
        try{
            DB::beginTransaction();
            $list_employees = User::join('roles', 'roles.id_role', '=', 'users.id_role')
                                    ->join('employee_office_hours', 'employee_office_hours.id_employee_office_hour', '=', 'roles.id_employee_office_hour')
                                    ->whereNotNull('users.id_role')
                                    ->where('employee_office_hours.office_hour_type', 'Use Shift')
                                    ->get()->toArray();
                                    
            foreach($list_employees ?? [] as $employee){
                $schedue_now = EmployeeSchedule::where('id', $employee['id'])->where('schedule_month', date('m'))->where('schedule_year', date('Y'))->first();
                if(!$schedue_now){
                    $schedue_before = EmployeeSchedule::where('id', $employee['id'])->where('schedule_month', date('m', strtotime('-1 months')))->where('schedule_year', date('Y'))->first();
                    if($schedue_before){
                        $schedule_date_before = EmployeeScheduleDate::where('id_employee_schedule',$schedue_before['id_employee_schedule'])->get()->toArray();

                        if($schedule_date_before){
                            $schedule_month = $schedue_before['schedule_month'] + 1;
                            if($schedule_month > 12 ){
                                $schedule_month = $schedule_month - 12;
                                $schedule_year = $schedue_before['schedule_year'] + 1;
                            }else{
                                $schedule_year = $schedue_before['schedule_year'];
                            }

                            $array_employee = [
                                'id' => $employee['id'],
                                'id_outlet' => $employee['id_outlet'],
                                'schedule_month' => $schedule_month,
                                'schedule_year' =>  $schedule_year,
                                'request_at' =>  date('Y-m-d H:i:s'),
                                'approve_by' => $schedue_before['approve_by'],
                                'last_updated_by' => $schedue_before['last_updated_by'] 
                            ];

                            $create_schedule = EmployeeSchedule::create($array_employee);
                            if($create_schedule){
                                foreach($schedule_date_before as $sch){
                                    $date = explode('-',$sch['date']);
                                    $date[1] = $schedule_month;
                                    $date[0] = $schedule_year;
                                    $date =  date('Y-m-d', strtotime(implode('-',$date)));

                                    $holiday = Holiday::join('outlet_holidays', 'outlet_holidays.id_holiday', '=', 'holidays.id_holiday')
                                                        ->join('date_holidays', 'date_holidays.id_holiday', '=', 'holidays.id_holiday')
                                                        ->where('outlet_holidays.id_outlet', $employee['id_outlet'])
                                                        ->whereDate('date_holidays.date', $date)
                                                        ->get()->toArray();
                                    if(!$holiday){
                                    //create schedule date 
                                        if($sch['is_overtime'] == 1){
                                            $get_original = EmployeeOfficeHourShift::where('id_employee_office_hour', $employee['id_employee_office_hour'])->where('shift_name', $sch['shift'])->first();
                                            $sch['time_start'] = $get_original['shift_start'];
                                            $sch['time_end'] = $get_original['shift_end'];
                                        }
                                        $create_schedule_date = EmployeeScheduleDate::create([
                                            'id_employee_schedule' => $create_schedule['id_employee_schedule'],
                                            'date' => $date,
                                            'shift' => $sch['shift'],
                                            'is_overtime' =>  0,
                                            'time_start' => $sch['time_start'],
                                            'time_end' => $sch['time_end'],
                                        ]);
                                    }  
                                }
                            }else{
                                DB::rollback();
                            }
                        }
                    }
                }
            }
            DB::commit();

            $log->success('success');
            return response()->json(['status' => 'success']);


        }catch (\Exception $e) {
            DB::rollBack();
            $log->fail($e->getMessage());
        } 

    }

    public function create(Request $request){
        $post = $request->all();
        $this_year = date('Y');
        $this_month = date('m');

        if($post['year'] >= (int)$this_year){
            if($post['month'] >= $this_month){
                $check_schedule = EmployeeSchedule::where('id',$post['id_employee'])->where('schedule_month',$post['month'])->where('schedule_year',$post['year'])->first();
                if(!$check_schedule){
                    $hs = User::where('id',$post['id_employee'])->first();
                    $array_hs = [
                        "id" => $post['id_employee'],
                        "id_outlet" => $hs['id_outlet'],
                        "approve_by" => auth()->user()->id,
                        "last_updated_by" => auth()->user()->id,
                        "schedule_month" => $post['month'],
                        "schedule_year" => $post['year'],
                        "request_at" => date('Y-m-d H:i:s'), 
                        "approve_at" => date('Y-m-d H:i:s'),
                        "reject_at" => NULL
                    ];
    
                    DB::beginTransaction();
                    $create_schedule = EmployeeSchedule::create($array_hs);
                    if(!$create_schedule){
                        DB::rollback();
                    }
                    DB::commit();
                    return response()->json([
                        'status' => 'success', 
                        'result' => $create_schedule
                    ]);
                }else{
                    return response()->json([
                        'status' => 'fail', 
                        'messages' => 'The Schedule for the selected month already exists'
                    ]);
                } 
            }else{
                return response()->json([
                    'status' => 'fail', 
                    'messages' => 'The Schedule month cant be smaller than this month'
                ]);
            }
        }else{
            return response()->json([
                'status' => 'fail', 
                'messages' => 'The Schedule year cant be smaller than this year'
            ]);
        }
    }

    public function list(Request $request)
	{
        $post = $request->json()->all();
        $data = EmployeeSchedule::join('users', 'users.id', 'employee_schedules.id')
                ->join('roles', 'roles.id_role', 'users.id_role')
                ->join('employee_office_hours', 'employee_office_hours.id_employee_office_hour', 'roles.id_employee_office_hour')
        		->join('outlets', 'outlets.id_outlet', 'employee_schedules.id_outlet')
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
                        if ($row['subject'] == 'name') {
                            if ($row['operator'] == '=') {
                                $data->where('name', $row['parameter']);
                            } else {
                                $data->where('name', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'phone') {
                            if ($row['operator'] == '=') {
                                $data->where('phone', $row['parameter']);
                            } else {
                                $data->where('phone', 'like', '%'.$row['parameter'].'%');
                            }
                        }

                        if ($row['subject'] == 'id_outlet') {
                            $data->where('employee_schedules.id_outlet', $row['operator']);
                        }

                        if ($row['subject'] == 'role_name') {
                            $data->where('roles.id_role', $row['operator']);
                        }

                        if ($row['subject'] == 'status') {
                        	switch ($row['operator']) {
                                case 'No Status':
                                    $data->where('employee_office_hours.office_hour_type', 'Without Shift');
                        			break;

                        		case 'Approved':
                        			$data->whereNotNull('employee_schedules.approve_at');
                        			break;

                        		case 'Rejected':
                        			$data->where(function($q) {
                        				$q->whereNotNull('employee_schedules.reject_at');
                        				$q->whereNull('employee_schedules.approve_at');
                        			});
                        			break;
                        		
                        		default:
                        			$data->where(function($q) {
                                        $q->where('employee_office_hours.office_hour_type', 'Use Shift');
                        				$q->whereNull('employee_schedules.reject_at');
                        				$q->whereNull('employee_schedules.approve_at');
                        			});
                        			break;
                        	}
                        }

                        if ($row['subject'] == 'month') {
                            $data->where('employee_schedules.schedule_month', $row['operator']);
                        }

                        if ($row['subject'] == 'year') {
                            $data->where('employee_schedules.schedule_year', $row['operator']);
                        }
                    }
                }
            } else {
            	$data->where(function ($subquery) use ($post) {
            		foreach ($post['conditions'] as $row) {
            			if (isset($row['subject'])) {
            				if ($row['subject'] == 'name') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('name', $row['parameter']);
            					} else {
            						$subquery->orWhere('name', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'phone') {
            					if ($row['operator'] == '=') {
            						$subquery->orWhere('phone', $row['parameter']);
            					} else {
            						$subquery->orWhere('phone', 'like', '%'.$row['parameter'].'%');
            					}
            				}

            				if ($row['subject'] == 'id_outlet') {
                                $subquery->orWhere('employee_schedules.id_outlet', $row['operator']);
            				}

                            if ($row['subject'] == 'role_name') {
                                $subquery->orWhere('roles.id_role', $row['operator']);
            				}

            				if($row['subject'] == 'status') {
            					switch ($row['operator']) {
                                    case 'No Status':
                                        $data->orWhere('employee_office_hours.office_hour_type', 'Without Shift');
                                        break;

            						case 'Approved':
            							$subquery->orWhereNotNull('employee_schedules.approve_at');
            							break;

        							case 'Rejected':
                                        $subquery->orWhere(function($q) {
	                        				$q->whereNotNull('employee_schedules.reject_at');
	                        				$q->whereNull('employee_schedules.approve_at');
	                        			});
	                        			break;

            						default:
            							$subquery->orWhere(function($q) {
                                            $q->where('employee_office_hours.office_hour_type', 'Use Shift');
            								$q->whereNull('employee_schedules.reject_at');
            								$q->whereNull('employee_schedules.approve_at');
            							});
            							break;
            					}
            				}

	                        if ($row['subject'] == 'month') {
                                $subquery->orWhere('employee_schedules.schedule_month', $row['operator']);
	                        }

	                        if ($row['subject'] == 'year') {
                                $subquery->orWhere('employee_schedules.schedule_year', $row['operator']);
	                        }
            			}
                    }
                });
            }
        }

        $data = $data->select(
		        	'employee_schedules.*',
		        	'users.name', 
		        	'users.phone', 
                    'roles.role_name',
                    'employee_office_hours.office_hour_type',
		        	'outlets.outlet_name', 
		        	'outlets.outlet_code', 
		        )->paginate(25)->toArray();

        return response()->json(MyHelper::checkGet($data));
    }

    public function getScheduleYear()
    {
        $data = EmployeeSchedule::groupBy('schedule_year')->get()->pluck('schedule_year');

        return MyHelper::checkGet($data);
    }

    public function detailNonShift(Request $request){
        $post = $request->all();
        
        if (empty($post['id_employee_schedule'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        $detail = EmployeeSchedule::join('outlets', 'outlets.id_outlet', 'employee_schedules.id_outlet')
                    ->join('users', 'users.id', 'employee_schedules.id')
                    ->join('roles', 'roles.id_role', 'users.id_role')
                    ->join('employee_office_hours','employee_office_hours.id_employee_office_hour','roles.id_employee_office_hour')
                    ->with([
                        'employee_schedule_dates', 
                        'outlet.outlet_schedules'
                    ])
                    ->where('id_employee_schedule', $post['id_employee_schedule'])
                    ->select(
                        'employee_schedules.*',
                        'users.*', 
                        'outlets.outlet_name', 
                        'outlets.outlet_code', 
                        'roles.*',
                        'employee_office_hours.office_hour_start', 
                        'employee_office_hours.office_hour_end'
                    )
                    ->first();

        if (!$detail) {
            return MyHelper::checkGet($detail);
        }
                    
        $listDate = MyHelper::getListDate($detail->schedule_month, $detail->schedule_year);
        $outletSchedule = [];
        foreach ($detail['outlet']['outlet_schedules'] as $s) {
        	$outletSchedule[$s['day']] = [
        		'is_closed' => $s['is_closed'],
        		'time_start' => $s['open'],
        		'time_end' => $s['close'],
        	];
        }

        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
	    			->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
	                ->where('id_outlet', $detail['id_outlet'])
	                ->whereMonth('date_holidays.date', $detail->schedule_month)
	                ->where(function($q) use ($detail) {
	                	$q->whereYear('date_holidays.date', $detail->schedule_year)
	                		->orWhere('yearly', '1');
	                })
	                ->get()
	                ->keyBy('date');

        $resDate = [];
        foreach ($listDate as $date) {
            $day = date('l, F j Y', strtotime($date));
            $hari = MyHelper::indonesian_date_v2($date, 'l');
        	$hari = str_replace('Jum\'at', 'Jumat', $hari);

        	$isClosed = $outletSchedule[$hari]['is_closed'] ?? '1';
        	if (isset($holidays[$date]) && isset($outletSchedule[$hari])) {
        		$isClosed = '1';
        	}

        	$resDate[] = [
        		'date' => $day,
        		'day' => $hari,
        		'holiday' => $holidays[$date]['holiday_name'] ?? null,
        		'is_closed' => $isClosed,
        		'time_start' => $outletSchedule[$hari]['time_start'] ?? null,
        		'time_end' => $outletSchedule[$hari]['time_end'] ?? null,
        	];
        }
        
        $res = [
        	'detail' => $detail,
        	'list_date' => $resDate
        ];
        return MyHelper::checkGet($res);
    }

    public function detailShift(Request $request){
        $post = $request->all();

        if (empty($post['id_employee_schedule'])) {
            return response()->json([
            	'status' => 'fail', 
            	'messages' => ['ID can not be empty']
            ]);
        }

        $detail = EmployeeSchedule::join('outlets', 'outlets.id_outlet', 'employee_schedules.id_outlet')
        		->join('users', 'users.id', 'employee_schedules.id')
                ->join('roles', 'roles.id_role', 'users.id_role')
        		->leftJoin('users as approver', 'approver.id', 'employee_schedules.approve_by')
        		->leftJoin('users as last_update_user', 'last_update_user.id', 'employee_schedules.last_updated_by')
                ->with([
                	'employee_schedule_dates', 
                	'outlet.outlet_schedules'
                ])
                ->where('id_employee_schedule', $post['id_employee_schedule'])
                ->select(
		        	'employee_schedules.*',
		        	'users.*', 
		        	'outlets.outlet_name', 
		        	'outlets.outlet_code', 
                    'roles.*',
		        	'approver.name as approve_by_name',
		        	'last_update_user.name as last_updated_by_name'
		        )
		        ->first();
        
        if (!$detail) {
            return MyHelper::checkGet($detail);
        }

        $shift = EmployeeOfficeHourShift::join('employee_office_hours','employee_office_hours.id_employee_office_hour','employee_office_hour_shift.id_employee_office_hour')->where('employee_office_hours.id_employee_office_hour', $detail->id_employee_office_hour)->get();

        $listDate = MyHelper::getListDate($detail->schedule_month, $detail->schedule_year);
        $outletSchedule = [];
        foreach ($detail['outlet']['outlet_schedules'] as $s) {
        	$outletSchedule[$s['day']] = [
        		'is_closed' => $s['is_closed'],
        		'shift' => $shift->pluck('shift_name')
        	];
        }
        
        $holidays = Holiday::leftJoin('outlet_holidays', 'holidays.id_holiday', 'outlet_holidays.id_holiday')
	    			->leftJoin('date_holidays', 'holidays.id_holiday', 'date_holidays.id_holiday')
	                ->where('id_outlet', $detail['id_outlet'])
	                ->whereMonth('date_holidays.date', $detail->schedule_month)
	                ->where(function($q) use ($detail) {
	                	$q->whereYear('date_holidays.date', $detail->schedule_year)
	                		->orWhere('yearly', '1');
	                })
	                ->get()
	                ->keyBy('date');

        $request->id_outlet = $detail->id_outlet;
        $request->month = $detail->schedule_month;
        $request->year = $detail->schedule_year;
        $request->id_role = $detail->id_role;
        $allSchedule = $this->outlet($request)['result'] ?? [];
     
        $selfSchedule = [];
        foreach ($detail['employee_schedule_dates'] as $key => $val) {
        	$date = date('Y-m-d', strtotime($val['date']));
        	$selfSchedule[$date] = $val['shift'];
        }
        
        $resDate = [];
        foreach ($listDate as $date) {
        	$day = MyHelper::indonesian_date_v2($date, 'l');
        	$day = str_replace('Jum\'at', 'Jumat', $day);
        	$y = date('Y', strtotime($date));
        	$m = date('m', strtotime($date));
        	$d = date('j', strtotime($date));

        	$isClosed = $outletSchedule[$day]['is_closed'] ?? '1';
        	if (isset($holidays[$date]) && isset($outletSchedule[$day])) {
        		$isClosed = '1';
        	}

        	$resDate[] = [
        		'date' => $date,
        		'day' => $day,
        		'outlet_holiday' => $holidays[$date]['holiday_name'] ?? null,
        		'selected_shift' => $selfSchedule[$date] ?? null,
        		'is_closed' => $isClosed,
        		'outlet_shift' => $outletSchedule[$day] ?? [],
        		'all_employee_schedule' => $allSchedule[$y][$m][$d] ?? []
        	];
        }
        $detail['attendance'] = EmployeeAttendance::whereIn('id_employee_schedule_date', $detail->employee_schedule_dates->pluck('id_employee_schedule_date'))->whereDate('attendance_date', date('Y-m-d'))->first();

        
        $res = [
        	'detail' => $detail,
        	'list_date' => $resDate
        ];
        return MyHelper::checkGet($res);

    }

    public function outlet(Request $request)
	{
		$thisMonth = $request->month ?? date('m');
		$thisYear  = $request->year ?? date('Y');
		$firstDate = date('Y-m-d', strtotime(date($thisMonth.'-'.$thisMonth.'-01')));

		$schedules = EmployeeSchedule::join('users', 'employee_schedules.id', 'users.id')
					->join('employee_schedule_dates', 'employee_schedules.id_employee_schedule', 'employee_schedule_dates.id_employee_schedule')
					->where([
						['employee_schedules.id_outlet', $request->id_outlet],
						['employee_schedule_dates.date', '>=', $firstDate],
                        ['users.id_role', '>=', $request->id_role]
					])
					->select(
						'users.name',
						'users.phone',
						'employee_schedules.*',
						'employee_schedule_dates.*'
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

    public function update(Request $request){
        $post = $request->json()->all();
        if (empty($post['id_employee_schedule'])) {
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
				$autocrmTitle = 'Reject Employee Schedule';
        	} elseif (($post['update_type'] == 'approve')) {
	            $data = [
	            	'approve_by' => $request->user()->id,
	            	'approve_at' => date('Y-m-d H:i:s'),
					'reject_at' => null
	            ];
				$autocrmTitle = 'Approve Employee Schedule';
        	}

        	$update = EmployeeSchedule::where('id_employee_schedule', $post['id_employee_schedule'])->update($data);

        	if ($update && $autocrmTitle) {
				$schedule = EmployeeSchedule::where('id_employee_schedule', $post['id_employee_schedule'])
							->with('outlet', 'user_employee')->first();
	        	// app($this->autocrm)->SendAutoCRM($autocrmTitle, $schedule['user_employee']['phone_number'] ?? null,
	            //     [
	            //         "month" 		=> !empty($schedule['schedule_month']) ? date('F', mktime(0, 0, 0, $schedule['schedule_month'], 10)) : null,
	            //         "year"  		=> (string) $schedule['schedule_year'] ?? null,
	            //         'outlet_name'   => $schedule['outlet']['outlet_name'] ?? null
	            //     ], null, false, false, $recipient_type = 'employee', null, true
	            // );
        	}
        }

        $schedule = EmployeeScheduleDate::where('id_employee_schedule', $post['id_employee_schedule'])->get();

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
        
        $fixedSchedule = EmployeeScheduleDate::where('id_employee_schedule', $post['id_employee_schedule'])->join('employee_attendances', 'employee_attendances.id_employee_schedule_date', 'employee_schedule_dates.id_employee_schedule_date')->select('employee_schedule_dates.id_employee_schedule_date', 'date')->get();
        $fixedScheduleDateId = $fixedSchedule->pluck('id_employee_schedule_date');
        $fixedScheduleDate = $fixedSchedule->pluck('date')->map(function($item) {return date('Y-m-d', strtotime($item));});

        $newData = [];
        foreach ($post['schedule'] as $key => $val) {
        	if (empty($val)) {
        		continue;
        	}

        	if (in_array(date('Y-m-d', strtotime($key)), $fixedScheduleDate->toArray()) || date('Y-m-d', strtotime($key)) < date('Y-m-d')) {
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
                $shifts = EmployeeOfficeHourShift::join('employee_office_hours','employee_office_hours.id_employee_office_hour','employee_office_hour_shift.id_employee_office_hour')->where('employee_office_hours.id_employee_office_hour', $post['id_employee_office_hour'])->get()->toArray();
                
                foreach($shifts ?? [] as $shift){
                    $newData[] = [
                        'id_employee_schedule' => $post['id_employee_schedule'],
                        'date' => $key,
                        'shift' => $shift['shift_name'],
                        'request_by' => $request_by,
                        'created_at' => $created_at,
                        'updated_at' => $updated_at
                    ];
                }
        	} else {
	        	$newData[] = [
	        		'id_employee_schedule' => $post['id_employee_schedule'],
	        		'date' => $key,
	        		'shift' => $val,
	        		'request_by' => $request_by,
	        		'created_at' => $created_at,
	        		'updated_at' => $updated_at
	        	];
        	}
        }

        DB::beginTransaction();

        $update = EmployeeSchedule::where('id_employee_schedule', $post['id_employee_schedule'])->update(['last_updated_by' => $request->user()->id]);
        $delete = EmployeeScheduleDate::where('id_employee_schedule', $post['id_employee_schedule'])->whereDate('date', '>=', date('Y-m-d'))->whereNotIn('id_employee_schedule_date', $fixedScheduleDateId)->delete();
        $save 	= EmployeeScheduleDate::insert($newData);
    	EmployeeSchedule::where('id_employee_schedule', $post['id_employee_schedule'])->first()->refreshTimeShift($post['id_employee_office_hour']);
        
        if ($save) {
        	DB::commit();
        } else {
        	DB::rollback();
        }

        return response()->json(MyHelper::checkUpdate($save));
    }
}
