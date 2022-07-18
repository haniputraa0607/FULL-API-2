<?php

namespace Modules\Employee\Http\Requests\InputFile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Modules\Employee\Entities\EmployeeFile;

class UpdateFile extends FormRequest
{
       public function withValidator($validator)
    {
        $validator->addExtension('update', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeeFile::where(array('id_employee_file'=>$value,'id_user'=>Auth::user()->id))->count();
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
                        'id_employee_file'      => 'required|update',
			'attachment'             => 'max:5000|min:0',
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
