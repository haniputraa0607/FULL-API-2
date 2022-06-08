<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeFaqLog extends Model
{
    protected $table = 'employee_faq_logs';

    protected $primaryKey = 'id_employee_faq_log';
    
    protected $fillable = [
        'id_employee_faq',
        'created_at',
        'updated_at',
    ];
}
