<?php

namespace Modules\Transaction\Entities;

use App\Lib\MyHelper;
use Illuminate\Database\Eloquent\Model;

class TransactionAcademyInstallment extends Model
{
    protected $table = 'transaction_academy_installment';

    protected $primaryKey = 'id_transaction_academy_installment';

    protected $fillable   = [
        'id_transaction_academy',
        'installment_receipt_number',
        'installment_step',
        'percent',
        'amount',
        'deadline',
        'paid_status',
        'completed_installment_at',
        'void_date'
    ];


    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCompleted($data = [])
    {
        \DB::beginTransaction();

        $currentDate = date('Y-m-d H:i:s');
        $this->update([
            'paid_status' => 'Completed',
            'completed_installment_at' => $currentDate
        ]);

        $phone = TransactionAcademy::join('transactions', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                    ->join('users', 'users.id', 'transactions.id_user')->first()['phone']??null;

        app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
            'Payment Academy Installment Completed',
            $phone,
            [
                'completed_date'=> $currentDate,
                'installment_step' => MyHelper::numberToRomanRepresentation($this->installment_step),
                'total_amount'      => $this->amount
            ]
        );

        $transactionAcademy = TransactionAcademy::where('id_transaction_academy', $this->id_transaction_academy)->first();
        $transactionAcademy->triggerPaymentCompleted(['amount' => $this->amount]);
        \DB::commit();
        return true;
    }

    /**
     * Called when payment cancelled
     * @return [type] [description]
     */
    public function triggerPaymentCancelled($data = [])
    {
        $currentDate = date('Y-m-d H:i:s');
        $this->update([
            'paid_status' => 'Cancelled',
            'updated_at' => $currentDate
        ]);

        $phone = TransactionAcademy::join('transactions', 'transactions.id_transaction', 'transaction_academy.id_transaction')
                ->join('users', 'users.id', 'transactions.id_user')->first()['phone']??null;

        app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
            'Payment Academy Installment Cancelled',
            $phone,
            [
                'completed_date'=> $currentDate,
                'installment_step' => MyHelper::numberToRomanRepresentation($this->installment_step),
                'total_amount'      => $this->amount
            ]
        );

        return true;
    }
}
