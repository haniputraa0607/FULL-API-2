<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class LocationOutletStarterBundlingProduct extends Model
{
    public $table = 'location_outlet_starter_bundling_products';
    public $primaryKey = 'id_location_outlet_starter_bundling_product';
    protected $fillable = [
        'id_location',
        'id_product_icount',
        'qty',
        'unit',
        'budget_code',
        'description',
    ];
}
