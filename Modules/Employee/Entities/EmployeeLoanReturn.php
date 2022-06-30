<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoanReturn extends Model
{
    protected $table = 'employee_loan_returns';
	protected $primaryKey = 'id_employee_loan_return';

	protected $fillable = [
        'id_employee_loan',
        'return_date',
        'date_pay',
        'amount_return',
        'status_return',
        'created_at',   
        'updated_at'
	];
}
