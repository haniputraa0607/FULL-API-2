<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductCatalogDetail extends Model
{
    public $table = 'product_catalog_details';
    public $primaryKey = 'id_product_catalog_detail';
    protected $fillable = [
        'id_product_catalog',
        'id_product_icount',
        'filter',
        'budget_code',
    ];

    public function product_catalog()
    {
        return $this->belongsTo(ProductCatalog::class, 'id_product_catalog');
    }
    public function product_catalog_product_icount()
    {
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }
}
