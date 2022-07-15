<?php

namespace Modules\Employee\Http\Requests\Income\Loan;

use Illuminate\Foundation\Http\FormRequest;

class CreateLoanIcount extends FormRequest
{
    public function rules()
    {
        return [
            'BusinessPartnerID'    => 'required|exists:employees,id_business_partner',
            'SalesInvoiceID'       => 'required|exists:employee_sales_payments,SalesInvoiceID',
            'amount'               => 'required|numeric',
            'status'               => 'required|string|in:Success,Fail',
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