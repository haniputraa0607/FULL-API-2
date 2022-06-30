<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeCategoryLoan extends Model
{
    protected $table = 'employee_category_loans';
	protected $primaryKey = 'id_employee_category_loan';

	protected $fillable = [
        'name_category_loan',
        'created_at',   
        'updated_at'
	];
}
