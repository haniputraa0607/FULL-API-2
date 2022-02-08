<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\CreateSurveyLocationRequest;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use Modules\Project\Entities\ProjectSurveyLocation;
class ApiSurveyLocationController extends Controller
{
   public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/project/survey/"; 
    }
    public function create(CreateSurveyLocationRequest $request)
    {
        
        $attachment = null;
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
            if(isset($request->attachment)){
                    $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, 'pdf');
                     if (isset($upload['status']) && $upload['status'] == "success") {
                             $attachment = $upload['path'];
                         } else {
                             $result = [
                                 'status'   => 'fail',
                                 'messages' => ['fail upload file']
                             ];
                             return $result;
                         }
                 }
                 $project = Project::where(array('id_project'=>$request->id_project,'progres'=>'Survey Location'))->update([
                                'progres'=>'Desain Location'
                            ]);
                $store = ProjectSurveyLocation::create([
                    "id_project"   =>  $request->id_project,
                    "location_length"   =>  $request->location_length,
                    "location_width"   =>  $request->location_width,
                    "location_large"   =>  $request->location_large,
                    "location_height"   =>  $request->location_height,
                    "surveyor"   =>  $request->surveyor, 
                    "kondisi"   =>  $request->kondisi??'Tidak',
                    "keterangan_kondisi"   =>  $request->keterangan_kondisi??null,
                    "listrik"   =>  $request->listrik??'Tidak',
                    "keterangan_listrik"   =>  $request->keterangan_listrik??null,
                    "ac"   =>  $request->ac??'Tidak', 
                    "keterangan_ac"   =>  $request->keterangan_ac??null,
                    "air"   =>  $request->air??'Tidak',
                    "keterangan_air"   =>  $request->keterangan_air??null,
                    "internet"   =>  $request->internet??'Tidak',
                    "keterangan_internet"   =>  $request->keterangan_internet??null, 
                    "line_telepon"   =>  $request->line_telepon??'Tidak',
                    "keterangan_line_telepon"   =>  $request->keterangan_line_telepon??null,
                    "nama_pic_mall"   =>  $request->nama_pic_mall,
                    "cp_pic_mall"   =>  $request->cp_pic_mall,
//                    "nama_kontraktor"   =>  $request->nama_kontraktor, 
//                    "cp_kontraktor"   =>  $request->cp_kontraktor,
//                    "area_lokasi"   =>  $request->area_lokasi,
                    "tanggal_mulai_pekerjaan"   => date_format(date_create($request->tanggal_mulai_pekerjaan),"Y-m-d"),
                    "tanggal_selesai_pekerjaan"   => date_format(date_create($request->tanggal_selesai_pekerjaan),"Y-m-d"),
                    "tanggal_loading_barang"   => date_format(date_create($request->tanggal_loading_barang),"Y-m-d"),
                    "tanggal_pengiriman_barang"   => date_format(date_create($request->tanggal_pengiriman_barang),"Y-m-d"),
                    "estimasi_tiba"   => date_format(date_create($request->estimasi_tiba),"Y-m-d"),
                    "survey_date"   => date_format(date_create($request->survey_date),"Y-m-d H:i:s"),
                    "attachment"   =>  $attachment,
                    'status'=>'Success',
                    "note"   =>  $note
                ]);
            $project = Project::where(array('id_project'=>$request->id_project))->join('partners','partners.id_partner','projects.id_partner')->first();
            if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Update Project',
                            $project->phone,
                            [
                                'name' => $project->name,
                            ], null, null, null, null, null, null, null, 1,
                        );
                        // return $autocrm;
                        if (!$autocrm) {
                            return response()->json([
                                'status'    => 'fail',
                                'messages'  => ['Failed to send']
                            ]);
                        }
                    }
            return response()->json(MyHelper::checkCreate($store));
    }
   public function index(Request $request)
    {
        $post = $request->all();
       
        if (isset($post['status']) && $post['status'] == 'Process') {
            $project = Project::where('projects.status','Process')
                    ->join('locations','locations.id_location','projects.id_location')
                    ->join('partners','partners.id_partner','projects.id_partner')
                    ->select('projects.*','partners.name as name_partner','locations.name as name_location');
        } else {
            $project = Project::where('projects.status','Success')
                    ->join('locations','locations.id_location','projects.id_location')
                    ->join('partners','partners.id_partner','projects.id_partner')
                    ->select('projects.*','partners.name as name_partner','locations.name as name_location');
        }
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){                
                        if($condition['operator'] == '='){
                            $project = $project->where($condition['subject'], $condition['parameter']);
                        }else{
                            $project = $project->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $project = $project->where(function ($q) use ($post){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){
                            if($condition['operator'] == '='){
                                $q->orWhere($condition['subject'], $condition['parameter']);
                            }else{
                                $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                            }
                        }
                    }
                });
            }
        }
        if(isset($post['order']) && isset($post['order_type'])){
            $project = $project->orderBy($post['order'], $post['order_type'])->paginate($request->length ?: 10);
        }else{
            $project = $project->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
            
        }
        return MyHelper::checkGet($project);
    }
    public function nextStep(Request $request)
    {
        if(isset($request->id_project)){
        $project = Project::where(array('id_project'=>$request->id_project,'progres'=>'Survey Location'))->update([
             'progres'=>'Desain Location'
         ]);
        if($project){
            $survey = ProjectSurveyLocation::where(array('id_project'=>$request->id_project))->where(array('status'=>'Process'))
                ->update([
                    'status'=> "Success"
                ]);
         return response()->json(['status' => 'success']);
        }
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        
    }
    public function destroy(Request $request)
    {
         if($request->id_project){
        $survey = ProjectSurveyLocation::where('id_project', $request->id_project)->where(array('status'=>'Process'))->delete();
        return MyHelper::checkDelete($survey);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
       
    }
    public function detail(Request $request)
    {
        if($request->id_project){
        $survey = ProjectSurveyLocation::where('id_project', $request->id_project)->where(array('status'=>'Process'))->first();
        return MyHelper::checkDelete($survey);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
}
