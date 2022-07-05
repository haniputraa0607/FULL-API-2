<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleBasicSalary extends Model
{
    protected $table = 'employee_role_basic_salary';

    protected $primaryKey = 'id_employee_role_basic_salary';
    
    protected $fillable = [
        'id_role',
        'value',
        'created_at',
        'updated_at',
    ];
}