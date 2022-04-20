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

}
