<?php

namespace Modules\Recruitment\Http\Requests\loan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
class CreateLoan extends FormRequest
{
    public function rules()
    {
        return [
            'id_user_hair_stylist'          => 'required',
            'id_hairstylist_category_loan'  => 'required',
            'amount'                        => 'required',
            'installment'                   => 'required',
            'effective_date'                => 'required',
            'type'                          => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $survey = HairstylistGroup::where(array('id_hairstylist_group'=>$value))->first();
         if($survey){
             return true;
         } return false;
        }); 

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
