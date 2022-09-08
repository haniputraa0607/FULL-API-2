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
        $transactions = Transaction::whereNotNull('id_outlet')
            ->with('transaction_products')
            ->whereNotNull('id_user')
            ->whereDate('transaction_date', '>=', $start_date)
            ->whereDate('transaction_date', '<=', $end_date)
            ->get();
        Setting::where('key', 'Refresh Commission Transaction')->update(['value' => 'process']);
        foreach ($transactions ?? [] as $key => $val) {
            $transaction_products = $val->transaction_products;
            foreach ($transaction_products ?? [] as $key => $trx) {
                $trx->breakdown();
            }
        }
        Setting::where('key', 'Refresh Commission Transaction')->update(['value' => 'finished']);
    }
}
