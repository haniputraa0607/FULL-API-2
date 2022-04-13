<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class UnitConversionLog extends Model
{
    public $table = 'unit_conversion_logs';
    public $primaryKey = 'id_unit_conversion_log';
    protected $fillable = [
        'code_conversion',
        'id_user',
        'id_outlet',
        'id_product_icount',
        'unit',
        'qty_before_conversion',
        'qty_conversion',
        'unit_conversion',
        'conversion_type',
        'ratio',
        'qty_after_conversion',
        'qty_unit_converion',
    ];
}
