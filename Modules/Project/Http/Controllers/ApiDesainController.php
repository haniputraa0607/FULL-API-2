<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\CreateSurveyLocationRequest;
use Modules\Project\Http\Requests\Project\CreateDesainRequest;
use Modules\Project\Http\Requests\Project\DeleteDesain;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use Modules\Project\Entities\ProjectSurveyLocation;
use Modules\Project\Entities\ProjectDesain;
class ApiDesainController extends Controller
{
   public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/project/desain/"; 
    }
    public function create(CreateDesainRequest $request)
    {
        $projectDesain = ProjectDesain::where(array('id_project'=>$request->id_project))->orderby('id_projects_desain','DESC')->first();
        $attachment = null;
        $note = null;
        if(isset($request->note)){
            $note = $request->note;
        }
        if(!$projectDesain){
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
            $store = ProjectDesain::create([
                    "id_project"   =>  $request->id_project,
                    "desain"   =>  1,
                    "status"   =>  $request->status,
                    "nama_designer"   =>  $request->nama_designer,
                    "cp_designer"   =>  $request->cp_designer,
                    "attachment"   =>  $attachment,
                    "note"   =>  $note
                ]);
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
                $store = ProjectDesain::create([
                    "id_project"   =>  $request->id_project,
                    "desain"   =>  $projectDesain->desain+1,
                    "status"   =>  $request->status,
                    "nama_designer"   =>  $request->nama_designer,
                    "cp_designer"   =>  $request->cp_designer,
                    "attachment"   => $attachment,
                    "note"   =>  $note
                ]);
        }
            return response()->json(MyHelper::checkCreate($store));
    }
    
    public function nextStep(Request $request)
    {
        if(isset($request->id_project)){
         $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Desain Location"))
                ->update([
                    'progres'=>'Contract'
                ]);
         if($project){
         return response()->json(['status' => 'success']);
         }
         return response()->json(['status' => 'fail', 'messages' => ['Proses Survey Lokasi belum ada']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
    public function destroy(DeleteDesain $request)
    {
        if(isset($request->id_projects_desain)){
        $delete = ProjectDesain::where('id_projects_desain', $request->id_projects_desain)->delete();
        $desain = ProjectDesain::where(array('id_project'=>$request->id_project))->get();
        $i = 1;
        foreach ($desain as $value) {
            $value['desain'] = $i;
            $value->save();
            $i++;
        }
        return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request)
    {
        if(isset($request->id_project)){
        $survey = ProjectDesain::where('id_project', $request->id_project)->get();
         return response()->json(['status' => 'success','result'=>$survey]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
