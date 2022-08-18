<?php

namespace Modules\Employee\Http\Requests\Reimbursement\BE;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\EmployeeReimbursement;
class Approved extends FormRequest
{
      public function withValidator($validator)
    {
        $validator->addExtension('reimbursement', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeeReimbursement::where(array('id_employee_reimbursement'=>$value,'status'=>"Fat Dept Approved"))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'reimbursement' => 'Update failed, :attribute not found or Finance Department not approved',
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
			'approve_notes'                 => 'required',
			'status'                        => 'required|in:Approved,Rejected'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'fail', 'messages'  => $validator->errors()->all()], 200));
    }

    protected function validationData()
    {
        return $this->json()->all();
    }
}
