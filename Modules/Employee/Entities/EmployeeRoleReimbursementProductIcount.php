<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleReimbursementProductIcount extends Model
{
    protected $table = 'employee_role_reimbursement_product_icounts';

    protected $primaryKey = 'id_employee_role_reimbursement_product_icount';
    
    protected $fillable = [
        'id_employee_reimbursement_product_icount',
        'id_role',
        'value_text',
        'created_at',
        'updated_at',
    ];
}