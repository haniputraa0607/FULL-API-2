<?php

namespace Modules\Product\Entities;

use App\Http\Models\Outlet;
use App\Http\Models\User;
use Illuminate\Database\Eloquent\Model;

class RequestProduct extends Model
{
    protected $table = 'request_products';
	protected $primaryKey = "id_request_product";

	protected $fillable = [
        'code',
        'id_outlet',
        'type',
        'requirement_date',
        'id_user_request',
        'note_request',
        'id_user_approve',
        'note_approve',
        'status',
        'from',
        'id_product_catalog',
        'id_purchase_request'
	];

    public function request_product_detail(){
        return $this->hasMany(RequestProductDetail::class, 'id_request_product');
    }

    public function request_product_user_request(){
        return $this->belongsTo(User::class, 'id_user_request', 'id');
    }

    public function request_product_user_approve(){
        return $this->belongsTo(User::class,  'id_user_approve', 'id');
    }

    public function request_product_outlet(){
        return $this->belongsTo(Outlet::class, 'id_outlet');
    }
}
