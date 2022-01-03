<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class RequestProductDetail extends Model
{
    protected $table = 'request_product_details';
	protected $primaryKey = "id_request_product_detail";

	protected $fillable = [
        'id_request_product',
        'id_product_icount',
        'value',
        'status',
	];

    public function request_product(){
        return $this->belongsTo(RequestProduct::class, 'id_request_product');
    }

    public function request_product_icount(){
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }
}
