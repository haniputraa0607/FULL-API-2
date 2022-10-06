<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Entities\EmployeeReimbursementDocument;
class EmployeeCashAdvanceDocument extends Model
{
    protected $table = 'employee_cash_advance_icounts';

    protected $primaryKey = 'id_employee_cash_advance_icount';
    
   protected $fillable = [
        'id_purchase_deposit_request',
        'value_detail',
        'status',
        'created_at',
        'updated_at',
    ];
}
