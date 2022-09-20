<?php

namespace Modules\Recruitment\Http\Requests\holiday;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;

class CreateHoliday extends FormRequest
{
    public function rules()
    {
        return [
            'holiday_name'        => 'required',
            'holiday_date'        => 'required'
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $survey = HairstylistGroup::where(array('hair_stylist_group_code'=>$value))->count();
         if($survey == 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unique' => ':attribute tidak boleh duplikat',
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
