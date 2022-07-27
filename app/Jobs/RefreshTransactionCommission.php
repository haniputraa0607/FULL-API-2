<?php

namespace App\Jobs;

use App\Http\Models\TransactionProduct;
use Illuminate\Bus\Queueable;
use App\Http\Models\Setting;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Models\Transaction;

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
        $start_date = $this->data['start_date'];
        $end_date = $this->data['end_date'];
        $transaction = Transaction::whereNotNull('id_outlet')->whereNotNull('id_user')->whereDate('transaction_date', '>=', $start_date)->whereDate('transaction_date', '<=', $end_date)->get()->toArray();
        Setting::where('key','Refresh Commission Transaction')->update(['value' => 'process']);
        foreach($transaction ?? [] as $key => $val){
            $transaction_products = TransactionProduct::where('id_transaction',$val['id_transaction'])->get()->toArray();
            foreach($transaction_products ?? [] as $key => $transaction){
                $trx = New TransactionProduct();
                $trx = $trx->find($transaction['id_transaction_product'])->breakdown();
            }
        }
        Setting::where('key','Refresh Commission Transaction')->update(['value' => 'finished']);
    }
}
