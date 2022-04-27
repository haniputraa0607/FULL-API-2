<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Employee\Entities\QuestionEmployee;

class EmployeeQuestions extends Model
{
    protected $table = 'employee_questions';

    protected $primaryKey = 'id_employee_question';
    
    protected $fillable = [
        'id_user',
        'id_question_employee',
        'answer',
        'created_at',
        'updated_at',
    ];
    public function questions()
	{
		return $this->belongsTo(QuestionEmployee::class, 'id_question_employee');
	}
}
