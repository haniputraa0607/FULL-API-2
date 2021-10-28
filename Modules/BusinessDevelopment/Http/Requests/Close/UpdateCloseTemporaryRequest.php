<?php

namespace Modules\BusinessDevelopment\Http\Requests\Close;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\PartnersCloseTemporary;

class UpdateCloseTemporaryRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'                              => 'required',
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
