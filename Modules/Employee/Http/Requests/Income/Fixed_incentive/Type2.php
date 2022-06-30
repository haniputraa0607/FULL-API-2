<?php

namespace Modules\Employee\Http\Requests\Income\Fixed_incentive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\EmployeeRoleFixedIncentiveDefaultDetail;
class Type2 extends FormRequest
{
    public function rules()
    {
        return [
            'id_employee_role_default_fixed_incentive'       => 'required|unik',
            'range'                                          => 'required',
            'value'                                          => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
        $data = $validator->getData();
        $survey = EmployeeRoleFixedIncentiveDefaultDetail::where(array('id_employee_role_default_fixed_incentive'=>$value,'range'=>$data['range']))->first();
         if(!$survey){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => 'Range sudah ada',
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
