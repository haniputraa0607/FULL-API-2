<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeJobExperience extends Model
{
    protected $table = 'employee_job_experiences';

    protected $primaryKey = 'id_employee_job_experience';
    
    protected $fillable = [
        'id_user',
        'company_name',
        'company_address',
        'company_position',
        'industry_type',
        'working_period',
        'employment_contract',
        'total_income',
        'scope_work',
        'achievement',
        'reason_resign',
        'created_at',
        'updated_at',
    ];

}
