<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class DepartmentBudgetLog extends Model
{
    protected $table = 'department_budget_logs';

    protected $primaryKey = 'id_department_budget_log';

    protected $fillable = [
        'id_department_budget',
        'date_budgeting',
        'source',
        'balance',
        'balance_before',
        'balance_after',
        'balance_total',
        'id_reference',
        'notes'

    ];

    public function department_budget()
	{
		return $this->belongsTo(\Modules\Employee\Entities\DepartmentBudget::class, 'id_department_budget');
	}
}
