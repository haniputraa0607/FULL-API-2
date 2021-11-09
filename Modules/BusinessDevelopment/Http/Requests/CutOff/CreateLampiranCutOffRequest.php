<?php

namespace Modules\BusinessDevelopment\Http\Requests\CutOff;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;
use Modules\BusinessDevelopment\Entities\OutletCutOff;

class CreateLampiranCutOffRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'             => 'required',
            'id_outlet_cut_off'        => 'required|outlet',
            'attachment'        => 'required'
           ]; 
    }
    public function withValidator($validator)
    {
         $validator->addExtension('outlet', function ($attribute, $value, $parameters, $validator) {
         $survey = OutletCutOff::where(array('id_outlet_cut_off'=>$value,'status'=>"Process"))->orwhere(array('status'=>"Waiting"))->first();
         if($survey){
             return true;
         } return false;
        }); 
    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'outlet' => 'Cek data status',
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
