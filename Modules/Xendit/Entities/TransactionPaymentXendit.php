<?php

namespace Modules\Xendit\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionPaymentXendit extends Model
{
    public $primaryKey  = 'id_transaction_payment_xendit';
    protected $fillable = [
        'id_transaction',
        'xendit_id',
        'external_id',
        'business_id',
        'phone',
        'type',
        'amount',
        'expiration_date',
        'failure_code',
        'status',
        'checkout_url',
    ];
    public $items = null;

    public static $prettyText = [
        'DANA' => 'DANA',
        'OVO' => 'OVO',
        'LINKAJA' => 'LinkAja',
    ];

    public function pay(&$errors = [])
    {
        if ($this->checkout_url || $this->business_id) {
            return true;
        }
        $xenditController = app('Modules\Xendit\Http\Controllers\XenditController');
        $create = $xenditController->create($this->type, $this->external_id, $this->amount, [
            'phone' => $this->phone,
            'items' => $this->items,
        ], $errors);
        if ($create) {
            $this->business_id = $create['business_id'] ?? null;
            $this->checkout_url = $create['checkout_url'] ?? null;
            $this->external_id = $create['external_id'] ?? $this->external_id;
            $this->status = $create['status'] ?? null;
            $result = true;
        } else {
            $result = false;
        }
        $save = $this->save();
        return $result;
    }
}