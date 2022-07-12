<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class QuestionEmployee extends Model
{
    protected $table = 'question_employees';

    protected $primaryKey = 'id_question_employee';
    
    protected $fillable = [
        'id_category_question',
        'type',
        'question'
    ];
    public function category()
	{
		return $this->belongsTo(CategoryQuestion::class, 'id_category_question');
	}
    public function employee()
	{
		return $this->hasOne(EmployeeQuestions::class, 'id_question_employee','id_question_employee');
	}
}
