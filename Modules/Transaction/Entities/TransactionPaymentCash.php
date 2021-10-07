<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionPaymentCash extends Model
{
    protected $table = 'transaction_payment_cash';

    protected $primaryKey = 'id_transaction_payment_cash';

    protected $fillable   = [
        'id_transaction',
        'payment_code',
        'cash_nominal',
        'cash_received_by'
    ];

}
