<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class SharingManagementFeeTransaction extends \App\Http\Models\Template\TransactionService
{
	protected $primaryKey = 'id_sharing_management_fee_transaction';
        protected $table = 'sharing_management_fee_transaction';


	protected $fillable = [
		'id_sharing_management_fee',
                'id_transaction',
                'status',
		'created_at',
		'updated_at',
	];
}
