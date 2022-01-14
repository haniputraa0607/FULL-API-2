<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionProductPromo extends Model
{
    protected $table = 'transaction_product_promos';

    protected $primaryKey = 'id_transaction_product_promo';

    protected $casts = [
		'total_discount' => 'int',
		'base_discount' => 'int',
		'qty_discount' => 'int'
	];

    protected $fillable   = [
        'id_transaction_product',
        'id_deals',
        'id_promo_campaign',
        'promo_type',
        'total_discount',
        'base_discount',
        'qty_discount'
    ];

	public function transaction_product()
	{
		return $this->belongsTo(\App\Http\Models\TransactionProduct::class, 'id_transaction_product');
	}
}
