<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionProductServiceLog extends Model
{
    protected $table = 'transaction_product_service_logs';

    protected $primaryKey = 'id_transaction_product_service_log';

    protected $fillable   = [
        'id_transaction_product_service',
        'action'
    ];

    public function transaction_product_service()
	{
		return $this->belongsTo(\Modules\Transaction\Entities\TransactionProductService::class, 'id_transaction_product_service');
	}

}
