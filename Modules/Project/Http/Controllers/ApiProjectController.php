<?php

namespace Modules\Project\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Project\Http\Requests\Project\CreateProjectRequest;
use Modules\Project\Entities\Project;
use App\Lib\MyHelper;

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
    public function detail(Request $request){
        $id_project = $request['id_project'];
        if(isset($id_project) && !empty($id_project)){
            $project = Project::where(array('id_project'=>$id_project))
                    ->with(['project_locations','project_partners','project_survey','project_desain','project_contract','project_fitout'])
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
}
