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
		'id_business_partner',
		'id_business_partner_ima',
		'id_company',
		'id_cluster',
		'code',
		'name',
		'title',
		'contact_person',
		'gender',
		'group',
		'phone',
		'mobile',
		'email',
		'address',
		'ownership_status',
		'cooperation_scheme',
		'id_bank_account',
		'npwp',
		'npwp_name',
		'npwp_address',
		'status_steps',
		'status',
		'is_suspended',
		'is_tax',
		'price_level',
		'start_date',
		'end_date',
		'id_term_payment',
		'id_account_payable',
		'id_account_receivable',
		'id_sales_disc',
		'id_purchase_disc',
		'id_tax_in',
		'id_tax_out',
		'id_salesman',
		'id_sales_order',
		'id_sales_order_detail',
		'id_sales_invoice',
		'id_sales_invoice_detail',
		'id_delivery_order_detail',
		'notes',
		'is_deleted',
		'id_sales_deposit',
		'voucher_no',
		'password',
		'first_update_password',
		'sharing_percent',
		'sharing_value',
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
    public function partner_legal_agreement(){
        return $this->hasMany(LegalAgreement::class, 'id_partner');
    }
    public function partner_survey(){
        return $this->hasMany(FormSurvey::class, 'id_partner');
    }
    public function partner_close_temporary(){
        return $this->hasMany(PartnersCloseTemporary::class, 'id_partner');
    }

    public function first_location()
    {
    	return $this->hasOne(Location::class, 'id_partner');
    }

		public function partner_new_step(){
			return $this->hasMany(NewStepsLog::class, 'id_partner');
		}
}
