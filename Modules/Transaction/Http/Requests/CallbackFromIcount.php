<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallbackFromIcount extends FormRequest
{
    public function rules()
    {
        return [
            'status'            => 'required|string|in:Success,Fail',
            'PurchaseInvoiceID' => 'required|string|exists:sharing_management_fee,PurchaseInvoiceID',
            'date_disburse'     => 'required|date_format:Y-m-d H:i:s',
        ];
    }

    public function authorize()
    {
        return true;
    }

    protected function validationData()
    {
        return $this->all();
    }
}
