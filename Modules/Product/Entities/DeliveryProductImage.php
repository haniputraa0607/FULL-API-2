<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryProductImage extends Model
{
    protected $table = 'delivery_product_images';
	protected $primaryKey = "id_delivery_product_image";

	protected $fillable = [
        'id_delivery_product',
        'path',
	];

    public function delivery_product(){
        return $this->belongsTo(DeliveryProduct::class, 'id_delivery_product');
    }
}
