<?php

namespace App\Jobs;

use App\Http\Models\DealsUser;
use Modules\Report\Entities\ExportQueue;
use App\Http\Models\Setting;
use App\Http\Models\User;
use Modules\Recruitment\Entities\ExportCommissionQueue;

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

class ExportCommissionJob implements ShouldQueue
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
        $this->data    = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $queue = ExportCommissionQueue::where('id_export_commission_queue', $this->data->id_export_commission_queue)->where('status_export', 'Running')->first();

        if(!empty($queue)){
           app('Modules\Recruitment\Http\Controllers\ApiExportCommission')->exportExcel($this->data->id_export_commission_queue);
        }

        return true;
    }
}
