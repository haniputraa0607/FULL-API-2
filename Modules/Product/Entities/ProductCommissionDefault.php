<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductCommissionDefault extends Model
{
    protected $table = 'product_commission_default';
    public $primaryKey = 'id_product_commission_default';
    protected $fillable = [
        'id_product',
        'percent',
        'commission',
        'created_at',
        'updated_at'
    ];

    public function product(){
        return $this->belongsTo(App\Http\Models\Product::class, 'id_product');
    }

}
