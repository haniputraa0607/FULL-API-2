<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionHomeServiceHairStylistReject extends Model
{
    protected $table = 'transaction_home_service_hairstylist_reject';

    protected $primaryKey = 'id_transaction_home_service_hairstylist_reject';

    protected $fillable   = [
        'id_transaction',
        'id_user_hair_stylist'
    ];
}
