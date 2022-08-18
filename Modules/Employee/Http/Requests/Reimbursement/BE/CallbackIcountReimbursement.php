<?php

namespace Modules\Employee\Http\Requests\Reimbursement\BE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallbackIcountReimbursement extends FormRequest
{
    public function rules()
    {
        return [
            'PurchaseInvoiceID' => [
                'required',
                'string',
                Rule::exists('employee_reimbursements', 'id_purchase_invoice'),
            ],
            'status'            => 'required|string|in:Success,Fail',
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