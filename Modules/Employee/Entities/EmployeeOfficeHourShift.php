<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeOfficeHourShift extends Model
{
    protected $table = 'employee_office_hour_shift';

    protected $primaryKey = 'id_employee_office_hour_shift';
    
    protected $fillable = [
        'id_employee_office_hour',
        'shift_name',
        'shift_start',
        'shift_end'
    ];
}
