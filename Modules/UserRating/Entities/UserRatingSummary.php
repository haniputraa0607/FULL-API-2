<?php

namespace Modules\UserRating\Entities;

use Illuminate\Database\Eloquent\Model;

class UserRatingSummary extends Model
{
    protected $primaryKey = 'id_user_rating_summary';
    protected $fillable = ['id_outlet','id_user_hair_stylist','summary_type','key','value'];
    
    public function user_hair_stylist()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
	}
}
