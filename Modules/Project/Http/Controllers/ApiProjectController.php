<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Http\Requests\Project\InitProjectRequest;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\Location;
use App\Http\Models\Outlet;
use Modules\Project\Http\Requests\Project\UpdateProjectRequest;
class ApiProjectController extends Controller
{
   
    public function create(CreateProjectRequest $request)
    {
                $store = Project::create([
                    "id_partner"   =>  $request->partner,
                    "id_location"   =>  $request->location,
                    "name"   =>  $request->name,
                    "start_project"   =>  $request->start_project,
                    "note"   =>  $request->note
                ]);
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
        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){               
                             if($condition['subject']=='id_partner'){
                                $project = $project->where('partners.'.$condition['subject'], $condition['parameter']);
                            }
                            elseif($condition['subject']=='id_location'){
                                $project = $project->where('location.'.$condition['subject'], $condition['parameter']);
                            }
                            elseif($condition['subject']=='progres'){
                                $project = $project->where('projects.'.$condition['subject'], $condition['parameter']);
                            }
                            else{
                                 if($condition['operator'] == 'like'){
                                      $project = $project->where('projects.'.$condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                      $project = $project->where('projects.'.$condition['subject'], $condition['parameter']);
                                 }
                           
                            }
                      
                    }
                }
            }else{
                $project = $project->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                              if($condition['subject']=='id_partner'){
                                $q->orWhere('partners.'.$condition['subject'], $condition['parameter']);
                            }
                            elseif($condition['subject']=='id_location'){
                                $q->orWhere('location.'.$condition['subject'], $condition['parameter']);
                            }
                            elseif($condition['subject']=='progres'){
                                $q->orWhere('projects.'.$condition['subject'], $condition['parameter']);
                            }
                            else{
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere('projects.'.$condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                      $q->orWhere('projects.'.$condition['subject'], $condition['parameter']);
                                 }
                           
                            }
                        }
                    }
                });
            }
        }
            $project = $project->orderBy('created_at', 'desc')->paginate($request->length ?: 10);
        return MyHelper::checkGet($project);
    }
    public function detail(Request $request){
        $id_project = $request['id_project'];
        if(isset($id_project) && !empty($id_project)){
            $project = Project::where(array('id_project'=>$id_project))
                    ->with(['project_locations','project_partners','project_survey','project_desain','project_contract','project_fitout','project_handover'])
                    ->join('locations','locations.id_location','projects.id_location')
                    ->join('partners','partners.id_partner','projects.id_partner')
                    ->select('projects.*','partners.name as name_partner','locations.name as name_location')
                    ->first();
            if($project){
                return response()->json(['status' => 'success', 'result' => $project ]);
            } else {
                return response()->json(['status' => 'fail', 'result' => 'Empty Data']);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
    public function destroy(Request $request)
    {
         if($request->id_project){
        $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process'))->update(['status'=>'Reject']);
        return MyHelper::checkDelete($project);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }
    public function update(UpdateProjectRequest $request)
    {
         $project = Project::where('id_project', $request->id_project)->where(array('status'=>'Process'))->update(['note'=>$request->note]);
         return response()->json(['status' => 'success', 'result' => $project ]);
    }
    public function initProject(Partner $partner,Location $location, $note = null)
    { 
        $project = Project::create(
                [
                    'id_partner' =>$partner->id_partner,
                    'id_location' =>$location->id_location,
                    'name' =>$location->name,
                    'start_project' =>date('Y-m-d H:i:s'),
                    'note' =>$note,
                ]);
        $outlet = Outlet::create([
            'outlet_code' => $this->outlet_code(),
            'id_location' => $location->id_location,
            'outlet_name' => $location->name,
            'outlet_address' => $location->address,
            'id_city' => $location->id_city,
            'outlet_postal_code' => $location->city_postal_code,
            'outlet_latitude' => $location->latitude,
            'outlet_longitude' => $location->longitude,
            'outlet_status' => 'Inactive',
        ]);
        return response()->json(['status' => 'success','result'=>[
            'project'=>$project,
            'outlet'=>$outlet
        ]]);
        
        
    }
    function outlet_code(){
        $outlet = Outlet::orderby('created_at','desc')->first();
        $awal = "M";
        if($outlet){
         $angka = str_replace($awal,"", $outlet->outlet_code);
         $u = 1; 
         for($x = 0; $x < $u; $x++){
             $angka++;
            $outlet_code = $awal.$angka;
            $outlet = Outlet::where(array('outlet_code'=>$outlet_code))->first();
            if(!$outlet){
                $outlet_code;
                break;
            }
            $u++;
         }
        return $outlet_code;
        }
        return $awal."1";
    }
}
