<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistOverTime extends Model
{
    protected $table = 'hairstylist_overtime';
    protected $primaryKey = 'id_hairstylist_overtime';

	protected $dates = [
		'date'
	];

	protected $fillable = [
		'id_user_hair_stylist',
		'id_outlet',
		'approve_by',
		'request_by',
		'date',
		'time',
		'duration',
		'request_at',
		'approve_at',
		'reject_at',

	];

    public function hair_stylist(){
        return $this->belongsTo(UserHairStylist::class, 'id_user_hair_stylist');
    }

    public function outlet(){
        return $this->belongsTo(Outlet::class, 'id_user_hair_stylist');
    }

    public function approve(){
        return $this->belongsTo(User::class, 'id_user_hair_stylist');
    }
}
