<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeLoan extends Model
{
    protected $table = 'employee_loans';
	protected $primaryKey = 'id_employee_loan';

	protected $fillable = [
        'id_user',
        'id_employee_category_loan',
        'effective_date',
        'amount',
        'installment',
        'type',
        'notes',
        'id_employee_sales_payment',
        'created_at',   
        'updated_at'
	];
        public function loan(){
            return $this->hasMany(EmployeeLoanReturn::class, 'id_employee_loan');
        }
}
