<?php

namespace Modules\Users\Entities;

use Illuminate\Database\Eloquent\Model;

class OldMember extends Model
{
    protected $table = 'old_member';

    protected $primaryKey = 'id_old_member';

    protected $fillable = [
        'id',
        'customer_group',
        'first_name',
        'last_name',
        'email',
        'sex',
        'birthday',
        'phone',
        'address1',
        'address2',
        'zip',
        'city',
        'state',
        'country',
        'loyalty_point',
        'claim_status'
    ];
}
