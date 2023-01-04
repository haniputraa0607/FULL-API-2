<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeFormEvaluation extends Model
{
    protected $table = 'employee_form_evaluations';

    protected $primaryKey = 'id_employee_form_evaluation';
    
    protected $fillable = [
        'id_employee',
        'code',
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
        'directory',
        'created_at',
        'updated_at',
    ];

    public function employee()
	{
		return $this->belongsTo(\Modules\Employee\Entities\Employee::class, 'id_employee','id_employee');
	}

    public function manager()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_manager','id');
	}

    public function hrga()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_hrga','id');
	}

    public function director()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_director','id');
	}
}
