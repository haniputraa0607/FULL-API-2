<?php

namespace Modules\Employee\Http\Requests\Income\Fixed_incentive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class UpdateDefault extends FormRequest
{
    public function rules()
    {
        return [
            'id_employee_role_default_fixed_incentive'  => 'required',
            'name_fixed_incentive'       => 'required',
            'status'                     => 'required|in:incentive,salary_cut',
            'type'                       => 'required',
            'formula'                    => 'required',
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
