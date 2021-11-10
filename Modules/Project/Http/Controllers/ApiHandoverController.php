<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\CreateSurveyLocationRequest;
use Modules\Project\Http\Requests\Project\CreateHandoverRequest;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use Modules\Project\Entities\ProjectContract;
use Modules\Project\Entities\ProjectHandover;

class ApiHandoverController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/project/handover/"; 
    }
    public function create(Request $request)
    {
        
        $store = ProjectHandover::where(array('id_project'=>$request->id_project))->first();
        $attachment = '';
        $note = '';
        if(isset($request->note)){
            $note = $request->note;
        }
        if($store){
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
             $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Handover"))
                ->update([
                    'progres'=>'Success',
                    'status'=>'Success'
                ]);
            $store = ProjectHandover::where(array('id_project'=>$request->id_project))->update([
                    "title"   =>  $request->title,
                    "attachment"   =>  $attachment,
                    'status'=>'Success',
                    "note"   =>  $note
                ]);
            $store = ProjectHandover::where(array('id_project'=>$request->id_project))->first();
            $project = Project::where('id_project',$request->id_project)->first();
            $outlet = Project::join('locations','locations.id_location','projects.id_location')
                ->where('locations.id_location', $project->id_location)
                ->join('cities','cities.id_city','locations.id_city')
                ->join('outlets','outlets.id_city','cities.id_city')
                ->update(['outlet_status'=>"Active"]);
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
                  $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Handover"))
                ->update([
                    'progres'=>'Success',
                    'status'=>'Success'
                ]);
                $store = ProjectHandover::create([
                    "id_project"   =>  $request->id_project,
                    "title"   =>  $request->title,
                    "attachment"   =>  $attachment,
                    'status'=>'Success',
                    "note"   =>  $note
                ]);
                $outlet = Project::join('locations','locations.id_location','projects.id_location')
                ->where('locations.id_location', $project->id_location)
                ->join('cities','cities.id_city','locations.id_city')
                ->join('outlets','outlets.id_city','cities.id_city')
                ->update(['outlet_status'=>"Active"]);
        }
            return response()->json(MyHelper::checkCreate($store));
    }
  
    public function nextStep(Request $request)
    {
        if(isset($request->id_project)){
         $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Handover"))
                ->update([
                    'progres'=>'Success',
                    'status'=>'Success'
                ]);
         if($project){
        $contract = ProjectHandover::where(array('id_project'=>$request->id_project,'status'=>'Process'))->update([
            'status'=>'Success'
        ]);
         return response()->json(['status' => 'success']);
         }
         return response()->json(['status' => 'fail', 'messages' => ['Progres bukan handover']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
    public function destroy(Request $request)
    {
        if($request->id_project){
        $survey = ProjectHandover::where('id_project', $request->id_project)->where(array('status'=>'Process'))->delete();
        return MyHelper::checkDelete($survey);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function detail(Request $request)
    {
        
        if(isset($request->id_project)){
         $survey = ProjectHandover::where('id_project', $request->id_project)->first();
         if($survey){
            return response()->json(['status' => 'success','result'=>$survey]);
            }
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        
    }
}
