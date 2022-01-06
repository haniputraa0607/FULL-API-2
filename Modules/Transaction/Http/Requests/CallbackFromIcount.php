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
            'status'               => 'required|status',
            'date_disburse'        => 'required|date_format:Y-m-d H:i:s',
            'signature'            => 'required|signature'
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
        $validator->addExtension('signature', function ($attribute, $value, $parameters, $validator) {
              $request = $validator->getData();
              if(isset($request['PurchaseInvoiceID'])&&isset($request['status'])&&isset($request['date_disburse'])){
              $enkrip = hash_hmac('sha256',$request['PurchaseInvoiceID'].$request['status'].$request['date_disburse'],true);
              if($enkrip == $value){
                    return true; 
              }
           }return false;
        }); 
    }
    public function messages()
    {
        return [
            'cek' => 'Invalid PurchaseInvoiceID or PurchaseInvoiceID status has been processed',
            'status' => "Invalid status, status must be Success or Fail",
            'signature' => 'Signature doesnt match'
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