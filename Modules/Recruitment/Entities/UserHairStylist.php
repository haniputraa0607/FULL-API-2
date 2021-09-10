<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class UserHairStylist extends Model
{
    protected $table = 'user_hair_stylist';
	protected $primaryKey = 'id_user_hair_stylist';

	protected $fillable = [
	    'id_outlet',
		'id_bank_account',
        'user_hair_stylist_status',
        'nickname',
        'email',
        'phone_number',
        'fullname',
        'password',
        'level',
        'gender',
        'nationality',
        'birthplace',
        'birthdate',
        'religion',
        'height',
        'weight',
        'recent_job',
        'recent_company',
        'blood_type',
        'recent_address',
        'postal_code',
        'marital_status',
        'email_verified',
        'first_update_password',
        'join_date',
        'approve_by'
	];
}
