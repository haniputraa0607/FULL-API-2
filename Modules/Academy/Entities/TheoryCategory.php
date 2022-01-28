<?php

namespace Modules\Academy\Entities;

use Illuminate\Database\Eloquent\Model;

class TheoryCategory extends Model
{
    protected $table = 'theory_categories';

    protected $primaryKey = 'id_theory_category';
    
    protected $fillable = [
        'id_parent_theory_category',
        'theory_category_name'
    ];
}
