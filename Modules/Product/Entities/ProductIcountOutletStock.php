<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductIcountOutletStock extends Model
{
    protected $table = 'product_icount_outlet_stocks';
	protected $primaryKey = "id_product_icount_outlet_stock";

	protected $fillable = [
        'id_product_icount',
        'id_outlet',
        'unit',
        'stock',
    ];
}
