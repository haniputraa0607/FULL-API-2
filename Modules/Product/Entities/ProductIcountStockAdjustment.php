<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductIcountStockAdjustment extends Model
{
    protected $primaryKey = 'id_product_icount_stock_adjustment';
    protected $fillable = [
        'id_product_icount',
        'id_user',
        'id_outlet',
        'unit',
        'stock_adjustment',
        'notes',
        'title',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
    }

    public function product_icount()
    {
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }

    public function unit_icount()
    {
        return $this->belongsTo(UnitIcount::class, 'unit');
    }

    public function outlet()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
    }
}
