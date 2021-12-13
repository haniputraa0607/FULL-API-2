<?php

namespace Modules\Recruitment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Modules\Recruitment\Entities\HairstylistGroup;
use Modules\Recruitment\Entities\HairstylistGroupCommission;
class CreateGroupCommission extends FormRequest
{
    public function rules()
    {
        return [
            'id_hairstylist_group'        => 'required',
            'id_product'                  => 'required|unik',
            'commission_percent'          => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('unik', function ($attribute, $value, $parameters, $validator) {
         $request = $validator->getData();
         $survey = HairstylistGroupCommission::where(array('id_product'=>$value,'id_hairstylist_group'=>$request['id_hairstylist_group']))->count();
         if($survey == 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'unik' => 'Produk sudah ada ',
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
