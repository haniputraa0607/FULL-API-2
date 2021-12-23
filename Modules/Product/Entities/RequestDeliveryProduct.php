<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryRequestProduct extends Model
{
    public $timestamps = false;
    protected $table = 'delivery_request_products';

	protected $fillable = [
        'id_delivery_product',
        'id_request_product',
	];

    public function delivery_product()
	{
        return $this->belongsTo(\Modules\Product\Entities\DeliveryProduct::class, 'id_delivery_product', 'id_delivery_product');
	}
}
