<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionHomeServiceHairStylistFinding extends Model
{
    protected $table = 'transaction_home_service_hairstylist_finding';

    protected $primaryKey = 'id_transaction_home_service_hairstylist_finding';

    protected $fillable   = [
        'id_transaction',
        'id_user_hair_stylist',
        'status'
    ];
}
