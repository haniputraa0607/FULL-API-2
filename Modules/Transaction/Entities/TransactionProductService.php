<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionProductService extends Model
{
    protected $table = 'transaction_product_services';

    protected $primaryKey = 'id_transaction_product_service';

    protected $fillable   = [
        'id_transaction',
        'id_transaction_product',
        'id_user_hair_stylist',
        'order_id',
        'schedule_date',
        'schedule_time',
        'service_status',
        'completed_at',
        'flag_update_schedule',
        'id_outlet_box',
        'is_conflict',
        'queue',
        'queue_code'
    ];

    public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

	public function user_hair_stylist()
	{
		return $this->belongsTo(\Modules\Recruitment\Entities\UserHairStylist::class, 'id_user_hair_stylist');
	}

	public function transaction_product()
	{
		return $this->belongsTo(\App\Http\Models\TransactionProduct::class, 'id_transaction_product');
	}

	public function hairstylist_not_available()
	{
		return $this->hasOne(\Modules\Transaction\Entities\HairstylistNotAvailable::class, 'id_transaction_product_service');
	}

}
