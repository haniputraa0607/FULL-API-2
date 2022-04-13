<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class EmployeeQuestions extends Model
{
    protected $table = 'employee_questions';

    protected $primaryKey = 'id_employee_question';
    
    protected $fillable = [
        'id_user',
        'category',
        'question',
        'answer',
        'created_at',
        'updated_at',
    ];

}
