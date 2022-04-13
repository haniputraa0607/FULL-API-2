<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeEducationNonFormal extends Model
{
    protected $table = 'employee_education_non_formal';

    protected $primaryKey = 'id_employee_education_non_formal';
    
    protected $fillable = [
        'id_user',
        'course_type',
        'long_term',
        'year_education_non_formal',
        'certificate',
        'financed_by',
        'created_at',
        'updated_at',
    ];

}
