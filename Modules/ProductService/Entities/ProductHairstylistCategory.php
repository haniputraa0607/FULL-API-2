<?php

namespace Modules\ProductService\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductHairstylistCategory extends Model
{
    protected $table = 'product_hairstylist_categories';
    public $primaryKey = 'id_product_hairstylist_category';

    protected $fillable = [
        'id_product',
        'id_hairstylist_category'
    ];
}
