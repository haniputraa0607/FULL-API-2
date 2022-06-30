<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleFixedIncentive extends Model
{
    protected $table = 'employee_role_fixed_incentives';

    protected $primaryKey = 'id_employee_role_fixed_incentive';
    
    protected $fillable = [
        'id_role',
        'id_employee_role_default_fixed_incentive_detail',
        'value',
        'created_at',
        'updated_at',
    ];
 
}
