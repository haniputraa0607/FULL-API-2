<?php

namespace App\Http\Models\Template;

use Illuminate\Database\Eloquent\Model;

class TransactionService extends Model
{
    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCompleted()
    {
        return true;
    }

    /**
     * Called when payment completed
     * @return [type] [description]
     */
    public function triggerPaymentCancelled()
    {
        return true;
    }
}
