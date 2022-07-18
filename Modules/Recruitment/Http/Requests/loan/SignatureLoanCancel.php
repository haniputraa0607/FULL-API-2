<?php

namespace Modules\Recruitment\Http\Requests\loan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Transaction\Entities\SharingManagementFee;
use App\Http\Models\Setting;
class SignatureLoanCancel extends FormRequest
{
    public function rules()
    {
        return [
            'BusinessPartnerID'    => 'required',
            'SalesInvoiceID'       => 'required',
           ]; 
    }
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'fail', 'messages'  => $validator->errors()->all()], 200));
    }

    protected function validationData()
    {
        return $this->all();
    }
}