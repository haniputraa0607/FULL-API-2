<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeOvertimeDocument extends Model
{
    protected $table = 'employee_overtime_documents';
    protected $primaryKey = 'id_employee_overtime_document';

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'id_employee_overtime',
		'type',
		'date',
		'id_user_approved',
        'notes',
		'attachment',
        'created_at',
        'updated_at',
	];
}
