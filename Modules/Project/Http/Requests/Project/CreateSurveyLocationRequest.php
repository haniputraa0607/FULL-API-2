<?php

namespace Modules\Project\Http\Requests\Project;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Http\Models\Outlet;
use Modules\Project\Entities\Project;
use Modules\BusinessDevelopment\Entities\Location;
class CreateSurveyLocationRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'location_length'   => 'required',
            'location_width'    => 'required',
            'location_large'    => 'required',
            'location_height'    => 'required',
            'survey_date' 	=> 'required|date',
            'id_project'        => 'required|project',
             "kondisi"   =>   'required',
            "keterangan_kondisi"   => 'required',
            "listrik"   =>  'required',
            "keterangan_listrik"   =>  'required',
            "ac"   =>   'required',
            "keterangan_ac"   =>   'required',
            "air"   =>   'required',
            "keterangan_air"   =>  'required',
            "internet"   =>  'required',
            "keterangan_internet"   => 'required',
            "line_telepon"   =>   'required',
            "keterangan_line_telepon"   =>   'required',
            "nama_pic_mall"   =>  'required',
            "cp_pic_mall"   =>   'required',
//            "nama_kontraktor"   =>   'required',
//            "cp_kontraktor"   =>   'required',
//            "area_lokasi"   =>   'required',
            "tanggal_mulai_pekerjaan"   =>  'required',
            "tanggal_selesai_pekerjaan"   =>  'required',
            "tanggal_loading_barang"   =>  'required',
            "tanggal_pengiriman_barang"   =>  'required',
            "estimasi_tiba"   => 'required',

//            'note'              => 'required',
            'surveyor'          => 'required',
//            'attachment'          => 'required',
            
        ]; 
    }
    public function withValidator($validator)
    {
        $validator->addExtension('project', function ($attribute, $value, $parameters, $validator) {
         $survey = Project::where(array('id_project'=>$value,'status'=>"Process",'progres'=>"Survey Location"))->count();
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
