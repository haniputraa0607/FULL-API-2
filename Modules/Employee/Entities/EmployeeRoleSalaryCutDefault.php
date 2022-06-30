<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleSalaryCutDefault extends Model
{
    protected $table = 'employee_role_default_salary_cuts';

    protected $primaryKey = 'id_employee_role_default_salary_cut';
    
    protected $fillable = [
        'code',
        'name',
        'value',
        'formula',
        'created_at',
        'updated_at',
    ];
}