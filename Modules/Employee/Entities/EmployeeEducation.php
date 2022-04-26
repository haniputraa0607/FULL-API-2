<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeEducation extends Model
{
    protected $table = 'employee_educations';

    protected $primaryKey = 'id_employee_education';
    
    protected $fillable = [
        'id_user',
        'educational_level',
        'name_school',
        'year_education',
        'study_program',
        'id_city',
        'created_at',
        'updated_at',
    ];

}
