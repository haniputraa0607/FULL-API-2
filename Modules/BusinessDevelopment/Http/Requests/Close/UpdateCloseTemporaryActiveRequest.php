<?php

namespace Modules\BusinessDevelopment\Http\Requests\Close;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;

class UpdateCloseTemporaryActiveRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'                              => 'required',
            'start_date'                         => 'required|start_date|today',
            'id_partners_close_temporary'        => 'required|partner',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('partner', function ($attribute, $value, $parameters, $validator) {
         $survey = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$value,'status'=>"Process"))->first();
         if($survey){
             return true;
         } return false;
        }); 
        $validator->addExtension('start_date', function ($attribute, $value, $parameters, $validator) {
         $data = $validator->getData();
         $partner = PartnersCloseTemporary::where(array('id_partners_close_temporary'=>$data['id_partners_close_temporary']))->first();
         $survey = Partner::where(array('id_partner'=>$partner['id_partner'],'status'=>"Inactive"))->whereDate('end_date','>=',$value)->first();
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
            'partner' => 'Status tidak dalam proses',
            'start_date' => 'Start date melebihi kontrak',
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
