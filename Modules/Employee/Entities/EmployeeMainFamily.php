<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeMainFamily extends Model
{
    protected $table = 'employee_main_families';

    protected $primaryKey = 'id_employee_main_family';
    
    protected $fillable = [
        'id_user',
        'family_members',
        'name_family',
        'gender_family',
        'birthplace_family',
        'birthday_family',
        'education_family',
        'job_family',
        'created_at',
        'updated_at',
    ];

}
