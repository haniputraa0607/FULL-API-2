<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class OutletStarterBundlingProduct extends Model
{
    public $primaryKey = 'id_outlet_starter_bundling_product';
    protected $fillable = [
        'id_product_icount',
        'qty',
        'budget_code',
        'description',
    ];
}
