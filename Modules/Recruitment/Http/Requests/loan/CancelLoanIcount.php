<?php

namespace Modules\Recruitment\Http\Requests\loan;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\HairstylistLoan;
use Modules\Recruitment\Entities\HairstylistLoanReturn;
use Modules\Recruitment\Entities\HairstylistSalesPayment;
use Illuminate\Support\Facades\DB;
class CancelLoanIcount extends FormRequest
{
    public function rules()
    {
        return [
            'BusinessPartnerID'    => 'required|cek',
            'SalesInvoiceID'       => 'required|exists:hairstylist_sales_payments,SalesInvoiceID|cek_payment',
            'type'                 => 'required|in:IMS,IMA',
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
           $data = $validator->getData();
           $sales = HairstylistSalesPayment::where([
                        'SalesInvoiceID'=>$value,
                        'hairstylist_sales_payments.type'=>$data['type']
                   ])
                   ->join('hairstylist_loans','hairstylist_loans.id_hairstylist_sales_payment','hairstylist_sales_payments.id_hairstylist_sales_payment')
                   ->join('hairstylist_loan_returns','hairstylist_loan_returns.id_hairstylist_loan','hairstylist_loans.id_hairstylist_loan')
                   ->select(DB::raw('
                                    sum(
                                   CASE WHEN
                                   hairstylist_loan_returns.status_return = "Success" THEN 1 ELSE 0
                                   END
                                    ) as count
                                '))
                   ->first();
           if($sales->count == 0){
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