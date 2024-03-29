<?php

namespace Modules\Recruitment\Http\Requests\Fixed_incentive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupInsentifDefault;
use Modules\Recruitment\Entities\HairstylistGroupInsentif;
class UpdateDefault extends FormRequest
{
    public function rules()
    {
        return [
            'id_hairstylist_group_default_fixed_incentive'  => 'required',
            'name_fixed_incentive'       => 'required',
            'status'                     => 'required|in:incentive,salary_cut',
            'type'                       => 'required',
            'formula'                    => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
        $data = $validator->getData();
        $survey = HairstylistGroupInsentifDefault::where('id_hairstylist_group_default_insentifs','!=',$data['id_hairstylist_group_default_insentifs'])->where('code',$value)->first();
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
