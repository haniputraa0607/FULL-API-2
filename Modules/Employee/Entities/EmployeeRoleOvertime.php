<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleOvertime extends Model
{
    protected $table = 'employee_role_overtimes';

    protected $primaryKey = 'id_employee_role_overtime';
    
    protected $fillable = [
        'id_employee_role_default_overtime',
        'id_role',
        'value',
        'created_at',
        'updated_at',
    ];
}
