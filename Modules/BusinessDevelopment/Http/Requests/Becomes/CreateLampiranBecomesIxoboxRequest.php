<?php

namespace Modules\BusinessDevelopment\Http\Requests\becomes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxobox;

class CreateLampiranBecomesIxoboxRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'             => 'required',
            'id_partners_becomes_ixobox'        => 'required|partner',
            'attachment'        => 'required'
           ]; 
    }
    public function withValidator($validator)
    {
         $validator->addExtension('partner', function ($attribute, $value, $parameters, $validator) {
         $survey = PartnersBecomesIxobox::where(array('id_partners_becomes_ixobox'=>$value,'status'=>"Process"))->first();
         if($survey){
             return true;
         } return false;
        }); 
    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'partner' => 'Status pergantian status tidak process',
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
