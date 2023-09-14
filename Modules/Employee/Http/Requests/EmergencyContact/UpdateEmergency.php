<?php

namespace Modules\Employee\Http\Requests\EmergencyContact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Modules\Employee\Entities\EmployeeEmergencyContact;

class UpdateEmergency extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->addExtension('update', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeeEmergencyContact::where(array('id_employee_emergency_contact'=>$value))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'update' => 'Update failed, :attribute not found ',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
                        'id_employee_emergency_contact'      => 'required|update'
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
