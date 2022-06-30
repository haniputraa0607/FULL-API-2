<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleFixedIncentiveDefaultDetail extends Model
{
    protected $table = 'employee_role_default_fixed_incentive_details';

    protected $primaryKey = 'id_employee_role_default_fixed_incentive_detail';
    
    protected $fillable = [
        'id_employee_role_default_fixed_incentive',
        'range',
        'value',
        'created_at',
        'updated_at',
    ];
 
}
