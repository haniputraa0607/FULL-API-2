<?php

namespace Modules\Employee\Http\Requests\Reimbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\EmployeeReimbursement;
class Update extends FormRequest
{
      public function withValidator($validator)
    {
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
