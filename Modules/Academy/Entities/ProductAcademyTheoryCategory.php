<?php

namespace Modules\Academy\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductAcademyTheoryCategory extends Model
{
    protected $table = 'product_academy_theory_categories';

    protected $primaryKey = 'id_product_academy_theory_category';
    
    protected $fillable = [
        'id_product',
        'id_theory_category'
    ];
}
