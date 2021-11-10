<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    protected $primaryKey = 'id_product_group';

    protected $fillable   = [
        'id_product_category',
        'product_group_code',
        'product_group_name',
        'product_group_description',
        'product_group_photo',
        'product_group_image_detail'
    ];

    public function product_category()
    {
    	return $this->belongsTo(\App\Http\Models\ProductCategory::class,'id_product_category','id_product_category');
    }

    public function products()
    {
        return $this->hasMany(\App\Http\Models\Product::class,'id_product_group','id_product_group');
    }

    public function getProductGroupPhotoAttribute($value)
    {
        if($value){
            return config('url.storage_url_api').$value;
        }
        $this->load(['products'=>function($query){
            $query->select('id_product','id_product_group')->whereHas('photos')->with('photos');
        }]);
        $prd = $this->products->toArray();
        if(!$prd){
            return config('url.storage_url_api').'img/product/item/default.png';
        }
        return ($prd[0]['photos'][0]['url_product_photo'] ?? config('url.storage_url_api').'img/product/item/default.png');
    }

    public function getProductGroupImageDetailAttribute($value)
    {
        if($value){
            return config('url.storage_url_api').$value;
        }
        return config('url.storage_url_api').'img/product/item/default.png';
    }
}
