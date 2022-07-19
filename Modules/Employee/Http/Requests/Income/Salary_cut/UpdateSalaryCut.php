<?php

namespace Modules\Employee\Http\Requests\Income\SalaryCut;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\EmployeeRoleSalaryCut;
class UpdateSalaryCut extends FormRequest
{
    public function rules()
    {
        return [
            'id_employee_role_salary_cut'  => 'required|unik',
            'value'                       => 'required',
            'formula'                     => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeeRoleSalaryCut::where(array('id_employee_role_salary_cut'=>$value))->first();
         if($survey){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => 'Insentif tidak ada',
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
