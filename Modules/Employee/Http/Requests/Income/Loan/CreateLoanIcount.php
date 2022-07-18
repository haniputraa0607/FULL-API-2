<?php

namespace Modules\Employee\Http\Requests\Income\Loan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Transaction\Entities\SharingManagementFee;
use App\Http\Models\Setting;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Entities\EmployeeSalesPayment;
class CreateLoanIcount extends FormRequest
{
    public function rules()
    {
        return [
            'BusinessPartnerID'    => 'required|cek',
            'api_key'              => 'required|api_key',
            'signature'            => 'required|signature',
            'SalesInvoiceID'       => 'required|cek_sales',
            'amount'               => 'required|integer',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('cek_sales', function ($attribute, $value, $parameters, $validator) {
         $share = EmployeeSalesPayment::where(array('SalesInvoiceID'=>$value))->first();
         if($share){
             return false;
         }
         return true;
        });
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $share = Employee::where(array('id_business_partner'=>$value))->first();
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
              if(isset($request['BusinessPartnerID'])&&isset($request['SalesInvoiceID'])&&isset($request['amount'])){
              $enkrip = hash_hmac('sha256',$request['BusinessPartnerID'].$request['SalesInvoiceID'].$request['amount'],$api_secret->value??true);
              if($enkrip == $value){
                    return true; 
              }
           }return false;
        }); 
    }
    public function messages()
    {
        return [
            'cek' => 'Business Partner ID not found',
            'cek_sales' => 'Sales Invoice ID exist',
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