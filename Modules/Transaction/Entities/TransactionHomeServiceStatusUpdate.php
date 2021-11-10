<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionHomeServiceStatusUpdate extends Model
{
    protected $table = 'transaction_home_service_status_updates';

    protected $primaryKey = 'id_transaction_home_service_update';

    protected $fillable   = [
        'id_transaction',
        'status'
    ];
}
