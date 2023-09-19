<?php

namespace Modules\Employee\Http\Requests\Reimbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\EmployeeReimbursement;
use Modules\Product\Entities\ProductIcount;
use App\Http\Models\Outlet;
use Illuminate\Support\Facades\Auth;
class Update extends FormRequest
{
      public function withValidator($validator)
    {
         $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $post =  Auth::user();
            $outlet = Outlet::leftjoin('locations','locations.id_location','outlets.id_location')->where('id_outlet',$post["id_outlet"])->select('company_type')->first();
            if($outlet['company_type']??''=="PT IMA"){
                $company = 'ima';
            }else{
                $company = 'ims';
            }
          $survey = $data = ProductIcount::join('employee_reimbursement_product_icounts','employee_reimbursement_product_icounts.id_product_icount','product_icounts.id_product_icount')
               ->where([
           'is_buyable'=>'true',
           'is_sellable'=>'true',
           'is_deleted'=>'false',
           'is_suspended'=>'false',
           'is_actived'=>'true',
           'company_type'=>$company
       ])->select([
           'product_icounts.id_product_icount',
           'product_icounts.name',
           'product_icounts.code'
       ])->first();
         if($survey){
             return true;
         } return false;
        }); 
        $validator->addExtension('reimbursement', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$value,'status'=>"Pending"))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'reimbursement' => 'Update failed, :attribute not found or status not Pending',
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
			'id_employee_reimbursement'     => 'required|reimbursement',
			'date_reimbursement'		=> 'date_format:"Y-m-d"',
			'attachment'                    => 'mimes:jpeg,jpg,bmp,png|max:5000',
                        'id_product_icount'		=> 'required|cek',
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
