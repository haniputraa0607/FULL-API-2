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
        
        $store = ProjectSurveyLocation::where(array('id_project'=>$request->id_project))->first();
        $attachment = null;
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        if($store){
            $attachment = $store->attachment;
            $note = $store->note;
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
            $store = ProjectSurveyLocation::where(array('id_project'=>$request->id_project))->update([
                    "location_length"   =>  $request->location_length,
                    "location_width"   =>  $request->location_width,
                    "location_large"   =>  $request->location_large,
                    "surveyor"   =>  $request->surveyor,
                    "survey_date"   =>  date_format(date_create($request->survey_date),"Y-m-d H:i:s"),
                    "note"   =>  $note,
                    "attachment"   =>  $attachment,
                ]);
            $store = ProjectSurveyLocation::where(array('id_project'=>$request->id_project))->first();
        }else{
            
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
                $store = ProjectSurveyLocation::create([
                    "id_project"   =>  $request->id_project,
                    "location_length"   =>  $request->location_length,
                    "location_width"   =>  $request->location_width,
                    "location_large"   =>  $request->location_large,
                    "surveyor"   =>  $request->surveyor, 
                    "survey_date"   => date_format(date_create($request->survey_date),"Y-m-d H:i:s"),
                    "attachment"   =>  $attachment,
                    "note"   =>  $note
                ]);
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
