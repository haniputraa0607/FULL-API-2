<?php

namespace App\Jobs;

use App\Http\Models\DealsUser;
use Modules\Report\Entities\ExportQueue;
use App\Http\Models\Setting;
use App\Http\Models\User;
use Modules\Recruitment\Entities\ExportPayrollQueue;
use Modules\Recruitment\Entities\HairstylistPayrollQueue;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Rap2hpoutre\FastExcel\FastExcel;
use DB;
use Storage;
use Excel;
use App\Lib\SendMail as Mail;
use Mailgun;
use File;
use Symfony\Component\HttpFoundation\Request;
use App\Lib\MyHelper;
use Modules\PortalPartner\Entities\OutletReportJob;

class OutletJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    /**
     * Create a new job instance.
     *
     * @return void
     */

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = OutletReportJob::where('status_export','Running')->first();
        if($data){
        $datas = app('Modules\PortalPartner\Http\Controllers\ApiDailyController')->daily($data);
        }
        return true;

    }
}
