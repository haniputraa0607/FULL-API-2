<?php

namespace Modules\Recruitment\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Model;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;

class UserHairStylist extends Authenticatable
{
	use Notifiable, HasMultiAuthApiTokens;

	public function findForPassport($username) {
        return $this->where('phone_number', $username)->first();
	}

	public function getAuthPassword() {
		return $this->password;
	}

    protected $table = 'user_hair_stylist';
	protected $primaryKey = 'id_user_hair_stylist';

	protected $hidden = [
		'password'
	];

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
