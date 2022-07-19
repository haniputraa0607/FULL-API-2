<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleOvertimeDefault extends Model
{
    protected $table = 'employee_role_default_overtimes';

    protected $primaryKey = 'id_employee_role_default_overtime';
    
    protected $fillable = [
        'hours',
        'value',
        'created_at',
        'updated_at',
    ];
}
