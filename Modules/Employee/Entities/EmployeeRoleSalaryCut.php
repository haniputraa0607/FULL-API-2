<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleSalaryCut extends Model
{
    protected $table = 'employee_role_salary_cuts';

    protected $primaryKey = 'id_employee_role_salary_cut';
    
    protected $fillable = [
        'id_role',
        'id_employee_role_default_salary_cut',
        'value',
        'formula',
        'created_at',
        'updated_at',
    ];
}