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
}
