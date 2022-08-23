<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductCommissionDefaultDynamic extends Model
{
    protected $table = 'product_commission_default_dynamics';
	protected $primaryKey = 'id_product_commission_default_dynamics';


	protected $fillable = [
		'id_product_commission_default',
		'operator',
		'qty',
		'value'
	];

    public function default(){
        return $this->belongsTo(Modules\Product\Entities\ProductCommissionDefault::class, 'id_product_commission_default');
    }
}
