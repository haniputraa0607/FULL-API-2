<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Entities\EmployeeCashAdvanceDocument;
class EmployeeCashAdvance extends Model
{
    protected $table = 'employee_cash_advances';

    protected $primaryKey = 'id_employee_cash_advance';
    
    protected $fillable = [
        'id_user',
        'id_user_approved',
        'date_cash_advance',
        'title',
        'price',
        'status',
        'notes',
        'attachment',
        'id_purchase_deposit_request',
        'value_detail',
        'tax_date',
        'date_send_cash_advance',
        'date_validation',
        'date_disburse',
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
        public function document() {
            return $this->hasMany(EmployeeCashAdvanceDocument::class,'id_employee_cash_advance','id_employee_cash_advance'); 
        }    
}
