<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductIcount extends Model
{
    protected $table = 'product_icounts';
	protected $primaryKey = "id_product_icount";

	protected $fillable = [
        'id_item',
        'id_company',
        'code',
        'name',
        'id_brand',
        'id_category',
        'id_sub_category',
        'item_group',
        'image_item',
        'unit1',
        'unit2',
        'unit3',
        'ratio2',
        'ratio3',
        'buy_price_1',
        'buy_price_2',
        'buy_price_3',
        'unit_price_1',
        'unit_price_2',
        'unit_price_3',
        'unit_price_4',
        'unit_price_5',
        'unit_price_6',
        'notes',
        'is_suspended',
        'is_sellable',
        'is_buyable',
        'id_cogs',
        'id_purchase',
        'id_sales',
        'id_deleted',
	];

    public function addLogStockProductIcount($qty, $source, $id_refrence = null, $desctiption = null){
        
    }
}
