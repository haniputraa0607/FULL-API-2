<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeRoleIncentiveDefault extends Model
{
    protected $table = 'employee_role_default_incentives';

    protected $primaryKey = 'id_employee_role_default_incentive';
    
    protected $fillable = [
        'code',
        'name',
        'value',
        'formula',
        'created_at',
        'updated_at',
    ];
}