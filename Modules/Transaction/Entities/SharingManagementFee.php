<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class SharingManagementFee extends \App\Http\Models\Template\TransactionService
{
	protected $primaryKey = 'id_sharing_management_fee';
        protected $table = 'sharing_management_fee';


	protected $fillable = [
		'id_partner',
                'type',
		'start_date',
		'end_date',
		'total_transaksi',
		'total_beban',
		'tax',
		'percent',
		'sharing',
		'disc',
		'transfer',
		'data',
		'id_transaction',
		'created_at',
		'updated_at',
	];
}
