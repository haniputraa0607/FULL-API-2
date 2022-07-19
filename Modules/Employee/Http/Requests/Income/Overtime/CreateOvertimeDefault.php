<?php

namespace Modules\Employee\Http\Requests\Income\Overtime;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Role;
class CreateOvertimeDefault extends FormRequest
{
    public function rules()
    {
        return [
            'value'       => 'required|integer',
            'hours'       => 'required|integer|unique:employee_role_default_overtimes,hours',
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
