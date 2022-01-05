<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductIcountOutletStockLog extends Model
{
    protected $table = 'product_icount_outlet_stock_logs';
	protected $primaryKey = "id_product_icount_outlet_stock_log";

	protected $fillable = [
        'id_outlet',
        'id_product_icount',
        'unit',
        'stock_before',
        'operator',
        'stock_after',
        'qty',
        'id_reference',
        'source',
    ];
}
