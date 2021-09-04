<?php

namespace Modules\BusinessDevelopment\Entities;

use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    protected $primaryKey = 'id_department';
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
}
