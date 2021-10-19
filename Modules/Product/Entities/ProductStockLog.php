<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductStockLog extends Model
{
    protected $table = 'product_stock_logs';
    public $primaryKey = 'id_product_stock_log';
    protected $fillable = [
        'id_product',
        'id_product_variant_group',
        'id_transaction',
        'id_user_hair_stylist',
        'stock_item',
        'stock_service',
        'stock_item_before',
        'stock_service_before',
        'stock_item_after',
        'stock_service_after'
    ];
}
