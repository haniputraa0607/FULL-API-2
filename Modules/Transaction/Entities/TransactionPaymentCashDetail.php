<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionPaymentCashDetail extends Model
{
    protected $table = 'transaction_payment_cash_details';

    protected $primaryKey = 'id_transaction_payment_cash_detail';

    protected $fillable   = [
        'id_transaction_payment_cash',
        'id_transaction_product',
        'id_outlet_cash',
        'cash_received_by',
    ];

}
