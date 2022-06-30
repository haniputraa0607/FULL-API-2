<?php

namespace Modules\Employee\Http\Requests\Income\Incentive; 

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Role;
class CreateIncentiveDefault extends FormRequest
{
    public function rules()
    {
        return [
            'name'        => 'required',
            'code'        => 'required|unique:employee_role_default_incentives,code',
            'value'       => 'required',
            'formula'     => 'required',
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
