<?php

namespace Modules\Employee\Http\Requests\Reimbursement\BE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Transaction\Entities\SharingManagementFee;
use App\Http\Models\Setting;
class CallbackIcountReimbursement extends FormRequest
{
    public function rules()
    {
        return [
            'PurchaseInvoiceID'    => 'required',
            'api_key'              => 'required|api_key',
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
        $validator->addExtension('api_key', function ($attribute, $value, $parameters, $validator) {
            $api_secret = Setting::where('key','api_key')->where('value',$value)->first();
           if($api_secret){
             return true; 
         } return false;
        }); 
        $validator->addExtension('status', function ($attribute, $value, $parameters, $validator) {
           if($value == 'Success'||$value=="Fail"){
             return true; 
         } return false;
        }); 
        $validator->addExtension('signature', function ($attribute, $value, $parameters, $validator) {
              $request = $validator->getData();
              $api_secret = Setting::where('key','api_secret')->first();
              if(isset($request['PurchaseInvoiceID'])&&isset($request['status'])&&isset($request['date_disburse'])){
              $enkrip = hash_hmac('sha256',$request['PurchaseInvoiceID'].$request['status'].$request['date_disburse'],$api_secret->value??true);
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
            'signature' => 'Signature doesnt match',
            'api_key' => 'Api Key Invalid'
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