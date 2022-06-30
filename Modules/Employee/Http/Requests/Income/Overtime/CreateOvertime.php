<?php

namespace Modules\Employee\Http\Requests\Income\Overtime;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Role;
class CreateOvertime extends FormRequest
{
    public function rules()
    {
        return [
            'id_role'                      => 'required|unik',
            'id_hairstylist_group_default_overtimes'    => 'required',
            ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $survey = Role::where(array('id_role'=>$value))->first();
         if($survey){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => 'Role tidak ada',
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
