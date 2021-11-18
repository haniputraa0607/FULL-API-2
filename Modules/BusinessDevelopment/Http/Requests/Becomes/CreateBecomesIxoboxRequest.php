<?php

namespace Modules\BusinessDevelopment\Http\Requests\Becomes;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;

use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\PartnersBecomesIxobox;

class CreateBecomesIxoboxRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'             => 'required',
            'close_date'        => 'required|close_date|today',
            'id_partner'        => 'required|partner',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('partner', function ($attribute, $value, $parameters, $validator) {
         $survey = PartnersBecomesIxobox::where(array('id_partner'=>$value,'status'=>"Process"))->orwhere(array('status'=>"Waiting"))->first();
         if($survey){
             return false;
         } return true;
        });
        $validator->addExtension('partner_status', function ($attribute, $value, $parameters, $validator) {
         $survey = Partner::where(array('id_partner'=>$value,'status'=>"Active"))->first();
         if($survey){
             return false; 
         } return true;
        }); 
        $validator->addExtension('close_date', function ($attribute, $value, $parameters, $validator) {
         $data = $validator->getData();
         $survey = Partner::where(array('id_partner'=>$data['id_partner'],'status'=>"Active"))->whereDate('end_date','>=',$value)->first();
         if($survey){
             return true; 
         } return false;
        }); 
        $validator->addExtension('today', function ($attribute, $value, $parameters, $validator) {
            $data = strtotime($value);
            $now = strtotime(date('Y-m-d'));
         if($data>=$now){
             return true; 
         } return false;
        }); 
    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'partner' => 'Partners sedang mengajukan pergantian status kerja sama',
            'close_date' => 'Close date melebihi kontrak',
            'today'=>"Minimal hari ini"
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
