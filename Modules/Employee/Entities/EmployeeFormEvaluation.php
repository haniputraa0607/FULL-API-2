<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeFormEvaluation extends Model
{
    protected $table = 'employee_form_evaluations';

    protected $primaryKey = 'id_employee_form_evaluation';
    
    protected $fillable = [
        'id_employee',
        'work_productivity',
        'work_quality',
        'knwolege_task',
        'relationship',
        'cooperation',
        'discipline',
        'initiative',
        'expandable',
        'comment',
        'update_status',
        'current_extension',
        'time_extension',
        'id_manager',
        'update_manager',
        'id_hrga',
        'update_hrga',
        'id_director',
        'update_director',
        'status_form',
        'created_at',
        'updated_at',
    ];

    public function employee()
	{
		return $this->belongsTo(\Modules\Employee\Employee::class, 'id_employee','id_employee');
	}
}
