<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\CreateSurveyLocationRequest;
use Modules\Project\Http\Requests\Project\CreateDesainRequest;
use Modules\Project\Http\Requests\Project\CreateFitOutRequest;
use Modules\Project\Http\Requests\Project\DeleteDesain;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use Modules\Project\Entities\ProjectSurveyLocation;
use Modules\Project\Entities\ProjectFitOut;

class ApiFitOutController extends Controller
{
   public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/project/fit_out/"; 
    }
    public function create(CreateFitOutRequest $request)
    {
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
            $store = ProjectFitOut::create([
                    "id_project"   =>  $request->id_project,
                    "title"   =>   $request->title,
                    "progres"   =>   $request->progres,
                    "attachment"   =>  $attachment,
                    "note"   =>  $request->note
                ]);
       
            return response()->json(MyHelper::checkCreate($store));
    }
    
    public function nextStep(Request $request)
    {
        if(isset($request->id_project)){
         $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process','progres'=>"Fit Out"))
                ->update([
                    'progres'=>'Success',
                    'status'=>'Success'
                ]);
         if($project){
        $fitOut = ProjectFitOut::where(array('id_project'=>$request->id_project,'status'=>'Process'))->get();
        foreach ($fitOut as $value) {
            $value['status'] = "Success";
            $value->save();
        }
         return response()->json(['status' => 'success']);
         }
         return response()->json(['status' => 'fail', 'messages' => ['Tidak dalam proses fit out']]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
    public function destroy(Request $request)
    {
        if(isset($request->id_projects_fit_out)){
        $delete = ProjectFitOut::where('id_projects_fit_out', $request->id_projects_fit_out)->where(array('status'=>'Process'))->delete();
        return response()->json(['status' => 'success', 'messages' => ['Data berhasil dihapus']]);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function index(Request $request)
    {
        if(isset($request->id_project)){
        $index = ProjectFitOut::where('id_project', $request->id_project)->get();
         return response()->json(['status' => 'success','result'=>$index]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
