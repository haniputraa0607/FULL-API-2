<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeReimbursement extends Model
{
    protected $table = 'employee_reimbursements';

    protected $primaryKey = 'id_employee_reimbursement';
    
    protected $fillable = [
        'id_user',
        'id_user_approved',
        'name_reimbursement',
        'date_reimbursement',
        'notes',
        'approve_notes',
        'attachment',
        'price',
        'date_submission',
        'date_validation',
        'validator_reimbursement',
        'date_send_reimbursement',
        'status',
        'created_at',
        'updated_at',
    ];
    public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user','id');
	}
    public function approval_user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user_approved','id');
	}
}