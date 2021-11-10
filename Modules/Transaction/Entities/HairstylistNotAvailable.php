<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistNotAvailable extends Model
{
    protected $table = 'hairstylist_not_available';

    protected $primaryKey = 'id_hairstylist_not_available';

    protected $fillable   = [
        'id_outlet',
        'id_user_hair_stylist',
        'id_transaction',
        'id_transaction_product_service',
        'booking_date',
        'booking_time'
    ];

}
