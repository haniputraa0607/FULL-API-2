<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeAnnouncementRuleParent extends Model
{
    protected $primaryKey = 'id_employee_announcement_rule_parent';

	protected $casts = [
		'id_employee_announcement' => 'int'
	];

	protected $fillable = [
		'id_employee_announcement',
		'employee_announcement_rule',
		'employee_announcement_rule_next'
	];

	public function employee_announcement()
	{
		return $this->belongsTo(\Modules\Employee\Entities\EmployeeAnnouncement::class, 'id_employee_announcement');
	}

	public function employee_announcement_rules()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeAnnouncementRule::class, 'id_employee_announcement_rule_parent');
	}

	public function rules()
	{
		return $this->hasMany(\Modules\Employee\Entities\EmployeeAnnouncementRule::class, 'id_employee_announcement_rule_parent')
					->select('id_employee_announcement_rule','id_employee_announcement_rule_parent','employee_announcement_rule_subject as subject', 'employee_announcement_rule_operator as operator', 'employee_announcement_rule_param as parameter', 'employee_announcement_rule_param_id as id', 'employee_announcement_rule_param_select as parameter_select');
	}
}
