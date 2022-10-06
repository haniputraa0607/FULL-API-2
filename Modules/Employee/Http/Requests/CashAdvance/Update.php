<?php

namespace Modules\Employee\Http\Requests\CashAdvance;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\EmployeeCashAdvance;
class Update extends FormRequest
{
      public function withValidator($validator)
    {
        $validator->addExtension('cash_advance', function ($attribute, $value, $parameters, $validator) {
         $survey = EmployeeCashAdvance::where(array('id_employee_cash_advance'=>$value,'status'=>"Pending"))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'cash_advance' => 'Update failed, :attribute not found or status not Pending',
        ];
    }
    public function authorize()
    {
        return true;
    }
    public function rules()
	{
		return [
			'id_employee_cash_advance'      => 'required|cash_advance',
			'date_cash_advance'		=> 'date_format:"Y-m-d"',
			'attachment'                    => 'mimes:jpeg,jpg,bmp,png|max:5000',
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
