<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\TransactionProduct;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionProductService;

class TransactionOutletService extends \App\Http\Models\Template\TransactionService
{
    protected $table = 'transaction_outlet_services';

    protected $primaryKey = 'id_transaction_outlet_services';

    protected $fillable   = [
        'id_transaction',
        'customer_name',
        'customer_email',
        'customer_domicile',
        'customer_birtdate',
        'customer_gender',
        'pickup_by',
        'pickup_at',
        'completed_at',
        'reject_at',
        'reject_reason',
        'need_manual_void'
    ];

    public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

    public function triggerPaymentCancelled($data = [])
    {
        //remove hs from table not available
        $idTrxProductService = TransactionProductService::where('id_transaction', $this->id_transaction)->pluck('id_transaction_product_service')->toArray();
        if(!empty($idTrxProductService)){
            HairstylistNotAvailable::whereIn('id_transaction_product_service', $idTrxProductService)->delete();
        }
    }

    public function triggerRejectOutletService($data = [])
    {
    	$trxProducts = TransactionProduct::where('id_transaction', $this->id_transaction)->with('transaction_product_service')->get();
    	foreach ($trxProducts as $trxProduct) {
    		$alreadyRejected = $trxProduct['reject_at'] ? 1 : 0;
    		$trxProduct->update([
				'reject_at' => date('Y-m-d H:i:s'),
				'reject_reason' => $data['reject_reason'] ?? null
			]);

    		if ($alreadyRejected) {
    			continue;
    		}
	    	if (isset($trxProduct['transaction_product_service']['id_transaction_product_service'])) {
				HairstylistNotAvailable::where('id_transaction_product_service', $trxProduct['transaction_product_service']['id_transaction_product_service'])->delete();
			} else {
				app('Modules\Transaction\Http\Controllers\ApiTransactionOutletService')->returnProductStock($trxProduct->id_transaction_product);
			}
    	}

    	return true;
    }
}
