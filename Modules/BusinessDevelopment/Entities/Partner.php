<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Hash;
use Modules\Disburse\Entities\BankAccount;
use Illuminate\Database\Eloquent\Model;

class Partner extends Authenticatable
{
	use Notifiable, HasMultiAuthApiTokens;

	public function findForPassport($username) {
		if(substr($username, 0, 2) == '62'){
			$username = substr($username,2);
		}elseif(substr($username, 0, 3) == '+62'){
			$username = substr($username,3);
		}

		if(substr($username, 0, 1) != '0'){
			$username = '0'.$username;
		}

        return $this->where('phone', $username)->first();
    }
	
	public function getAuthPassword() {
		return $this->password;
	}
        
        protected $table = 'partners';
	protected $primaryKey = "id_partner";
	protected $hidden = ['password'];

	protected $fillable = [
		'name',
		'phone',
		'email',
		'address',
		'ownership_status',
		'cooperation_scheme',
		'id_bank_account',
		'status',
		'start_date',
		'end_date',
		'password',
		'first_update_password',
	];
        
    public function partner_locations(){
        return $this->hasMany(Location::class, 'id_partner');
    }
    public function partner_bank_account(){
        return $this->belongsTo(BankAccount::class, 'id_bank_account');
    }
    public function partner_step(){
        return $this->hasMany(StepsLog::class, 'id_partner');
    }
    public function partner_confirmation(){
        return $this->hasMany(ConfirmationLetter::class, 'id_partner');
    }
    public function partner_survey(){
        return $this->hasMany(FormSurvey::class, 'id_partner');
    }
}
