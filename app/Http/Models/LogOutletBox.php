<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogTransactionUpdate extends Model
{
	protected $table = 'log_outlet_box';
	public $primaryKey = 'id_log_outlet_box';
	protected $connection = 'mysql2';
    protected $fillable = [
    	'id_log_outlet_box',
    	'id_user_hair_stylist',
    	'assigned_by',
    	'id_outlet_box',
        'note'
    ];
}
