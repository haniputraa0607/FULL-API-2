<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeOfficeHour extends Model
{
    protected $table = 'employee_office_hours';

    protected $primaryKey = 'id_employee_office_hour';
    
    protected $fillable = [
        'office_hour_name',
        'office_hour_type',
        'office_hour_start',
        'office_hour_end'
    ];

    public function office_hour_shift(){
        return $this->hasMany(\Modules\Employee\Entities\EmployeeOfficeHourShift::class, 'id_employee_office_hour', 'id_employee_office_hour');
    }
}
