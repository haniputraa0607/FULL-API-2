<?php

namespace Modules\UserRating\Entities;

use Illuminate\Database\Eloquent\Model;

class UserRating extends Model
{
    protected $primaryKey = 'id_user_rating';
    protected $fillable = ['id_user','id_transaction','id_transaction_product_service','id_outlet','id_user_hair_stylist','option_question','rating_value','suggestion','option_value'];
    public function transaction() {
    	return $this->belongsTo(\App\Http\Models\Transaction::class,'id_transaction','id_transaction');
    }
    public function user()
    {
        return $this->belongsTo(\App\Http\Models\User::class,'id_user','id');
    }
    
    public function user_hair_stylist()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
	}
}
