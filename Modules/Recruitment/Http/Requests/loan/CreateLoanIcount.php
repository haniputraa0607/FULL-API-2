<?php

namespace Modules\Recruitment\Http\Requests\loan;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Recruitment\Entities\UserHairStylist;

class CreateLoanIcount extends FormRequest
{
    public function rules()
    {
        return [
            'BusinessPartnerID'    => 'required|cek',
            'SalesInvoiceID'       => 'required|hairstylist_sales_payments,SalesInvoiceID',
            'amount'               => 'required|numeric',
            'type'                 => 'required|in:IMS,IMA',
            'status'               => 'required|string|in:Success,Fail',
        ]; 
    }

    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
           $request = $validator->getData();
           if($request['type']=="IMA"){
               $share = UserHairStylist::where(array('id_business_partner_ima'=>$value))->first();   
           }else{
               $share = UserHairStylist::where(array('id_business_partner'=>$value))->first();
           }
           if($share){
               return true;
           }
           return false;
       });
    }

    public function messages()
    {
        return [
            'cek' => 'Business Partner ID not found',
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