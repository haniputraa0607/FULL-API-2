<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class DeliveryProductDetail extends Model
{
    protected $table = 'delivery_product_details';
	protected $primaryKey = "id_delivery_product_detail";

	protected $fillable = [
        'id_delivery_product',
        'id_product_icount',
        'value',
        'status',
	];

    public function delivery_product(){
        return $this->belongsTo(DeliveryProduct::class, 'id_delivery_product');
    }

    public function delivery_product_icount(){
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }
}
