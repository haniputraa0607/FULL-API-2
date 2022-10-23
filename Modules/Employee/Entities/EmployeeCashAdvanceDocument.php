<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Entities\EmployeeReimbursementDocument;
class EmployeeCashAdvanceDocument extends Model
{
    protected $table = 'employee_cash_advance_documents';

    protected $primaryKey = 'id_employee_cash_advance_document';
    
   protected $fillable   = [
        'id_employee_cash_advance',
        'document_type',
        'process_date',
        'id_approved',
        'process_notes',
        'attachment',
    ];  
}
