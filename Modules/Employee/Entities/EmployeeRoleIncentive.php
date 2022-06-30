<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleIncentive extends Model
{
    protected $table = 'employee_role_incentives';

    protected $primaryKey = 'id_employee_role_incentive';
    
    protected $fillable = [
        'id_role',
        'id_employee_role_default_incentive',
        'value',
        'formula',
        'created_at',
        'updated_at',
    ];
}