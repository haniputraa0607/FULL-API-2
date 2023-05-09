<?php

namespace App\Jobs;

use App\Http\Models\Setting;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\PortalPartner\Entities\OutletReportQueueJob;

class OutletGenerateJob implements ShouldQueue
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
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
         $queue = OutletReportQueueJob::where('id_outlet_report_queue_job', $this->data->id_outlet_report_queue_job)->where('status', 'Running')->first();

        if(!empty($queue)){
           app('Modules\PortalPartner\Http\Controllers\ApiGenerateController')->daily($queue);
            $queue = OutletReportQueueJob::where('id_outlet_report_queue_job', $this->data->id_outlet_report_queue_job)->update([
                'status'=>'Ready'
            ]);
            
            return true;
        }
       
        return false;
    }
}
