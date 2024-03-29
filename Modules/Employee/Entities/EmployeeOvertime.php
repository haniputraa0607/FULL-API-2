<?php

namespace Modules\Employee\Entities;

use App\Http\Models\User;
use App\Http\Models\Outlet;
use Modules\Employee\Entities\EmployeeOvertimeDocument;
use Illuminate\Database\Eloquent\Model;

class EmployeeOvertime extends Model
{
    protected $table = 'employee_overtime';
    protected $primaryKey = 'id_employee_overtime';

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'id_employee',
		'id_outlet',
		'approve_by',
		'request_by',
		'date',
		'time',
		'duration',
		'rest_before',
		'rest_after',
		'notes',
        'approve_notes',
		'request_at',
		'approve_at',
		'reject_at',
		'id_assign',
		'read',
		'status'
	];

    public function employee(){
        return $this->belongsTo(User::class, 'id_employee');
    }

    public function outlet(){
        return $this->belongsTo(Outlet::class, 'id_outlet');
    }

    public function approve(){
        return $this->belongsTo(User::class, 'approve_by');
    }

    public function request(){
        return $this->belongsTo(User::class, 'request_by');
    }

	public function documents(){
		return $this->hasMany(EmployeeOvertimeDocument::class, 'id_employee_overtime');
	}
}
