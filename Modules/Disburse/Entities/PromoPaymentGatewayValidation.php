<?php

namespace Modules\Disburse\Entities;

use Illuminate\Database\Eloquent\Model;

class PromoPaymentGatewayValidation extends Model
{
    protected $table = 'promo_payment_gateway_validation';
	protected $primaryKey = 'id_promo_payment_gateway_validation';

	protected $fillable = [
	    'id_user',
	    'id_rule_promo_payment_gateway',
        'start_date_periode',
        'end_date_periode',
        'correct_get_promo',
        'not_get_promo',
        'must_get_promo',
        'wrong_cashback',
        'file'
	];
}