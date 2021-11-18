<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademy extends Model
{
    protected $table = 'transaction_academy';

    protected $primaryKey = 'id_transaction_academy';

    protected $fillable   = [
        'id_transaction',
        'payment_method',
        'total_installment',
        'amount_completed',
        'amount_not_completed',
        'transaction_academy_duration',
        'transaction_academy_total_meeting',
        'transaction_academy_hours_meeting'
    ];
}
