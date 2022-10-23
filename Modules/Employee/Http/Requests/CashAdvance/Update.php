<?php

namespace Modules\Employee\Http\Requests\CashAdvance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\EmployeeCashAdvance;
class Update extends FormRequest
{
      public function withValidator($validator)
    {
        $validator->addExtension('cash_advance', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$value,'status'=>"Pending"))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $post =  Auth::user();
            $outlet = Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$post["id_outlet"])->select('company_type')->first();
            if($outlet['company_type']??''=="PT IMA"){
                $company = 'ima';
            }else{
                $company = 'ims';
            }
          $survey = $data = ProductIcount::join('employee_cash_advance_product_icounts','employee_cash_advance_product_icounts.id_product_icount','product_icounts.id_product_icount')
               ->where([
           'is_buyable'=>'true',
           'is_sellable'=>'true',
           'is_deleted'=>'false',
           'is_suspended'=>'false',
           'is_actived'=>'true',
           'company_type'=>$company,
           'employee_cash_advance_product_icounts.id_product_icount'=>$value        
       ])->select([
           'product_icounts.id_product_icount',
           'product_icounts.name',
           'product_icounts.code'
       ])->first();
         if($survey){
             return true;
         } return false;
        }); 
    }
    public function messages()
    {
        return [
            'cash_advance' => 'Update failed, :attribute not found or status not Pending',
             'cek' => 'Product icount tidak ada ',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
                        'id_product_icount'		=> 'required|cek',
			'id_employee_cash_advance'      => 'required|cash_advance',
			'date_cash_advance'		=> 'date_format:"Y-m-d"',
			'attachment'                    => 'mimes:jpeg,jpg,bmp,png|max:5000',
        ];
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
