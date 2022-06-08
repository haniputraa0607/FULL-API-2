<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeReimbursementIcount extends Model
{
    protected $table = 'employee_reimbursement_icounts';

    protected $primaryKey = 'id_employee_reimbursement_icount';
    
    protected $fillable = [
        'id_purchase_invoice',
        'value_detail',
        'status',
        'created_at',
        'updated_at',
    ];
}
