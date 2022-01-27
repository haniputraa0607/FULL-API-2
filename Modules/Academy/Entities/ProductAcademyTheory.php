<?php

namespace Modules\Academy\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductAcademyTheory extends Model
{
    protected $table = 'product_academy_theory';

    protected $primaryKey = 'id_product_academy_theory';
    
    protected $fillable = [
        'id_product',
        'id_theory'
    ];
}
