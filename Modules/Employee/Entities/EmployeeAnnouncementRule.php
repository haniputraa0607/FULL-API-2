<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeAnnouncementRule extends Model
{
    protected $primaryKey = 'id_employee_announcement_rule';

	protected $casts = [
		'id_employee_announcement_rule_parent' => 'int',
		'employee_announcement_rule_param_id' => 'int'
	];

	protected $fillable = [
		'id_employee_announcement_rule_parent',
		'employee_announcement_rule_subject',
		'employee_announcement_rule_operator',
		'employee_announcement_rule_param',
		'employee_announcement_rule_param_select',
		'employee_announcement_rule_param_id'
	];

	public function employee_announcement_rule_parent()
	{
		return $this->belongsTo(\Modules\Employee\Entities\EmployeeAnnouncementRuleParent::class, 'id_employee_announcement_rule_parent');
	}
}
