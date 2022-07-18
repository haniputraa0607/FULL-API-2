<?php

namespace Modules\Recruitment\Http\Requests\loan;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistLoan;
use Modules\Recruitment\Entities\HairstylistLoanReturn;
use Modules\Recruitment\Entities\HairstylistSalesPayment;

class CancelLoanIcount extends FormRequest
{
    public function rules()
    {
        return [
            'BusinessPartnerID'    => 'required|cek',
            'SalesInvoiceID'       => 'required|exists:hairstylist_sales_payments,SalesInvoiceID|cek_payment'
        ]; 
    }

    public function withValidator($validator)
    {
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
           if($this->type == "IMA"){
               $share = UserHairStylist::where(array('id_business_partner_ima'=>$value))->first();   
           }else{
               $share = UserHairStylist::where(array('id_business_partner'=>$value))->first();
           }
           if($share){
               return true;
           }
           return false;
       });
        $validator->addExtension('cek_payment', function ($attribute, $value, $parameters, $validator) {
           
           $sales = HairstylistSalesPayment::where('SalesInvoiceID',$value)->first();
           $loan = HairstylistLoan::where('id_hairstylist_sales_payment',$sales->id_hairstylist_sales_payment)->first();
           if(!$loan){
               return false;
           }
           $return = HairstylistLoanReturn::where(array(
               'id_hairstylist_loan'=>$loan->id_hairstylist_loan,
               'status_return'  => "Success"
           ))->count();
           if($return == 0){
               return true;
           }
           return false;
       });
    }

    public function messages()
    {
        return [
            'cek' => 'Business Partner ID not found',
            'cek_payment' => 'Cancel loan failed, check this payment loan status',
            
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