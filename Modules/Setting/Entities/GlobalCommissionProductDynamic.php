<?php

namespace Modules\Setting\Entities;

use Illuminate\Database\Eloquent\Model;

class GlobalCommissionProductDynamic extends Model
{
    protected $table = 'global_commission_product_dynamics';
	protected $primaryKey = 'id_global_commission_product_dynamic';


	protected $fillable = [
		'operator',
		'qty',
		'value'
	];
}
