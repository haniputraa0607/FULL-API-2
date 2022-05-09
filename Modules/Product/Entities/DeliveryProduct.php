<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Model;
use App\Http\Models\User;
use App\Http\Models\Outlet;
use Modules\Recruitment\Entities\UserHairStylist;

class DeliveryProduct extends Model
{
    protected $table = 'delivery_products';
	protected $primaryKey = "id_delivery_product";

	protected $fillable = [
        'code',
        'id_outlet',
        'type',
        'charged',
        'id_user_delivery',
        'id_user_accept',
        'status',
        'delivery_date',
        'confirmation_date',
        'confirmation_note',
        'from',
        'status'
	];

    public function delivery_product_detail(){
        return $this->hasMany(DeliveryProductDetail::class, 'id_delivery_product');
    }

    public function delivery_product_images(){
        return $this->hasMany(DeliveryProductImage::class, 'id_delivery_product');
    }

    public function delivery_product_user_delivery(){
        return $this->belongsTo(User::class, 'id_user_delivery', 'id');
    }

    public function delivery_product_user_accept(){
        return $this->belongsTo(UserHairStylist::class,  'id_user_accept', 'id_user_hair_stylist');
    }

    public function delivery_product_outlet(){
        return $this->belongsTo(Outlet::class, 'id_outlet');
    }
    
    public function delivery_request_products()
    {
        return $this->hasMany(DeliveryRequestProduct::class, 'id_delivery_product', 'id_delivery_product');
    }

    public function request(){
		return $this->belongsToMany(RequestProduct::class,'delivery_request_products','id_delivery_product','id_request_product');
	}

}
