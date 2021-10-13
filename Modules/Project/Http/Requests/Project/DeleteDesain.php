<?php

namespace Modules\Project\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
class DeleteDesain extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id_project'   => 'required|project',
            'id_projects_desain'   => 'required',
        ]; 
    }
     public function withValidator($validator)
    {
        $validator->addExtension('project', function ($attribute, $value, $parameters, $validator) {
         $project = Project::where(array('id_project'=>$value,'status'=>"Process"))->first();
         if($project){
             return true;
         } return false;
        }); 
    }
    public function messages()
    {
        return [
            'project'=> 'Project tidak dalam status Process',
            'required' => ':attribute harus diisi'
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
