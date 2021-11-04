<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionHomeService extends Model
{
    protected $table = 'transaction_home_services';

    protected $primaryKey = 'id_transaction_home_service';

    protected $fillable   = [
        'id_transaction',
        'id_user_address',
        'id_user_hair_stylist',
        'status',
        'schedule_date',
        'schedule_time',
        'preference_hair_stylist',
        'destination_name',
        'destination_phone',
        'destination_address',
        'destination_short_address',
        'destination_address_name',
        'destination_note',
        'destination_latitude',
        'destination_longitude'
    ];
}
