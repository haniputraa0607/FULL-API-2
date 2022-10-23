<?php

namespace Modules\Employee\Http\Requests\CashAdvance\BE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CallbackIcountCashAdvance extends FormRequest
{
    public function rules()
    {
        return [
            'PurchaseDepositRequestID' => [
                'required',
                'string',
                Rule::exists('employee_cash_advances', 'id_purchase_deposit_request'),
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