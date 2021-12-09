<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionShop extends \App\Http\Models\Template\TransactionService
{
	protected $primaryKey = 'id_transaction_shop';

	protected $casts = [
		'id_transaction' => 'int',
		'id_admin_taken' => 'int',
		'id_admin_ready' => 'int'
	];

	protected $dates = [
		'received_at',
		'ready_at',
		'delivery_at',
		'arrived_at',
		'completed_at',
		'rejected_at'
	];

	protected $fillable = [
		'id_transaction',
		'id_admin_taken',
		'id_admin_ready',
		'shop_status',
		'delivery_method',
		'delivery_name',
		'shop_status',
		'received_at',
		'ready_at',
		'delivery_at',
		'arrived_at',
		'completed_at',
		'rejected_at',
		'reject_reason',
		'destination_name',
		'destination_phone',
		'destination_address',
		'destination_short_address',
		'destination_address_name',
		'destination_note',
		'destination_latitude',
		'destination_longitude'
	];

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

	public function admin_receive() 
	{
		return $this->belongsTo(User::class, 'id_admin_receive', 'id');
	}

	public function admin_taken() 
	{
		return $this->belongsTo(User::class, 'id_admin_taken', 'id');
	}
}
