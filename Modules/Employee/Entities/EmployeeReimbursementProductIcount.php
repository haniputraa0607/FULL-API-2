<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeReimbursementProductIcount extends Model
{
    protected $table = 'employee_reimbursement_product_icounts';

    protected $primaryKey = 'id_employee_reimbursement_product_icount';
    
    protected $fillable = [
        'id_product_icount',
        'created_at',
        'updated_at',
    ];
}
