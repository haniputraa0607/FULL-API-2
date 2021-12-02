<?php

/**
 * Created by Reliese Model.
 * Date: Tue, 19 Nov 2019 09:30:16 +0700.
 */

namespace Modules\Transaction\Entities;

use Reliese\Database\Eloquent\Model as Eloquent;

/**
 * Class SubscriptionPaymentMidtran
 *
 * @property string $masked_card
 * @property string $approval_code
 * @property string $bank
 * @property string $eci
 * @property string $transaction_time
 * @property string $gross_amount
 * @property string $order_id
 * @property string $payment_type
 * @property string $signature_key
 * @property string $status_code
 * @property string $vt_transaction_id
 * @property string $transaction_status
 * @property string $fraud_status
 * @property string $status_message
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 */
class TransactionAcademyInstallmentPaymentMidtrans extends Eloquent
{
	protected $primaryKey = 'id_transaction_academy_installment_payment_midtrans';

	protected $fillable = [
		'id_transaction_academy',
		'id_transaction_academy_installment',
		'masked_card',
		'approval_code',
		'bank',
		'eci',
		'transaction_time',
		'gross_amount',
		'order_id',
		'payment_type',
		'signature_key',
		'status_code',
		'vt_transaction_id',
		'transaction_status',
		'fraud_status',
		'status_message'
	];
}
