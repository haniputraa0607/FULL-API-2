<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeFamily extends Model
{
    protected $table = 'employee_families';

    protected $primaryKey = 'id_employee_family';
    
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
