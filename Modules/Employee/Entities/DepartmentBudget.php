<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class DepartmentBudget extends Model
{
    protected $table = 'department_budgets';

    protected $primaryKey = 'id_department_budget';

    protected $fillable = [
        'id_department',
        'budget_balance',
    ];

    public function logs()
	{
		return $this->hasMany(\Modules\Employee\Entities\DepartmentBudgetLog::class, 'id_department_budget');
	}
    public function department()
	{
		return $this->belongsTo(\Modules\Users\Entities\Department::class, 'id_department');
	}
}
