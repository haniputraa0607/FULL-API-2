<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class RequestProductImage extends Model
{
    protected $table = 'request_product_images';
	protected $primaryKey = "id_request_product_image";

	protected $fillable = [
        'id_request_product',
        'path',
	];

    public function request_product(){
        return $this->belongsTo(RequestProduct::class, 'id_request_product');
    }
}
