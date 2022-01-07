<?php

namespace Modules\Transaction\Entities;

use App\Jobs\FindingHairStylistHomeService;
use Illuminate\Database\Eloquent\Model;
use App\Http\Models\Transaction;
use App\Http\Models\TransactionProduct;
use App\Lib\MyHelper;

class TransactionHomeService extends \App\Http\Models\Template\TransactionService
{
    protected $table = 'transaction_home_services';

    protected $primaryKey = 'id_transaction_home_service';

    protected $fillable   = [
        'id_transaction',
        'id_user_address',
        'id_user_hair_stylist',
        'status',
        'schedule_date',
        'schedule_set_time',
        'schedule_time',
        'preference_hair_stylist',
        'destination_name',
        'destination_phone',
        'destination_address',
        'destination_id_subdistrict',
        'destination_short_address',
        'destination_address_name',
        'destination_note',
        'destination_latitude',
        'destination_longitude',
        'counter_finding_hair_stylist'
    ];

    public function triggerPaymentCompleted($data = []){
        FindingHairStylistHomeService::dispatch(['id_transaction' => $this->id_transaction, 'id_transaction_home_service' => $this->id_transaction_home_service])->allOnConnection('findinghairstylistqueue');
    }
	
	public function triggerRejectHomeService($data = [])
    {
    	$updateStatus = TransactionHomeServiceStatusUpdate::create([
            'id_transaction' => $this->id_transaction,
            'status' => 'Cancelled'
        ]);

        if ($updateStatus) {
        	$trx = Transaction::with('user')->find($this->id_transaction);
            app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                'Home Service Update Status',
                $trx['user']['phone'],
                [
                    'id_transaction' => $trx['id_transaction'],
                    'status'=> $updateStatus['status'] ?? ' ',
                    'receipt_number' => $trx['transaction_receipt_number']
                ]
            );

            $this->update([
                'id_user_hair_stylist' => null,
                'status' => 'Cancelled'
            ]);

            TransactionHomeServiceHairStylistFinding::where('id_transaction', $this->id_transaction)->delete();
            app("Modules\Transaction\Http\Controllers\ApiOnlineTransaction")->cancelBookHS($this->id_transaction);
            app("Modules\Transaction\Http\Controllers\ApiOnlineTransaction")->cancelBookProductStock($this->id_transaction);

            // reject product
            $trxProducts = TransactionProduct::where('id_transaction', $this->id_transaction)->get();
	    	foreach ($trxProducts as $trxProduct) {
	    		$alreadyRejected = $trxProduct['reject_at'] ? 1 : 0;
	    		$trxProduct->update([
					'reject_at' => date('Y-m-d H:i:s'),
					'reject_reason' => $data['reject_reason'] ?? null
				]);
	    	}
        }

        return $updateStatus;
    }    

    public function subdistrict()
	{
		return $this->belongsTo(\App\Http\Models\Subdistrict::class, 'id_subdistrict');
	}

	public function transaction()
	{
		return $this->belongsTo(\App\Http\Models\Transaction::class, 'id_transaction');
	}
}
