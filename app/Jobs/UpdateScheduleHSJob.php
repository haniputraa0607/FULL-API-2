<?php

namespace App\Jobs;

use App\Http\Models\OutletSchedule;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Outlet\Entities\OutletTimeShift;
use Modules\Recruitment\Entities\HairstylistSchedule;
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Http\Controllers\HairStylistController;

class UpdateScheduleHSJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data   = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $idUserHairStylist = $this->data['id_user_hair_stylist'];
        $detail = UserHairStylist::where('id_user_hair_stylist', $idUserHairStylist)->first();
        $currentMonth = date('m');
        $currentYear = date('Y');
        $currentDate = date('Y-m-d');
        $allSchedule = HairstylistSchedule::where('id_user_hair_stylist', $idUserHairStylist)
                    ->where('schedule_year', $currentYear)
                    ->where('schedule_month', '>=', $currentMonth)->get()->toArray();

        $day = [
            'Mon' => 'Senin',
            'Tue' => 'Selasa',
            'Wed' => 'Rabu',
            'Thu' => 'Kamis',
            'Fri' => 'Jumat',
            'Sat' => 'Sabtu',
            'Sun' => 'Minggu'
        ];

        foreach($allSchedule as $schedule){
            $update = HairstylistSchedule::where('id_hairstylist_schedule', $schedule['id_hairstylist_schedule'])->update(['id_outlet' => $detail['id_outlet']]);

            if($update){
                $getAllShift = HairstylistScheduleDate::where('id_hairstylist_schedule', $schedule['id_hairstylist_schedule'])->whereDate('date', '>=', $currentDate)->get()->toArray();
                foreach ($getAllShift as $shift){
                    $day = $day[date('D', strtotime($shift['date']))];
                    $outlet = OutletSchedule::where('day', $day)->where('id_outlet', $detail['id_outlet'])->first();
                    if($outlet){
                        $shiftTime = OutletTimeShift::where('id_outlet', $detail['id_outlet'])
                            ->where('id_outlet_schedule', $outlet['id_outlet_schedule'])->where('shift', $shift['shift'])->first();
                        HairstylistScheduleDate::where('id_hairstylist_schedule_date', $shift['id_hairstylist_schedule_date'])
                            ->update(['time_start' => $shiftTime['shift_time_start'], 'time_end' => $shiftTime['shift_time_end']]);
                    }
                }
            }
        }

        return true;
    }
}