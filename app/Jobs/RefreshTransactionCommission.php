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
use App\Jobs\RefreshIncomeHS;

class RefreshTransactionCommission implements ShouldQueue
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
        $start_date  = $this->data['start_date'];
        $end_date    = $this->data['end_date'];
        Setting::where('key', 'Refresh Commission Transaction')->update(['value' => 'process']);
        $curDate = date('Y-m-d', strtotime($start_date));
        $lastDate = date('Y-m-d', strtotime($end_date));
        while ($curDate <= $lastDate && $curDate != date('Y-m-d')) {
            app('Modules\Transaction\Http\Controllers\ApiTransactionProductionController')->CronBreakdownCommission($curDate);
            $curDate = date('Y-m-d', strtotime($curDate . ' +1day'));
        }
        $send = [
            'start_date' => $start_date,
            'end_date' => $end_date,
        ];
        RefreshIncomeHS::dispatch($send)->onConnection('refreshcommissionqueue');
        Setting::where('key', 'Refresh Commission Transaction')->update(['value' => 'finished']);
    }
}
