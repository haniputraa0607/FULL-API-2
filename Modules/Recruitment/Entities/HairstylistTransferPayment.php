<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:19 +0000.
 */

namespace Modules\Recruitment\Entities;

use Illuminate\Database\Eloquent\Model;

class HairstylistTransferPayment extends Model
{
    protected $table = 'hairstylist_transfer_payments';
	protected $primaryKey = 'id_hairstylist_transfer_payment';

	protected $fillable = [
	    'id_hairstylist_log_balance',
		'id_user_hair_stylist',
		'id_outlet',
        'transfer_payment_code',
		'transfer_payment_status',
		'total_amount',
		'confirm_at',
		'confirm_by'
	];
}
