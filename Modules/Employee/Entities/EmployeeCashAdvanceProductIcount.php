<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeCashAdvanceProductIcount extends Model
{
    protected $table = 'employee_cash_advance_product_icounts';

    protected $primaryKey = 'id_employee_cash_advance_product_icount';
    
    protected $fillable = [
        'id_product_icount',
        'created_at',
        'updated_at',
    ];
}
