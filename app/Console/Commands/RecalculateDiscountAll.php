<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class RecalculateDiscountAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discount-all:recalculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate discount all';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // find wrong calculated discount all
        $trxs = Transaction::with('transaction_products')->select('transactions.id_transaction', 'transaction_discount', \DB::raw('SUM(transaction_product_discount_all) as discount_all'))->join('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')->groupBy('transactions.id_transaction')->havingRaw('abs(transaction_discount) <> discount_all')->get();
        // $trxs = Transaction::select('transactions.id_transaction', 'transaction_discount', \DB::raw('SUM(transaction_product_discount_all) as discount_all, count(distinct(id_transaction_promo)) as total_promo'))->join('transaction_products', 'transactions.id_transaction', 'transaction_products.id_transaction')->groupBy('transactions.id_transaction')->havingRaw('abs(transaction_discount) <> discount_all and total_promo > 1')->join('transaction_promos', 'transaction_promos.id_transaction', 'transactions.id_transaction')->get(); // only multiple promo
         
        foreach ($trxs as $trx) {
            $kurang = abs($trx->transaction_discount) - $trx->discount_all;
            $digunakan = 0;
            $total_harga = $trx->transaction_products->sum('transaction_product_subtotal');
            foreach ($trx->transaction_products as $i => $trx_product) {
                $diskon_kurang = 0;
                if ($i != $trx->transaction_products->count()-1) {
                    $diskon_kurang += round($trx_product->transaction_product_subtotal / $total_harga * $kurang);
                    $digunakan += $diskon_kurang;
                } else {
                    $diskon_kurang += $kurang - $digunakan;
                }
                $trx_product->update([
                    'transaction_product_discount_all' => $trx_product->transaction_product_discount_all + $diskon_kurang,
                ]);
            }
        }
    }
}
