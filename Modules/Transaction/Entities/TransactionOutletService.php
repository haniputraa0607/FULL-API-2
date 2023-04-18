<?php

namespace Modules\Transaction\Entities;

use App\Http\Models\Product;
use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\HairstylistNotAvailable;
use Modules\Transaction\Entities\TransactionProductService;
use App\Lib\MyHelper;

class TransactionOutletService extends \App\Http\Models\Template\TransactionService
{
    protected $table = 'transaction_outlet_services';

    protected $primaryKey = 'id_transaction_outlet_services';

    protected $fillable   = [
        'id_transaction',
        'customer_name',
        'customer_email',
        'customer_domicile',
        'customer_birtdate',
        'customer_gender',
        'pickup_by',
        'pickup_at',
        'completed_at',
        'reject_at',
        'reject_reason',
        'need_manual_void'
    ];

    public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}

    public function triggerPaymentCancelled($data = [])
    {
        //remove hs from table not available
        $idTrxProductService = TransactionProductService::where('id_transaction', $this->id_transaction)->pluck('id_transaction_product_service')->toArray();
        if(!empty($idTrxProductService)){
            HairstylistNotAvailable::whereIn('id_transaction_product_service', $idTrxProductService)->delete();
        }
    }

    public function triggerRejectOutletService($data = [])
    {
    	$trxProducts = TransactionProduct::where('id_transaction', $this->id_transaction)->with('transaction_product_service.user_hair_stylist')->get();
    	$trx = Transaction::with('outlet')->find($this->id_transaction);
    	$sentSpv = false;
    	foreach ($trxProducts as $trxProduct) {
    		$alreadyRejected = $trxProduct['reject_at'] ? 1 : 0;
    		$trxProduct->update([
				'reject_at' => date('Y-m-d H:i:s'),
				'reject_reason' => $data['reject_reason'] ?? null
			]);

    		if ($alreadyRejected) {
    			continue;
    		}

	    	if (isset($trxProduct['transaction_product_service']['id_transaction_product_service'])) {
				HairstylistNotAvailable::where('id_transaction_product_service', $trxProduct['transaction_product_service']['id_transaction_product_service'])->delete();

				if (isset($trxProduct['transaction_product_service']['user_hair_stylist']['phone_number'])) {
		    		$phoneHs = $trxProduct['transaction_product_service']['user_hair_stylist']['phone_number'];

		    		app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
	                    'Mitra HS - Transaction Service Rejected',
	                    $phoneHs,
	                    [
	                    	'date' => $trx['transaction_date'],
	                    	'outlet_name' => $trx['outlet']['outlet_name'],
	                    	'detail' => $detail ?? null,
	                    	'receipt_number' => $trx['transaction_receipt_number'],
	                    	'order_id' => $trxProduct['transaction_product_service']['order_id']
	                    ], null, false, false, 'hairstylist'
	                );
				}
			} else {
				app('Modules\Transaction\Http\Controllers\ApiTransactionOutletService')->returnProductStock($trxProduct->id_transaction_product);

				if (!$sentSpv) {
					$phoneSpv = UserHairStylist::where('id_outlet', $trx['id_outlet'])
								->where('level', 'Supervisor')
								->where('user_hair_stylist_status', 'Active')
								->first()['phone_number'];

					app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
	                    'Mitra SPV - Transaction Product Rejected',
	                    $phoneSpv,
	                    [
	                        'date' => $trx['transaction_date'],
	                    	'outlet_name' => $trx['outlet']['outlet_name'],
	                    	'detail' => $detail ?? null,
	                    	'receipt_number' => $trx['transaction_receipt_number']
	                    ], null, false, false, 'hairstylist'
	                );

					$sentSpv = true;
				}
			}
    	}

    	return true;
    }

    public function triggerPaymentCompleted($data = [])
    {
        $trxProducts = TransactionProduct::where('id_transaction', $this->id_transaction)->with('transaction_product_service.user_hair_stylist')->get();
        $trx = Transaction::with('outlet')->find($this->id_transaction);
		$sentSpv = false;
    	foreach ($trxProducts as $tp) {
	    	if (isset($tp['transaction_product_service']['user_hair_stylist']['phone_number'])) {
	    		$phoneHs = $tp['transaction_product_service']['user_hair_stylist']['phone_number'];

	    		app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                    'Mitra HS - Transaction Service Created',
                    $phoneHs,
                    [
                    	'date' => $trx['transaction_date'],
                    	'outlet_name' => $trx['outlet']['outlet_name'],
                    	'detail' => $detail ?? null,
                    	'receipt_number' => $trx['transaction_receipt_number'],
                    	'order_id' => $tp['transaction_product_service']['order_id'],
                        'service_name' => Product::where('id_product', $tp['id_product'])->first()['product_name']??''
                    ], null, false, false, 'hairstylist'
                );
			} elseif (!$sentSpv) {
				$phoneSpv = UserHairStylist::where('id_outlet', $trx['id_outlet'])
							->where('level', 'Supervisor')
							->where('user_hair_stylist_status', 'Active')
							->first()['phone_number'] ?? null;
				
				if($phoneSpv){
					app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
						'Mitra SPV - Transaction Product Created',
						$phoneSpv,
						[
							'date' => $trx['transaction_date'],
							'outlet_name' => $trx['outlet']['outlet_name'],
							'detail' => $detail ?? null,
							'receipt_number' => $trx['transaction_receipt_number']
						], null, false, false, 'hairstylist'
					);
	
					$sentSpv = true;
				}
			}
    	}

    	return true;
    }
}
