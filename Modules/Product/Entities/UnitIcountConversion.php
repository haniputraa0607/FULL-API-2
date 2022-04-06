<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class UnitIcountConversion extends Model
{
    public $table = 'unit_icount_conversions';
    public $primaryKey = 'id_unit_icount_conversion';
    protected $fillable = [
        'id_unit_icount',
        'qty_conversion',
        'unit_conversion'
    ];
    public function unit_conversion(){
        return $this->belongsTo(UnitIcount::class, 'id_unit_icount');
    }

}
