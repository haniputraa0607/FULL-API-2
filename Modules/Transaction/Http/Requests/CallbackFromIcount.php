<?php

namespace Modules\Transaction\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Transaction\Entities\SharingManagementFee;

class CallbackFromIcount extends FormRequest
{
    public function rules()
    {
        return [
            'PurchaseInvoiceID'    => 'required|cek',
            'status'                => 'required|status'
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $share = SharingManagementFee::where(array('PurchaseInvoiceID'=>$value,'status'=>'Proccess'))->first();
         if($share){
             return true;
         }
         return false;
        });
        $validator->addExtension('status', function ($attribute, $value, $parameters, $validator) {
           if($value == 'Success'||$value=="Fail"){
             return true; 
         } return false;
        }); 
    }
    public function messages()
    {
        return [
            'cek' => 'Invalid PurchaseInvoiceID or PurchaseInvoiceID status already in proccess',
            'status' => "Invalid status, status must be Success or Fail",
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
