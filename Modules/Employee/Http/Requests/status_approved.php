<?php

namespace Modules\Employee\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Employee\Entities\Employee;
class status_approved extends FormRequest
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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
            return [
                    'phone'            => 'required|max:18',
                    'status_step'       => 'required',
                    'status_approved'  => 'required|status_approved',
                ];
    }
    public function withValidator($validator)
    {
        $validator->addExtension('status_approved', function ($attribute, $value, $parameters, $validator) {
         $data = $validator->getData();
         if($value != "Submitted"){
             return false;
         }
         $employee = Employee::join('users','users.id','employees.id_user')->where(array(
             'users.phone'=>$data['phone'],
             'employees.status'=>"Candidate",
             "employees.status_approved"=>null
         ))->first();
         if($employee){
             return true;
         }
         return false;
        }); 
    }
    public function messages()
    {
        return [
            'required' => ':attribute Empty',
            'status_approved' => 'Approved Failed',
        ];
    }
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json(['status' => 'fail', 'messages'  => $validator->errors()->all()], 200));
    }

    protected function validationData()
    {
        return $this->json()->all();
    }
}
