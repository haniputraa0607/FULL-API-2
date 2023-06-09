<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Http\Models\Transaction;
use Modules\Product\Entities\ProductIcountOutletStockLog;
use App\Http\Models\TransactionProduct;
use App\Http\Models\Outlet;
use Modules\Product\Entities\ProductProductIcount;
use Modules\Product\Entities\ProductIcount;


class RefreshCheckStock implements ShouldQueue
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
        $date  = $this->data['date'];

        $trxs = Transaction::whereDate('transaction_date', '<=', $date)->whereNotNull('completed_at')->where('transaction_payment_status', 'Completed')->whereNotIn('id_transaction', function($query) {
            $query->select('id_reference')
                  ->from('product_icount_outlet_stock_logs');
        })->get()->toArray();

        foreach($trxs ?? [] as $key => $trx){
            $log = ProductIcountOutletStockLog::where('id_outlet', $outlet['id_outlet'])->where('source','Book Product')->where('id_reference',$trx['id_transaction'])->get();

            if($log->count()){
                continue;
            }

            $data = TransactionProduct::where('transactions.id_transaction', $trx['id_transaction'])
                ->join('transactions', 'transactions.id_transaction', 'transaction_products.id_transaction')
                ->select('transaction_products.*', 'transactions.id_outlet')
                ->get()->toArray();
            
            foreach ($data as $dt){
                $outletType = Outlet::join('locations', 'locations.id_location', 'outlets.id_location')->where('id_outlet', $dt['id_outlet'])
                        ->first()['company_type']??null;
                $outletType = strtolower(str_replace('PT ', '', $outletType));
                $getProductUse = ProductProductIcount::join('product_detail', 'product_detail.id_product', 'product_product_icounts.id_product')
                    ->where('product_product_icounts.id_product', $dt['id_product'])
                    ->where('company_type', $outletType)
                    ->where('product_detail.id_outlet', $dt['id_outlet'])->get()->toArray();

                foreach ($getProductUse as $productUse){
                    $product_icount = new ProductIcount();
                    $update = $product_icount->find($productUse['id_product_icount'])->addLogStockProductIcount(-($productUse['qty']*$dt['transaction_product_qty']), $productUse['unit'], 'Book Product', $dt['id_transaction'], null, $dt['id_outlet']);
                }
            }
        }
    }
}
