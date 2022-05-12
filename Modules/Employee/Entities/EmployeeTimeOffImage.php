<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeTimeOffImage extends Model
{
    protected $table = 'employee_time_off_images';
	protected $primaryKey = "id_employee_time_off_image";

	protected $fillable = [
        'id_employee_time_off',
        'path',
	];

    public function employee_time_off(){
        return $this->belongsTo(EmployeeTimeOff::class, 'id_employee_time_off');
    }
}
