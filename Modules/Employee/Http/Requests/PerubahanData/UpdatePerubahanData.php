<?php

namespace Modules\Employee\Http\Requests\PerubahanData;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Modules\Employee\Entities\EmployeePerubahanData;

class UpdatePerubahanData extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->addExtension('update', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeePerubahanData::where(array('id_employee_perubahan_data'=>$value,'status'=>"Pending"))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'update' => 'Update failed, :attribute not found or status data not pending',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
                        'id_employee_perubahan_data'      => 'required|update',
                        'status'                          => 'required|in:Success,Reject'
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
