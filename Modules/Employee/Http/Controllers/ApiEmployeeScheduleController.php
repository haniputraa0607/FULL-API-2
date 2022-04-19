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
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Entities\EmployeeOfficeHourShift;

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
                            $data->where(function ($q) use($row){
                                $q->where('employee_schedules.id_outlet', $row['operator'])
                                ->orWhereIn('employee_schedules.id', function($query) use($row){
                                    $query->select('employee_attendances.id')
                                        ->from('employee_attendances')
                                        ->where('employee_attendances.id_outlet', $row['operator']);
                                });
                            });
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
                                $subquery->orWhere(function ($q) use($row){
                                    $q->where('employee_schedules.id_outlet', $row['operator'])
                                        ->orWhereIn('employee_schedules.id', function($query) use($row){
                                            $query->select('employee_attendances.id')
                                                ->from('employee_attendances')
                                                ->where('employee_attendances.id_outlet', $row['operator']);
                                        });
                                });
            				}

            				if($row['subject'] == 'status') {
            					switch ($row['operator']) {
            						case 'Approved':
            							$subquery->orWhereNotNull('approve_at');
            							break;

        							case 'Rejected':
                                        $subquery->orWhere(function($q) {
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
                                $subquery->orWhere('schedule_month', $row['operator']);
	                        }

	                        if ($row['subject'] == 'year') {
                                $subquery->orWhere('schedule_year', $row['operator']);
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
}
