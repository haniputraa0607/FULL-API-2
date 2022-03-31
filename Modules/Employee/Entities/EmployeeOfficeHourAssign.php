<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeOfficeHourAssign extends Model
{
    protected $table = 'employee_office_hour_assign';

    protected $primaryKey = 'id_employee_office_hour_assign';
    
    protected $fillable = [
        'employee_office_hour_assign_name',
        'id_employee_office_hour',
        'id_department',
        'id_job_level',
        'created_by',
        'updated_by'
    ];
}
