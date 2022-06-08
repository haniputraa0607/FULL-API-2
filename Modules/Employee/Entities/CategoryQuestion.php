<?php

namespace Modules\Employee\Entities;

use Illuminate\Database\Eloquent\Model;

class CategoryQuestion extends Model
{
    protected $table = 'category_questions';

    protected $primaryKey = 'id_category_question';
    
    protected $fillable = [
        'name_category'
    ];
     public function questions() {
    	return $this->hasMany(\Modules\Employee\Entities\QuestionEmployee::class, 'id_category_question', 'id_category_question');
    }
}
