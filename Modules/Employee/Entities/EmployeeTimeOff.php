<?php

namespace Modules\Employee\Entities;

use App\Http\Models\User;
use App\Http\Models\Outlet;
use Illuminate\Database\Eloquent\Model;

class EmployeeTimeOff extends Model
{
    protected $table = 'employee_time_off';
    protected $primaryKey = 'id_employee_time_off';

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'id_employee',
		'id_outlet',
		'type',
		'notes',
        'approve_notes',
		'approve_by',
		'request_by',
		'date',
		'start_time',
		'end_time',
		'request_at',
		'approve_at',
		'reject_at',
		'use_quota_time_off',
		'read'

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
}
