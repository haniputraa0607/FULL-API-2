<?php

namespace Modules\Employee\Http\Requests\Income\Overtime;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\EmployeeRoleOvertimeDefault; 

class UpdateDefaultOvertime extends FormRequest
{
    public function rules()
    {
        return [
            'id_employee_role_default_overtime'        => 'required',
            'hours'                                          => 'required|integer|unik',
            'value'                                         => 'required|integer',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
        $data = $validator->getData();
        $survey = EmployeeRoleOvertimeDefault::where('id_employee_role_default_overtime','!=',$data['id_employee_role_default_overtime'])->where('hours',$value)->first();
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
