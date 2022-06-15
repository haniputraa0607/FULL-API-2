<?php

namespace Modules\Recruitment\Http\Requests\Fixed_incentive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDetailDefault;
class CreateFixedIncentive extends FormRequest
{
    public function rules()
    {
        return [
            'id_hairstylist_group'                                   => 'required|unik',
            'id_hairstylist_group_default_fixed_incentive_detail'    => 'required|cek',
            'value'                                                  => 'required',
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
        $validator->addExtension('cek', function ($attribute, $value, $parameters, $validator) {
         $survey = HairstylistGroupFixedIncentiveDetailDefault::where(array('id_hairstylist_group_default_fixed_incentive_detail'=>$value))->first();
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
            'cek' => 'Detail fixed incentive tidak ada',
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
