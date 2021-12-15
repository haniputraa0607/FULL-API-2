<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionBreakdown extends Model
{
    protected $table = 'transaction_breakdowns';
	protected $primaryKey = "id_transaction_breakdown";

	protected $fillable = [
        'id_transaction_product',
        'type',
        'value',
    ];
}
