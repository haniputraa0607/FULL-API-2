<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Product;

use function PHPSTORM_META\map;

class ProductProductIcount extends Model
{
    protected $table = 'product_product_icounts';
	protected $primaryKey = "id_product_product_icount";

	protected $fillable = [
        'id_product',
        'id_product_icount',
        'unit',
        'qty',
        'optional'
    ];

    public function products(){
        return $this->belongsTo(Product::class, 'id_product');
    }
    public function product_icounts(){
        return $this->belongsTo(ProductIcount::class, 'id_product_icount');
    }
}
