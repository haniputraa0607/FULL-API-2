<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Transaction\Entities\TransactionProductService;

class QueueService implements ShouldQueue
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
        $trx        = $this->data['trx'];
        $service    = $this->data['service'];
        $product    = $this->data['product'];

        $queue = TransactionProductService::join('transactions','transactions.id_transaction','transaction_product_services.id_transaction')->whereDate('schedule_date', date('Y-m-d',strtotime($service->schedule_date)))->where('id_outlet',$trx->id_outlet)->where('transaction_product_services.id_transaction', '<>', $trx->id_transaction)->max('queue') + 1;
        if($queue<10){
            $queue_code = '[00'.$queue.'] - '.$product->product_name;
        }elseif($queue<100){
            $queue_code = '[0'.$queue.'] - '.$product->product_name;
        }else{
            $queue_code = '['.$queue.'] - '.$product->product_name;
        }

		$update = TransactionProductService::where('id_transaction_product_service', $service->id_transaction_product_service)->update(['queue'=>$queue,'queue_code'=>$queue_code]);

    }
}
