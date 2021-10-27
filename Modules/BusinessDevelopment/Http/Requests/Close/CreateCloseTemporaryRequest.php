<?php

namespace Modules\BusinessDevelopment\Http\Requests\Close;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;

class CreateCloseTemporaryRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'             => 'required',
            'close_date'        => 'required',
            'id_partner'        => 'required|partner',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('partner', function ($attribute, $value, $parameters, $validator) {
         $survey = PartnersCloseTemporary::where(array('id_partner'=>$value,'status'=>"Process"))->first();
         if($survey){
             return false;
         } return true;
        }); 
    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'partner' => 'Partners sedang mengajukan pemutusan sementara',
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
