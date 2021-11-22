<?php

namespace Modules\BusinessDevelopment\Http\Requests\OutletCLose;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\OutletCutOff;
use Modules\BusinessDevelopment\Entities\OutletChangeOwnership;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporary;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;

class CreateOutletActiveRequest extends FormRequest
{
    public function rules()
    {
        return [
            'id_partner'        => 'required',
            'id_outlet'         => 'required|outlet',
            'date'              => 'required|today',
            'title'             => 'required',
            'jenis_active'             => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('outlet', function ($attribute, $value, $parameters, $validator) {
         $outlet = Outlet::where(array('id_outlet'=>$value,'outlet_status'=>'Inactive'))->first();
         $survey = OutletChangeOwnership::where(array('id_outlet'=>$value,'status'=>"Process",'status'=>"Waiting"))->first();
         $surveycutoff = OutletCutOff::where(array('id_outlet'=>$value,'status'=>"Process",'status'=>"Waiting"))->first();
         $surveyclose = OutletCloseTemporary::where(array('id_outlet'=>$value,'status'=>"Process",'status'=>"Waiting"))->first();
         if($survey&&$surveycutoff&&$surveyclose&&!$outlet){
             return false;
         } return true;
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
            'outlet' => 'Pengajuan cut off Outlet sedang di proses',
            'today' => "Minimal hari ini",
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
