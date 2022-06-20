<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistLoanReturn extends Model
{
    protected $table = 'hairstylist_loan_returns';
	protected $primaryKey = 'id_hairstylist_loan_return';

	protected $fillable = [
        'id_hairstylist_loan',
        'return_date',
        'date_pay',
        'amount_return',
        'status_return',
        'created_at',   
        'updated_at'
	];
}
