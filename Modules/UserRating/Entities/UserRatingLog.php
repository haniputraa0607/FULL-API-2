<?php

namespace Modules\UserRating\Entities;

use Illuminate\Database\Eloquent\Model;

class UserRatingLog extends Model
{
	protected $primaryKey = 'id_user_rating_log';
    protected $fillable = ['id_user','id_transaction','id_outlet','id_user_hair_stylist','last_popup','refuse_count'];
}
