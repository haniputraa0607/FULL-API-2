<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeTimeOffDocument extends Model
{
    protected $table = 'employee_time_off_documents';
    protected $primaryKey = 'id_employee_time_off_document';

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'id_employee_time_off',
		'type',
		'date',
		'id_user_approved',
        'notes',
		'attachment',
        'created_at',
        'updated_at',
	];
}
