<?php

namespace Modules\Transaction\Entities;

use App\Jobs\FindingHairStylistHomeService;
use Illuminate\Database\Eloquent\Model;

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
}
