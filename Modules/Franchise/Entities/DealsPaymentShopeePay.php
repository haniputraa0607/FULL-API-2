<?php

namespace Modules\Franchise\Entities;

use Illuminate\Database\Eloquent\Model;

class DealsPaymentShopeePay extends Model
{
    protected $connection = 'mysql3';
    public $primaryKey  = 'id_deals_payment_shopee_pay';
    protected $fillable = [
        'id_deals',
        'id_deals_user',
        'order_id',
    	'request_id',
    	'payment_reference_id',
    	'merchant_ext_id',
    	'store_ext_id',
    	'amount',
    	'currency',
    	'return_url',
    	'point_of_initiation',
    	'validity_period',
    	'additional_info',
    	'transaction_sn',
    	'payment_status',
    	'user_id_hash',
    	'terminal_id',
        'redirect_url_app',
        'redirect_url_http',
        'refund_reference_id',
        'void_reference_id',
        'manual_refund'
    ];
}
