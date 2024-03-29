<?php

/**
 * Created by Reliese Model.
 * Date: Thu, 10 May 2018 04:28:18 +0000.
 */

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;
use App\Lib\MyHelper;
use App\Jobs\FraudJob;
use App\Jobs\QueueService;
use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Entities\UserPromo;
use Modules\Transaction\Entities\TransactionAcademy;
use Modules\Xendit\Entities\TransactionPaymentXendit;
use Modules\PromoCampaign\Entities\TransactionPromo;

/**
 * Class Transaction
 * 
 * @property int $id_transaction
 * @property int $id_user
 * @property string $transaction_receipt_number
 * @property string $transaction_notes
 * @property int $transaction_subtotal
 * @property int $transaction_shipment
 * @property int $transaction_service
 * @property int $transaction_discount
 * @property int $transaction_tax
 * @property int $transaction_grandtotal
 * @property int $transaction_point_earned
 * @property int $transaction_cashback_earned
 * @property string $transaction_payment_status
 * @property \Carbon\Carbon $void_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * 
 * @property \App\Http\Models\User $user
 * @property \Illuminate\Database\Eloquent\Collection $transaction_payment_manuals
 * @property \Illuminate\Database\Eloquent\Collection $transaction_payment_midtrans
 * @property \Illuminate\Database\Eloquent\Collection $transaction_payment_offlines
 * @property \Illuminate\Database\Eloquent\Collection $products
 * @property \Illuminate\Database\Eloquent\Collection $transaction_shipments
 *
 * @package App\Models
 */
class Transaction extends Model
{
	protected $primaryKey = 'id_transaction';

	protected $casts = [
		'id_user' => 'int',
		// 'transaction_subtotal' => 'int',
		'transaction_shipment' => 'int',
		// 'transaction_service' => 'int',
		'transaction_discount' => 'int',
		// 'transaction_tax' => 'int',
		'transaction_grandtotal' => 'int',
		'transaction_point_earned' => 'int',
		'transaction_cashback_earned' => 'int'
	];

	protected $dates = [
		'void_date'
	];

	protected $fillable = [
		'id_user',
		'id_outlet',
		'id_promo_campaign_promo_code',
		'id_subscription_user_voucher',
		'transaction_receipt_number',
		'transaction_notes',
		'transaction_subtotal',
        'transaction_gross',
		'transaction_shipment',
		'transaction_shipment_go_send',
		'transaction_is_free',
		'transaction_service',
		'transaction_discount',
        'transaction_discount_item',
        'transaction_discount_bill',
		'transaction_tax',
		'trasaction_type',
        'transaction_from',
		'transaction_cashier',
		'sales_type',
		'transaction_device_type',
		'transaction_grandtotal',
        'mdr',
		'transaction_point_earned',
		'transaction_cashback_earned',
		'transaction_payment_status',
		'trasaction_payment_type',
		'void_date',
		'transaction_date',
		'completed_at',
		'special_memberships',
		'membership_level',
		'id_deals_voucher',
		'latitude',
		'longitude',
        'distance_customer',
		'membership_promo_id',
        'transaction_flag_invalid',
        'image_invalid_flag',
        'fraud_flag',
		'cashback_insert_status',
		'calculate_achievement',
		'show_rate_popup',
		'transaction_discount_delivery',
		'transaction_discount_item',
		'transaction_discount_bill',
		'need_manual_void',
		'failed_void_reason',
		'shipment_method',
		'shipment_courier',
        'scope',
        'reject_at',
        'reject_type',
        'reject_reason',
        'refund_requirement',
        'customer_name',
	];

	public $manual_refund = 0;
	public $payment_method = null;
	public $payment_detail = null;
	public $payment_reference_number = null;

	public function user()
	{
		return $this->belongsTo(\App\Http\Models\User::class, 'id_user');
	}

	public function outlet()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet');
	}
	
	public function outlet_name()
	{
		return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet')->select('id_outlet', 'outlet_name');
	}

	public function transaction_payment_manuals()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentManual::class, 'id_transaction');
	}

	public function transaction_payment_midtrans()
	{
		return $this->hasOne(\App\Http\Models\TransactionPaymentMidtran::class, 'id_transaction');
	}

	public function transaction_payment_xendit()
	{
		return $this->hasOne(TransactionPaymentXendit::class, 'id_transaction');
	}

	public function transaction_payment_offlines()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentOffline::class, 'id_transaction');
	}
	public function transaction_payment_ovo()
	{
		return $this->hasMany(\App\Http\Models\TransactionPaymentOvo::class, 'id_transaction');
	}

	public function transaction_payment_ipay88()
	{
		return $this->hasOne(\Modules\IPay88\Entities\TransactionPaymentIpay88::class, 'id_transaction');
	}

	public function transaction_payment_shopee_pay()
	{
		return $this->hasOne(\Modules\ShopeePay\Entities\TransactionPaymentShopeePay::class, 'id_transaction');
	}

	public function transaction_payment_subscription()
	{
		return $this->hasOne(\Modules\Subscription\Entities\TransactionPaymentSubscription::class, 'id_transaction');
	}

	public function products()
	{
		return $this->belongsToMany(\App\Http\Models\Product::class, 'transaction_products', 'id_transaction', 'id_product')
					->select('product_categories.*','products.*')
					->leftJoin('product_categories', 'product_categories.id_product_category', '=', 'products.id_product_category')
					->withPivot('id_transaction_product', 'transaction_product_qty', 'transaction_product_price', 'transaction_product_price_base', 'transaction_product_price_tax', 'transaction_product_subtotal', 'transaction_modifier_subtotal', 'transaction_product_discount', 'transaction_product_note')
					->withTimestamps();
	}

	public function transaction_shipments()
	{
		return $this->belongsTo(\App\Http\Models\TransactionShipment::class, 'id_transaction', 'id_transaction');
	}

    public function productTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
            ->where('type', 'Product')
            ->whereNull('id_bundling_product')
            ->orderBy('transaction_products.id_product');
	}

	public function productServiceTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
            ->where('type', 'Service')
            ->whereNull('id_bundling_product')
            ->orderBy('transaction_products.id_product');
	}

    public function allProductTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
            ->where('type', 'Product')
            ->orderBy('id_product');
	}

    public function productTransactionBundling()
    {
        return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
            ->where('type', 'Product')
            ->whereNotNull('id_bundling_product')
            ->orderBy('id_product');
    }

	public function plasticTransaction() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')->where('type', 'Plastic')->orderBy('id_product');
	}

    public function product_detail()
    {
    	if ($this->trasaction_type == 'Delivery') {
    		return $this->belongsTo(TransactionShipment::class, 'id_transaction', 'id_transaction');
    	} else {
    		return $this->belongsTo(TransactionPickup::class, 'id_transaction', 'id_transaction');
    	}
	}
	
    public function transaction_pickup()
    {
		return $this->belongsTo(TransactionPickup::class, 'id_transaction', 'id_transaction');
    }

    public function transaction_pickup_go_send()
    {
    	// make sure you have joined transaction_pickups before using this
		return $this->belongsTo(TransactionPickupGoSend::class, 'id_transaction_pickup', 'id_transaction_pickup');
    }

    public function transaction_pickup_wehelpyou()
    {
    	// make sure you have joined transaction_pickups before using this
		return $this->belongsTo(TransactionPickupWehelpyou::class, 'id_transaction_pickup', 'id_transaction_pickup');
    }

    public function logTopup() 
    {
    	return $this->belongsTo(LogTopup::class, 'id_transaction', 'transaction_reference');
	}
	
	public function vouchers()
	{
		return $this->belongsToMany(\App\Http\Models\DealsVoucher::class, 'transaction_vouchers', 'id_transaction', 'id_deals_voucher');
	}

	public function transaction_vouchers()
	{
		return $this->hasMany(\App\Http\Models\TransactionVoucher::class, 'id_transaction', 'id_transaction');
	}

	public function promo_campaign_promo_code()
	{
		return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignPromoCode::class, 'id_promo_campaign_promo_code', 'id_promo_campaign_promo_code');
	}

	public function pickup_gosend_update()
	{
		return $this->hasMany(\App\Http\Models\TransactionPickupGoSendUpdate::class, 'id_transaction', 'id_transaction')->orderBy('created_at','desc');
	}
    public function transaction_multiple_payment()
    {
        return $this->hasMany(\App\Http\Models\TransactionMultiplePayment::class, 'id_transaction');
    }

    public function promo_campaign()
    {
        return $this->belongsTo(\Modules\PromoCampaign\Entities\PromoCampaignPromoCode::class, 'id_promo_campaign_promo_code', 'id_promo_campaign_promo_code')
            ->join('promo_campaigns', 'promo_campaigns.id_promo_campaign', 'promo_campaign_promo_codes.id_promo_campaign');
    }

    public function point_refund(){
        return $this->belongsTo(LogBalance::class, 'id_transaction', 'id_reference')
            ->where('source', 'like', 'Rejected%');
    }

    public function point_use(){
        return $this->belongsTo(LogBalance::class, 'id_transaction', 'id_reference')
            ->where('balance', '<', 0)
            ->whereIn('source', ['Online Transaction', 'Transaction']);
    }

    public function disburse_outlet_transaction(){
        return $this->hasOne(\Modules\Disburse\Entities\DisburseOutletTransaction::class, 'id_transaction');
    }

    public function subscription_user_voucher()
	{
		return $this->belongsTo(\Modules\Subscription\Entities\SubscriptionUserVoucher::class, 'id_subscription_user_voucher');
	}

    public function outlet_city()
    {
        return $this->belongsTo(\App\Http\Models\Outlet::class, 'id_outlet')
            ->join('cities','cities.id_city','outlets.id_city');
    }

    public function transaction_outlet_service()
	{
		return $this->hasOne(\Modules\Transaction\Entities\TransactionOutletService::class, 'id_transaction');
	}

    public function transaction_home_service()
	{
		return $this->hasOne(\Modules\Transaction\Entities\TransactionHomeService::class, 'id_transaction');
	}

    public function transaction_shop()
	{
		return $this->hasOne(\Modules\Transaction\Entities\TransactionShop::class, 'id_transaction');
	}

	public function transaction_academy(){
        return $this->hasOne(\Modules\Transaction\Entities\TransactionAcademy::class, 'id_transaction');
    }

	public function user_feedbacks()
    {
        return $this->hasMany(\Modules\UserFeedback\Entities\UserFeedback::class,'id_transaction','id_transaction');
    }

    public function transaction_product_services()
	{
		return $this->hasMany(\Modules\Transaction\Entities\TransactionProductService::class, 'id_transaction');
	}

	public function transaction_products() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction');
	}

	public function transaction_products_product_type() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
			->with(['product'])
			->where('type', 'Product');
	}

	public function transaction_products_service_type() 
    {
    	return $this->hasMany(TransactionProduct::class, 'id_transaction', 'id_transaction')
			->with(['transaction_product_service'])
			->where('type', 'Service');
	}

	public function hairstylist_not_available()
	{
		return $this->hasMany(\Modules\Transaction\Entities\HairstylistNotAvailable::class, 'id_transaction');
	}

	public function transaction_home_service_hairstylist_finding()
	{
		return $this->hasMany(\Modules\Transaction\Entities\TransactionHomeServiceHairStylistFinding::class, 'id_transaction');
	}

	public function transaction_promos() 
    {
    	return $this->hasMany(\Modules\PromoCampaign\Entities\TransactionPromo::class, 'id_transaction', 'id_transaction');
	}

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCompleted($data = [])
    {
    	// check complete allowed
    	if ($this->transaction_payment_status != 'Pending') {
    		return $this->transaction_payment_status == 'Completed';
    	}
    	\DB::beginTransaction();
    	// update transaction status
    	if ($this->transaction_from != 'academy') {
	    	$this->update([
	    		'transaction_payment_status' => 'Completed', 
	    		'completed_at' => date('Y-m-d H:i:s')
	    	]);
    	}

    	// trigger payment complete -> service
    	switch ($this->transaction_from) {
    		case 'outlet-service':
    			$this->transaction_outlet_service->triggerPaymentCompleted($data);
    			break;

    		case 'home-service':
    			$this->transaction_home_service->triggerPaymentCompleted($data);
    			break;

    		case 'shop':
    			$this->transaction_shop->triggerPaymentCompleted($data);
    			break;

    		case 'academy':
    			$this->transaction_academy->triggerPaymentCompleted($data);
    			$academyNotCompleted = TransactionAcademy::where('id_transaction', $this->id_transaction)->first()['amount_not_completed']??null;
    			if (!is_null($academyNotCompleted) && $academyNotCompleted == 0) {
			    	$this->update([
			    		'transaction_payment_status' => 'Completed', 
			    		'completed_at' => date('Y-m-d H:i:s')
			    	]);
    			}
    			break;
    	}

    	// check fraud
    	if ($this->user) {
	    	$this->user->update([
	            'count_transaction_day' => $this->user->count_transaction_day + 1,
	            'count_transaction_week' => $this->user->count_transaction_week + 1,
	    	]);

	    	$config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->value('is_active');

	        if($config_fraud_use_queue == 1){
	            FraudJob::dispatch($this->user, $this, 'transaction')->onConnection('fraudqueue');
	        }else {
	            $checkFraud = app('\Modules\SettingFraud\Http\Controllers\ApiFraud')->checkFraudTrxOnline($this->user, $this);
	        }
    	}

    	// send notification
        $trx = clone $this;
        $mid = [
            'order_id'     => $trx->transaction_receipt_number,
            'gross_amount' => $trx->transaction_multiple_payment->where('type', '<>', 'Balance')->sum(),
        ];
        $trx->load('outlet');
        $trx->load('productTransaction');

        $trx->productTransaction->each(function($transaction_product,$index) use($trx){
			$transaction_product->breakdown();
        });

		$trx->productServiceTransaction->each(function($transaction_product_service,$index) use($trx){
			$transaction_product_service->breakdown();
        });

		app('\Modules\Transaction\Http\Controllers\ApiNotification')->notification($mid, $trx);

        \DB::commit();

		if(isset($trx->transaction_product_services)){
			$trx->transaction_product_services->each(function($service,$index) use($trx){
				$product = $service->transaction_product->product;
				$send = [
					'trx' => $trx,
					'service' => $service,
					'product' => $product,
				];
				$refresh = QueueService::dispatch($send)->onConnection('queueservicequeue');

				// $queue = \Modules\Transaction\Entities\TransactionProductService::join('transactions','transactions.id_transaction','transaction_product_services.id_transaction')->whereDate('schedule_date', date('Y-m-d',strtotime($service->schedule_date)))->where('id_outlet',$trx->id_outlet)->where('transaction_product_services.id_transaction', '<>', $trx->id_transaction)->max('queue') + 1;
				// if($queue<10){
				// 	$queue_code = '[00'.$queue.'] - '.$product->product_name;
				// }elseif($queue<100){
				// 	$queue_code = '[0'.$queue.'] - '.$product->product_name;
				// }else{
				// 	$queue_code = '['.$queue.'] - '.$product->product_name;
				// }
				// $service->update(['queue'=>$queue,'queue_code'=>$queue_code]);
			});
		}

        
    	return true;
    }

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCompletedFromCancelled($data = [])
    {
        \DB::beginTransaction();
        // check complete allowed
        if ($this->transaction_payment_status == 'Pending' || $this->transaction_payment_status == 'Completed') {
            return false;
        }

        $this->update([
            'transaction_payment_status' => 'Completed',
            'completed_at' => date('Y-m-d H:i:s'),
            'void_date' => null
        ]);

        // trigger payment complete -> service
        switch ($this->transaction_from) {
            case 'outlet-service':
                $this->transaction_outlet_service->triggerPaymentCompleted($data);
                break;

            case 'home-service':
                $this->transaction_home_service->triggerPaymentCompleted($data);
                break;

            case 'shop':
                $this->transaction_shop->triggerPaymentCompleted($data);
                break;

            case 'academy':
                $this->transaction_academy->triggerPaymentCompleted($data);
                if ($this->transaction_academy->amount_not_completed == 0) {
                    $this->update([
                        'transaction_payment_status' => 'Completed',
                        'completed_at' => date('Y-m-d H:i:s')
                    ]);
                }
                break;
        }

        // check fraud
        if ($this->user) {
            $this->user->update([
                'count_transaction_day' => $this->user->count_transaction_day + 1,
                'count_transaction_week' => $this->user->count_transaction_week + 1,
            ]);

            $config_fraud_use_queue = Configs::where('config_name', 'fraud use queue')->value('is_active');

            if($config_fraud_use_queue == 1){
                FraudJob::dispatch($this->user, $this, 'transaction')->onConnection('fraudqueue');
            }else {
                $checkFraud = app('\Modules\SettingFraud\Http\Controllers\ApiFraud')->checkFraudTrxOnline($this->user, $this);
            }
        }

        $trx = clone $this;
        $checkPromo = TransactionPromo::where('id_transaction', $trx['id_transaction'])->get()->toArray();

        foreach ($checkPromo as $val){
            if(!empty($val['id_deals_user'])){
                $idDeals = DealsUser::join('deals_vouchers', 'deals_vouchers.id_deals_voucher', 'deals_users.id_deals_voucher')
                            ->where('id_deals_user', $val['id_deals_user'])->select('deals_vouchers.id_deals')->first()['id_deals']??null;
                app('\Modules\Transaction\Http\Controllers\ApiPromoTransaction')->insertUsedVoucher($trx, [
                   'id_deals_user' => $val['id_deals_user'],
                   'id_deals' => $idDeals
                ]);
            }elseif(!empty($val['id_promo_campaign_promo_code'])){
                $user = User::where('id', $trx['id_user'])->first();
                $idPromoCampaignCode = PromoCampaignPromoCode::where('id_promo_campaign_promo_code', $val['id_promo_campaign_promo_code'])->first()['id_promo_campaign']??null;
                app('\Modules\Transaction\Http\Controllers\ApiPromoTransaction')->insertUsedCode($trx, [
                    'id_promo_campaign' => $idPromoCampaignCode,
                    'id_promo_campaign_promo_code' => $val['id_promo_campaign_promo_code'],
                    'id_user' => $user['id'],
                    'user_name' => $user['name'],
                    'user_phone' => $user['phone']
                ], 1);
            }
        }

        if($trx['transaction_from'] == 'outlet-service' || $trx['transaction_from'] == 'shop'){
            app('\Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->bookHS($trx['id_transaction']);
            app('\Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->bookProductStock($trx['id_transaction']);
        }

        // send notification
        $mid = [
            'order_id'     => $trx->transaction_receipt_number,
            'gross_amount' => $trx->transaction_multiple_payment->where('type', '<>', 'Balance')->sum(),
        ];
        $trx->load('outlet');
        $trx->load('productTransaction');

        $trx->productTransaction->each(function($transaction_product,$index){
            $transaction_product->breakdown();
        });

		$trx->productServiceTransaction->each(function($transaction_product_service,$index) use($trx){
			$transaction_product_service->breakdown();
        });

		app('\Modules\Transaction\Http\Controllers\ApiNotification')->notification($mid, $trx);

        \DB::commit();
		
		if(isset($trx->transaction_product_services)){
			$trx->transaction_product_services->each(function($service,$index) use($trx){
				$product = $service->transaction_product->product;
				$send = [
					'trx' => $trx,
					'service' => $service,
					'product' => $product,
				];
				$refresh = QueueService::dispatch($send)->onConnection('queueservicequeue');
			});
		}

        
        return true;
    }

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCancelled($data = [])
    {
    	// check complete allowed
    	if ($this->transaction_payment_status != 'Pending' && $this->trasaction_payment_type != "Cash") {
    		return $this->transaction_payment_status == 'Completed';
    	}
    	\DB::beginTransaction();

    	// update transaction payment cancelled
    	$this->update([
    		'transaction_payment_status' => 'Cancelled', 
    		'void_date' => date('Y-m-d H:i:s')
    	]);
		MyHelper::updateFlagTransactionOnline($this, 'cancel', $this->user);

        //reversal balance
        $logBalance = LogBalance::where('id_reference', $this->id_transaction)->whereIn('source', ['Online Transaction', 'Transaction'])->where('balance', '<', 0)->get();
        foreach($logBalance as $logB){
            $reversal = app('\Modules\Balance\Http\Controllers\BalanceController')->addLogBalance( $this->id_user, abs($logB['balance']), $this->id_transaction, 'Reversal', $this->transaction_grandtotal);
            if (!$reversal) {
            	\DB::rollBack();
            	return false;
            }
            $user = User::where('id', $this->id_user)->first();
            $send = app('\Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM('Transaction Failed Point Refund', $this->user->phone,
                [
                    "outlet_name"       => $this->outlet_name->outlet_name,
                    "transaction_date"  => $this->transaction_date,
                    'id_transaction'    => $this->id_transaction,
                    'receipt_number'    => $this->transaction_receipt_number,
                    'received_point'    => (string) abs($logB['balance']),
                    'order_id'          => $this->order_id,
                ]
            );
        }

        // restore promo status
        if ($this->id_promo_campaign_promo_code) {
	        // delete promo campaign report
        	$update_promo_report = app('\Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign')->deleteReport($this->id_transaction, $this->id_promo_campaign_promo_code);
        	if (!$update_promo_report) {
            	\DB::rollBack();
            	return false;
            }	
        }

        // return voucher
        $update_voucher = app('\Modules\Deals\Http\Controllers\ApiDealsVoucher')->returnVoucher($this->id_transaction);

        // return subscription
        $update_subscription = app('\Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher')->returnSubscription($this->id_transaction);

    	// trigger payment cancelled -> service
    	switch ($this->transaction_from) {
    		case 'outlet-service':
    			$this->transaction_outlet_service->triggerPaymentCancelled($data);
    			break;

    		case 'home-service':
    			$this->transaction_home_service->triggerPaymentCancelled($data);
    			break;

    		case 'shop':
    			$this->transaction_shop->triggerPaymentCancelled($data);
    			break;

    		case 'academy':
    			$this->transaction_academy->triggerPaymentCancelled($data);
    			break;
    	}

        if($this->transaction_from == 'outlet-service' || $this->transaction_from == 'shop') {
            app('\Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->cancelBookProductStock($this->id_transaction);
        }

    	// send notification
    	// TODO write notification logic here
    	app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
        	'Transaction Expired', 
        	$this->user->phone, 
        	[
	            'date' => $this->transaction_date,
            	'outlet_name' => $this->outlet['outlet_name'],
            	'detail' => $detail ?? null,
            	'receipt_number' => $this->transaction_receipt_number
	        ]
	    );

    	\DB::commit();
    	return true;
    }

    public function triggerReject($data = [])
    {

    	if ($this->reject_at) {
    		return true;
    	}
    	\DB::beginTransaction();

    	$this->update([
    		'reject_at' => date('Y-m-d H:i:s'),
    		'reject_reason' => $data['reject_reason'] ?? null
    	]);

    	$refundPayment = app('\Modules\Transaction\Http\Controllers\ApiTransactionRefund')->refundPayment($this);
    	if (empty($refundPayment['status']) || $refundPayment['status'] != 'success') {
        	\DB::rollBack();
        	return false;
        }	

    	// restore promo status
        if ($this->id_promo_campaign_promo_code) {
	        // delete promo campaign report
        	$update_promo_report = app('\Modules\PromoCampaign\Http\Controllers\ApiPromoCampaign')->deleteReport($this->id_transaction, $this->id_promo_campaign_promo_code);
        	if (!$update_promo_report) {
            	\DB::rollBack();
            	return false;
            }	
        }

        // return voucher
        $update_voucher = app('\Modules\Deals\Http\Controllers\ApiDealsVoucher')->returnVoucher($this->id_transaction);

        // return subscription
        $update_subscription = app('\Modules\Subscription\Http\Controllers\ApiSubscriptionVoucher')->returnSubscription($this->id_transaction);

        // trigger reject -> service
    	switch ($this->transaction_from) {
    		case 'outlet-service':
    			$this->transaction_outlet_service->triggerRejectOutletService($data);
    			break;

    		case 'home-service':
    			$this->transaction_home_service->triggerRejectHomeService($data);
    			break;

    		case 'shop':
    			break;

    		case 'academy':
    			break;
    	}

        if($this->transaction_from == 'outlet-service' || $this->transaction_from == 'shop') {
            app('\Modules\Transaction\Http\Controllers\ApiOnlineTransaction')->cancelBookProductStock($this->id_transaction);
        }

    	// send notification
    	// TODO write notification logic here
    	app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
        	'Transaction Rejected', 
        	$this->user->phone, 
        	[
	            'date' => $this->transaction_date,
            	'outlet_name' => $this->outlet['outlet_name'],
            	'detail' => $detail ?? null,
            	'receipt_number' => $this->transaction_receipt_number
	        ]
	    );

    	\DB::commit();
    	return true;
    }

    public function getOrderIdAttribute()
    {
    	return $this->transaction_receipt_number;
    }

    public function recalculateTaxandMDR()
    {
    	$tax_percent = $this->outlet->is_tax ?: 0;
    	$payment_type = $this->transaction_multiple_payment()->where('type', '<>', 'Balance')->get()->pluck('type')->first();

    	$payment_detail = null;
    	switch ($payment_type) {
    		case 'Midtrans':
    			$payment = $this->transaction_payment_midtrans()->first();
    			$payment_detail = optional($payment)->payment_type;
    			break;
    		case 'Xendit':
    			$payment = $this->transaction_payment_xendit()->first();
    			$payment_detail = optional($payment)->type;
    			break;
    	}

        $products = TransactionProduct::where('id_transaction', $this->id_transaction)->get()->toArray();

        //update mdr
        if($payment_type && $payment_detail){
            $code = strtolower($payment_type.'_'.$payment_detail);
            $settingmdr = Setting::where('key', 'mdr_formula')->first()['value_text']??'';
            $settingmdr = (array)json_decode($settingmdr);
            $formula = $settingmdr[$code]??'';
            if(!empty($formula)){
                try {
                    $mdr = MyHelper::calculator($formula, ['transaction_grandtotal' => $this->transaction_grandtotal]);
                    if(!empty($mdr)){
                        $this->update(['mdr' => $mdr]);
                        $count = count($products);
                        $lastmdr = $mdr;
                        // $sum = array_sum(array_column($products, 'transaction_product_subtotal'));
                        $sum = $this->transaction_grandtotal;
                        foreach ($products as $key=>$product){
                            $index = $key+1;
				            $price_plus_tax = $product['transaction_product_price'] - ($product['transaction_product_discount_all'] / $product['transaction_product_qty']);
                            if($count == $index){
                                $mdrProduct = $lastmdr;
                            }else{
                                $mdrProduct = (($price_plus_tax * $product['transaction_product_qty']) * $mdr) / $sum;
                                $lastmdr = $lastmdr - $mdrProduct;
                            }
                            TransactionProduct::where('id_transaction_product', $product['id_transaction_product'])->update(['mdr_product' => $mdrProduct]);
                        }
                    }
                } catch (\Exception $e) {
                }
            }
        }

    	// update tax
    	$tax = round(($this->transaction_grandtotal * $tax_percent / (100 + $tax_percent)), 2);
        foreach ($products as $key => $product) {
            $price_plus_tax = $product['transaction_product_price'] - ($product['transaction_product_discount_all'] / $product['transaction_product_qty']);
        	$tax_product = round(($price_plus_tax * $tax_percent / (100 + $tax_percent)), 2);
        	$base_product = $product['transaction_product_price'] - $tax_product;
            TransactionProduct::where('id_transaction_product', $product['id_transaction_product'])->update([
				'transaction_product_price_base' => $base_product,
				'transaction_product_price_tax' => $tax_product,
            ]);
        }
        $this->update(['transaction_tax' => $tax]);
    }
}
