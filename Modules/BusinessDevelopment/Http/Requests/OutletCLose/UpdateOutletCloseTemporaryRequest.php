<?php

namespace Modules\BusinessDevelopment\Http\Requests\OutletCLose;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\OutletCutOff;
use Modules\BusinessDevelopment\Entities\OutletCloseTemporary;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;

class UpdateOutletCloseTemporaryRequest extends FormRequest
{
    public function rules()
    {
        return [
            'id_outlet_close_temporary' => 'required|outlet',
            'date'              => 'required|today',
            'title'              => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('outlet', function ($attribute, $value, $parameters, $validator) {
         $survey = OutletCloseTemporary::where(array('id_outlet_close_temporary'=>$value,'status'=>"Process"))->first();
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
            'outlet' => 'Data Close Temporary tidak sedang dalam proses',
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
