<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeAnnouncement extends Model
{
    
	protected $primaryKey = 'id_employee_announcement';

	protected $dates = [
		'date_start',
		'date_end'
	];

	protected $fillable = [
		'date_start',
		'date_end',
		'content'
	];

	public function employee_announcement_rule_parents()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeAnnouncementRuleParent::class, 'id_employee_announcement')
					->select('id_employee_announcement_rule_parent', 'id_employee_announcement', 'employee_announcement_rule as rule', 'employee_announcement_rule_next as rule_next');
	}
}
