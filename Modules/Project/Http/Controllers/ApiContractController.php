<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\CreateSurveyLocationRequest;
use Modules\Project\Http\Requests\Project\CreateContractRequest;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use Modules\Project\Entities\ProjectContract;

class ApiContractController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/project/contract/"; 
    }
    public function create(CreateContractRequest $request)
    {
        $store = ProjectContract::where(array('id_project'=>$request->id_project))->first();
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
            $store = ProjectContract::where(array('id_project'=>$request->id_project))->update([
                    "first_party"   =>  $request->first_party,
                    "second_party"   =>  $request->second_party,
                    "nominal"   => str_replace(',', '', $request->nominal),
                    "attachment"   =>  $attachment,
                    "status"=>'Success',
                    "note"   =>  $note
                ]);
            $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Contract"))
                ->update([
                    'progres'=>'Fit Out'
                ]);
            $store = ProjectContract::where(array('id_project'=>$request->id_project))->first();
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
                 $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Contract"))
                ->update([
                    'progres'=>'Fit Out'
                ]);
                $store = ProjectContract::create([
                    "id_project"   =>  $request->id_project,
                    "first_party"   =>  $request->first_party,
                    "second_party"   =>  $request->second_party,
                    "nominal"   => str_replace(',', '', $request->nominal),
                    "attachment"   =>  $attachment,
                    "status"=>'Success',
                    "note"   =>  $note
                ]);
        }
            return response()->json(MyHelper::checkCreate($store));
    }
  
    public function nextStep(Request $request)
    {
        if(isset($request->id_project)){
         $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Contract"))
                ->update([
                    'progres'=>'Fit Out'
                ]);
         if($project){
        $contract = ProjectContract::where(array('id_project'=>$request->id_project,'status'=>'Process'))->update([
            'status'=>'Success'
        ]);
         return response()->json(['status' => 'success']);
         }
         return response()->json(['status' => 'fail', 'messages' => ['Proses Desain Lokasi belum ada']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
    public function destroy(Request $request)
    {
        if($request->id_project){
        $survey = ProjectContract::where('id_project', $request->id_project)->where(array('status'=>'Process'))->delete();
        return MyHelper::checkDelete($survey);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function detail(Request $request)
    {
        
        if(isset($request->id_project)){
         $survey = ProjectContract::where('id_project', $request->id_project)->first();
         if($survey){
            return response()->json(['status' => 'success','result'=>$survey]);
            }
        }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        
    }
}
