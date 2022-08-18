<?php

namespace Modules\Employee\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class EmployeeReimbursementDocument extends Authenticatable
{
    protected $table = 'employee_reimbursement_documents';

    protected $primaryKey = 'id_employee_reimbursement_document';

    protected $fillable   = [
        'id_employee_reimbursement',
        'document_type',
        'process_date',
        'id_approved',
        'process_notes',
        'attachment',
    ];

}
