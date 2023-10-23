<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Models\Province;
use App\Http\Models\Outlet;
use App\Http\Models\User;
use Modules\Employee\Entities\EmployeeTimeOff;
use App\Lib\MyHelper;

class ReminderEmployeeAttendance implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$autocrm;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data=$data;
        $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $time_reminder = $this->data['time_reminder'];
        $rem = $this->data['value'];
        $employee = User::join('employee_schedules', 'employee_schedules.id', 'users.id')->join('employee_schedule_dates', 'employee_schedule_dates.id_employee_schedule', 'employee_schedules.id_employee_schedule')->where('users.id',$rem['id'])->where('employee_schedules.schedule_month', date('m'))->where('employee_schedules.schedule_year', date('Y'))->whereDate('employee_schedule_dates.date', date('Y-m-d'))->first();
        if($employee){
            $time_off = EmployeeTimeOff::join('employee_not_available', 'employee_not_available.id_employee_time_off', 'employee_time_off.id_employee_time_off')->where('employee_time_off.id_employee', $employee['id'])->whereDate('employee_time_off.start_date', '<=',date('Y-m-d'))->whereDate('employee_time_off.end_date', '>=',date('Y-m-d'))->first();
            if(empty($time_off) && !isset($time_off)){
                $outlet = Outlet::where('id_outlet',$employee['id_outlet'])->first();
                $timeZone = Province::join('cities', 'cities.id_province', 'provinces.id_province')
                ->where('id_city', $outlet['id_city'])->first()['time_zone_utc']??null;
                $time_zone = [
                    7 => 'WIB',
                    8 => 'WITA',
                    9 => 'WIT'
                ];
                
                if($rem['key']=='reminder_clock_in'){
                    $time = (strtotime($employee['time_start'])) - ($time_reminder * 60);
                    $time_clock = $employee['time_start'];
                    $content_time = MyHelper::adjustTimezone($employee['time_start'], $timeZone, 'H:i', true);
                    $key_crm = 'Reminder Employee to Clock In';
                    
                }elseif($rem['key']=='reminder_clock_out'){
                    $time = (strtotime($employee['time_end'])) - ($time_reminder * 60);
                    $time_clock = $employee['time_end'];
                    $content_time = MyHelper::adjustTimezone($employee['time_end'], $timeZone, 'H:i', true);
                    $key_crm = 'Reminder Employee to Clock Out';

                }

                $time = date('H:i', $time);

                if(date('H:i') >= $time && date('H:i') <= date('H:i', strtotime($time_clock))){
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        $key_crm,
                        $employee['phone'],
                        [
                            'name' => $employee['name'],
                            'time_attendance' => $content_time,
                            'timezone' => $time_zone[$timeZone],
                            'category' => 'Attendance'
                        ], null, false, false, 'employee'
                    );
                }
            }
        }
    }
}
