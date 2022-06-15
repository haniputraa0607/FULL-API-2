<?php

namespace Modules\Recruitment\Http\Requests\Fixed_incentive;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupFixedIncentiveDetailDefault;
class Type2 extends FormRequest
{
    public function rules()
    {
        return [
            'id_hairstylist_group_default_fixed_incentive'       => 'required|unik',
            'range'                       => 'required',
            'value'                    => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
        $data = $validator->getData();
        $survey = HairstylistGroupFixedIncentiveDetailDefault::where(array('id_hairstylist_group_default_fixed_incentive'=>$value,'range'=>$data['range']))->first();
         if(!$survey){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => 'Range sudah ada',
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
