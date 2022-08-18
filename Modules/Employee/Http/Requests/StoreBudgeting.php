<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Users\Entities\Department;
use App\Http\Models\Setting;

class StoreBudgeting extends FormRequest
{
    public function rules()
    {
        return [
            'DepartmentID'         => 'required|string|exists:departments,id_department_icount',
            'balance'              => 'required|numeric'
        ]; 
    }

    public function authorize()
    {
        return true;
    }

    protected function validationData()
    {
        return $this->all();
    }
}
