<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogTransactionUpdate extends Model
{
	protected $table = 'log_transaction_updates';
	public $primaryKey = 'id_log_transaction_update';
	protected $connection = 'mysql2';
    protected $fillable = [
    	'id_log_transaction_update',
    	'id_user',
    	'id_transaction',
    	'transaction_from',
        'old_data',
        'new_data',
    	'note'
    ];
}
