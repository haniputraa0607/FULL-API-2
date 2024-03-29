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
use App\Http\Models\Outlet;

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
    public function create(CreateHandoverRequest $request)
    {
        $attachment = '';
        $note = '';
        if(isset($request->note)){
            $note = $request->note;
        }
        if(isset($request->id_project)){
         $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Handover"))
                ->first();
        if($project){
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
                  $project->progres = "Success";
                  $project->save();
                  
                $store = ProjectHandover::create([
                    "id_project"   =>  $request->id_project,
                    "title"   =>  $request->title,
                    "attachment"   =>  $attachment,
                    "soft_opening"   =>  date_format(date_create($request->soft_opening),"Y-m-d H:i:s"),
                    "grand_opening"   =>  date_format(date_create($request->grand_opening),"Y-m-d H:i:s"),
                    'status'=>'Success',
                    "note"   =>  $note
                ]);
//                $outlet = Outlet::where('id_location', $project->id_location)
//                ->update(['outlet_status'=>"Active"]);
                 $project = Project::where(array('id_project'=>$request->id_project))->join('partners','partners.id_partner','projects.id_partner')->first();
            if (\Module::collections()->has('Autocrm')) {
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Approve Project',
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
            return response()->json(['status' => 'fail', 'messages' => ['Tidak dalam proses handover']]);    
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);    
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
