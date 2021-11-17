<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademyInstallment extends Model
{
    protected $table = 'transaction_academy_installment';

    protected $primaryKey = 'id_transaction_academy_installment';

    protected $fillable   = [
        'id_transaction_academy',
        'percent',
        'amount',
        'deadline',
        'completed_installment_at'
    ];
}
