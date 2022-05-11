<?php

namespace Modules\Employee\Http\Requests\Reimbursement;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class history extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
    public function rules(){
    return [
            'month'=> 'required|date_format:"Y-m"'
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        return $this->month = null;
    }

    protected function validationData()
    {
     return $this->json()->all();
    }
}
