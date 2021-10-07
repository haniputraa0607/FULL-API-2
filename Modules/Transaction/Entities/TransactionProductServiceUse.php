<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionProductServiceUse extends Model
{
    protected $table = 'transaction_product_service_use';

    protected $primaryKey = 'id_transaction_product_service_use';

    protected $fillable   = [
        'id_transaction',
        'id_transaction_product_service',
        'id_product',
        'quantity_use'
    ];
}
