<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class LogApiIcount extends Model
{
    protected $table = 'log_api_icount';
	public $primaryKey = 'id_log_api_icount';
	protected $connection = 'mysql2';
    protected $fillable = [
    	'type',
    	'id_reference',
    	'request_url',
    	'request_method',
        'request_parameter',
    	'response_body',
        'response_header',
    	'response_code'
    ];
}
