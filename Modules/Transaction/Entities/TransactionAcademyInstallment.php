<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademyInstallment extends Model
{
    protected $table = 'transaction_academy_installment';

    protected $primaryKey = 'id_transaction_academy_installment';

    protected $fillable   = [
        'id_transaction_academy',
        'installment_receipt_number',
        'installment_step',
        'percent',
        'amount',
        'deadline',
        'paid_status',
        'completed_installment_at',
        'void_date'
    ];
}
