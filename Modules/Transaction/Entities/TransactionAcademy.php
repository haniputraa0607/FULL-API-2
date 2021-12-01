<?php

namespace Modules\Transaction\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionAcademy extends \App\Http\Models\Template\TransactionService
{
    protected $table = 'transaction_academy';

    protected $primaryKey = 'id_transaction_academy';

    protected $fillable   = [
        'id_transaction',
        'payment_method',
        'total_installment',
        'amount_completed',
        'amount_not_completed',
        'transaction_academy_duration',
        'transaction_academy_total_meeting',
        'transaction_academy_hours_meeting'
    ];

    public function user_schedule(){
        return $this->hasMany(TransactionAcademySchedule::class, 'id_transaction_academy', 'id_transaction_academy');
    }

    public function triggerPaymentCompleted($data = [])
    {
        $this->update([
            'amount_completed' => $this->amount_completed + $data['amount'], 
            'amount_not_completed' => $this->amount_not_completed - $data['amount']
        ]);
    }
}
