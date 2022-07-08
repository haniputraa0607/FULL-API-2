<?php

namespace Modules\Employee\Http\Requests\Income\Loan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
class CreateLoan extends FormRequest
{
    public function rules()
    {
        return [
            'id_user'                       => 'required',
            'id_employee_category_loan'     => 'required',
            'amount'                        => 'required',
            'installment'                   => 'required',
            'effective_date'                => 'required',
            'type'                          => 'required',
           ]; 
    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => 'Group Hairstylist tidak ada',
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
