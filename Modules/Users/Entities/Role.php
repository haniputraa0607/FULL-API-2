<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
	protected $primaryKey = 'id_role';

	protected $fillable = [
		'role_name',
		'id_department',
        'id_job_level',
        'id_employee_office_hour',
        'created_by',
        'updated_by'
	];

	public function roles_features(){
        return $this->hasMany(RolesFeature::class, 'id_role', 'id_role');
    }

	public function office_hour(){
        return $this->belongsTo(\Modules\Employee\Entities\EmployeeOfficeHour::class, 'id_employee_office_hour', 'id_employee_office_hour');
    }
}
