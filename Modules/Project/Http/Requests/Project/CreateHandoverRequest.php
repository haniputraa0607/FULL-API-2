<?php

namespace Modules\Project\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;

class CreateHandoverRequest extends FormRequest
{
    public function rules()
    {
        return [
            'title'       => 'required',
//            'attachment'        => 'required',
            'soft_opening'        => 'required',
            'grand_opening'        => 'required',
            'id_project'        => 'required|project',
//            'note'              => 'required',
           ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('project', function ($attribute, $value, $parameters, $validator) {
         $survey = Project::where(array('id_project'=>$value,'status'=>"Process",'progres'=>"Handover"))->count();
         if($survey != 0){
             return true;
         } return false;
        }); 

    }
    public function messages()
    {
        return [
            'required' => ':attribute harus diisi',
            'project' => 'Cek status dan progres dari project',
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
