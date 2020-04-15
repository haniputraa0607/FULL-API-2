<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class Disburse extends Model
{
    protected $table = 'disburse';
	protected $primaryKey = 'id_disburse';

	protected $fillable = [
	    'id_outlet',
	    'disburse_nominal',
		'disburse_status',
        'id_bank_name',
        'beneficiary_account_number',
        'beneficiary_name',
        'beneficiary_alias',
        'beneficiary_email',
        'request',
        'response',
        'notes',
        'reference_no'
	];
}
