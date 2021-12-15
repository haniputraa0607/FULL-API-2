<?php

namespace Modules\Transaction\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class TransactionAcademyInstallmentUpdate extends Model
{
    protected $table = 'transaction_academy_installment_updates';

    protected $primaryKey = 'id_transaction_academy_installment_update';

    protected $fillable   = [
        'id_transaction_academy_installment',
        'installment_receipt_number_old'
    ];
}
