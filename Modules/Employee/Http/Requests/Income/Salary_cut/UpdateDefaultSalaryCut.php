<?php

namespace Modules\Employee\Http\Requests\Income\Salary_cut;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\EmployeeRoleSalaryCutDefault;
class UpdateDefaultSalaryCut extends FormRequest
{
    public function rules()
    {
        return [
            'id_employee_role_default_salary_cut'            => 'required',
            'code'                                          => 'required|unik',
            'name'                                          => 'required',
            'value'                                         => 'required',
            'formula'                                       => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
        $data = $validator->getData();
        $survey = EmployeeRoleSalaryCutDefault::where('id_employee_role_default_salary_cut','!=',$data['id_employee_role_default_salary_cut'])->where('code',$value)->first();
         if($survey){
             return false;
         }return true;
        }); 

    }
    public function messages()
    {
        return [
            'unik' => 'The :attribute has already been taken.',
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
