<?php

namespace Modules\Employee\Entities;

use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class EmployeeChangeShift extends Model
{
    protected $table = 'employee_change_shifts';

    protected $primaryKey = 'id_employee_change_shift';
    
    protected $fillable = [
        'id_user',
        'change_shift_date',
        'id_employee_office_hour_shift',
        'reason',
        'status',
        'id_approve',
        'approve_date',
        'created_at',
        'updated_at',
    ];

    public function user()
	{
		return $this->belongsTo(User::class, 'id_user','id');
	}

    public function approve()
	{
		return $this->belongsTo(User::class, 'id_approve','id');
	}

    public function office_hour_shift()
	{
		return $this->belongsTo(EmployeeOfficeHourShift::class, 'id_employee_office_hour_shift','id_employee_office_hour_shift');
	}
}
