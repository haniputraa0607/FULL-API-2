<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Product\Entities\ProductIcount;

class LocationOutletStarterBundlingProduct extends Model
{
    public $table = 'location_outlet_starter_bundling_products';
    public $primaryKey = 'id_location_outlet_starter_bundling_product';
    protected $fillable = [
        'id_location',
        'id_product_icount',
        'qty',
        'filter',
        'unit',
        'budget_code',
        'description',
    ];

    public function product()
    {
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }
}
