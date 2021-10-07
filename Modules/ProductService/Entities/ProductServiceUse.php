<?php

namespace Modules\ProductService\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductServiceUse extends Model
{
    protected $table = 'product_service_use';
    public $primaryKey = 'id_product_service_use';

    protected $fillable = [
        'id_product_service',
        'id_product',
        'quantity_use'
    ];
}
