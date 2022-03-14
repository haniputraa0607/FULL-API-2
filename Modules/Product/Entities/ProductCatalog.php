<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductCatalog extends Model
{
    public $table = 'product_catalogs';
    public $primaryKey = 'id_product_catalog';
    protected $fillable = [
        'name',
        'company_type',
        'description',
        'status',
    ];

    public function product_catalog_details()
    {
        return $this->hasMany(ProductCatalogDetail::class, 'id_product_catalog');
    }
}
