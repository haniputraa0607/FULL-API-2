<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupOvertimeDefault;
use Modules\Recruitment\Entities\HairstylistGroupOvertime;
class UpdateDefaultOvertime extends FormRequest
{
    public function rules()
    {
        return [
            'id_hairstylist_group_default_overtimes'        => 'required',
            'days'                                          => 'required|integer|unik',
            'value'                                         => 'required|integer',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
        $data = $validator->getData();
        $survey = HairstylistGroupOvertimeDefault::where('id_hairstylist_group_default_overtimes','!=',$data['id_hairstylist_group_default_overtimes'])->where('days',$value)->first();
         if($survey){
             return false;
         }return true;
        }); 

    }
    public function messages()
    {
        return [
            'unik' => 'The :attribute has already been taken.',
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
