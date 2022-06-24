<?php

namespace App\Jobs;

use App\Http\Models\TransactionProduct;
use Illuminate\Bus\Queueable;
use App\Http\Models\Setting;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

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
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $id_transaction = $this->data['id_transaction'];
        $key = $this->data['key'];
        $total = $this->data['total'];
        if($key == 1 && $total != 1){
            Setting::where('key','Refresh Commission Transaction')->update(['value' => 'process']);
        }
        $transaction_products = TransactionProduct::where('id_transaction',$id_transaction)->get()->toArray();
        foreach($transaction_products ?? [] as $key => $transaction){
            $trx = New TransactionProduct();
            $trx = $trx->find($transaction['id_transaction_product'])->breakdown();
        }
        if($key == $total){
            Setting::where('key','Refresh Commission Transaction')->update(['value' => 'finished']);
        }
    }
}
