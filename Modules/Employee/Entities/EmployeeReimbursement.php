<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Entities\EmployeeReimbursementDocument;
class EmployeeReimbursement extends Model
{
    protected $table = 'employee_reimbursements';

    protected $primaryKey = 'id_employee_reimbursement';
    
    protected $fillable = [
        'id_user',
        'id_user_approved',
        'id_product_icount',
        'date_reimbursement',
        'notes',
        'approve_notes',
        'attachment',
        'price',
        'qty',
        'id_purchase_invoice',
        'value_detail',
        'due_date',
        'date_submission',
        'date_validation',
        'validator_reimbursement',
        'date_send_reimbursement',
        'status',
        'created_at',
        'updated_at',
        'read'
    ];
    public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user','id');
	}
    public function approval_user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user_approved','id');
	}
        public function document() {
            return $this->hasMany(EmployeeReimbursementDocument::class,'id_employee_reimbursement','id_employee_reimbursement'); 
        }    
}
