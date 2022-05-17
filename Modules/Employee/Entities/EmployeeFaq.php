<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeFaq extends Model
{
    protected $table = 'employee_faqs';

    protected $primaryKey = 'id_employee_faq';
    
    protected $fillable = [
        'faq_question',
        'faq_answer',
        'created_at',
        'updated_at',
    ];
}
