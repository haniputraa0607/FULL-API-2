<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class UnitIcount extends Model
{
    public $table = 'unit_icounts';
    public $primaryKey = 'id_unit_icount';
    protected $fillable = [
        'id_product_icount',
        'unit',
    ];

    public function conversion(){
        return $this->hasMany(UnitIcountConversion::class, 'id_unit_icount');
    }
    
    public function unit_product(){
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }
}
