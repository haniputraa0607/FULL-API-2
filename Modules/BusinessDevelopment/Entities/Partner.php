<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Disburse\Entities\BankAccount;

class Partner extends Model
{
    protected $primaryKey = 'id_partner';
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
        'first_update_password'
    ];
    public function partner_locations(){
        return $this->hasMany(Location::class, 'id_partner');
    }
    public function partner_bank_account(){
        return $this->belongsTo(BankAccount::class, 'id_bank_account');
    }
}
