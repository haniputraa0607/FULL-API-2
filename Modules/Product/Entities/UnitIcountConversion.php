<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class UnitIcountConversion extends Model
{
    public $table = 'unit_icount_conversions';
    public $primaryKey = 'id_unit_icount_conversion';
    protected $fillable = [
        'id_product_icount',
        'qty',
        'unit',
        'qty_conversion',
        'unit_conversion'
    ];

}
