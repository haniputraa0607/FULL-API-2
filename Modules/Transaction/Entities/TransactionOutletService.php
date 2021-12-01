<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

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
        'completed_at'
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
}
