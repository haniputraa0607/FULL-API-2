<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletStarterBundlingProduct extends Model
{
    public $primaryKey = 'id_outlet_starter_bundling_product';
    protected $fillable = [
        'id_outlet_starter_bundling',
        'id_product_icount',
        'qty',
        'unit',
        'budget_code',
        'description',
    ];

    public function product()
    {
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }
}
