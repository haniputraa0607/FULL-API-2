<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\User;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;
use Modules\Employee\Entities\DesingRequest;

class ApiDesignRequestController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "file/design_request/";
    }

    public function storeDesignRequest(Request $request)
    {
        $post = $request->all();
        
        if (!empty($post)) {
            if (isset($post['title'])) {
                $data_store['title'] = $post['title'];
            }
            if (isset($post['required_note'])) {
                $data_store['required_note'] = $post['required_note'];
            }
            if (isset($post['required_date'])) {
                $data_store['required_date'] = date('Y-m-d',strtotime($post['required_date']));
            }
            $data_store['id_request'] = $request->user()->id;
            
            DB::beginTransaction();

            $store = DesingRequest::create($data_store); 
            if(!$store) {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add design request']]);
            }   
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function listDesignRequest(Request $request){
        $post = $request->all();
        
        $design_request = DesingRequest::with(['request','approve']);
        if(isset($post['conditions']) && !empty($post['conditions'])){
            $rule = 'and';
            if(isset($post['rule'])){
                $rule = $post['rule'];
            }
            if($rule == 'and'){
                foreach ($post['conditions'] as $condition){
                    if(isset($condition['subject'])){      

                        if($condition['subject']=='status'){
                            $condition['parameter'] = $condition['operator'];
                            $condition['operator'] = '=';
                        }elseif($condition['subject']=='request_name'){
                            if(!MyHelper::isJoined($design_request,'users')){
                                $design_request = $design_request->join('users','users.id','=','design_requests.id_request');
                            }
                            $condition['subject'] = 'users.name';
                        }else{
                            $condition['subject'] = 'design_requests.'.$condition['subject'];
                        }
                        
                        if($condition['operator'] == '='){
                            $design_request = $design_request->where($condition['subject'], $condition['parameter']);
                        }else{
                            $design_request = $design_request->where($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                        }
                    }
                }
            }else{
                $design_request = $design_request->where(function ($q) use ($post, $design_request){
                    foreach ($post['conditions'] as $condition){
                        if(isset($condition['subject'])){

                            if($condition['subject']=='status'){
                                $condition['parameter'] = $condition['operator'];
                                $condition['operator'] = '=';
                            }elseif($condition['subject']=='request_name'){
                                if(!MyHelper::isJoined($design_request,'users')){
                                    $design_request = $design_request->join('users','users.id','=','design_requests.id_request');
                                }
                                $condition['subject'] = 'users.name';
                            }else{
                                $condition['subject'] = 'design_requests.'.$condition['subject'];
                            }

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
            if($post['order']=='request_name'){
                if(!MyHelper::isJoined($design_request,'request_name')){
                    $design_request = $design_request->join('users','users.id','=','design_requests.id_request');
                }
                $design_request = $design_request->select('design_requests.*');
                if(isset($post['page'])){
                    $design_request = $design_request->orderBy('users.name', $post['order_type'])->paginate($request->length ?: 10);
                }else{
                    $design_request = $design_request->orderBy('users.name', $post['order_type'])->get()->toArray();
                }
            }else{
                $design_request = $design_request->select('design_requests.*');
                if(isset($post['page'])){
                    $design_request = $design_request->orderBy('design_requests.'.$post['order'], $post['order_type'])->paginate($request->length ?: 10);
                }else{
                    $design_request = $design_request->orderBy('design_requests.'.$post['order'], $post['order_type'])->get()->toArray();
                }
            }
        }else{
            if(isset($post['page'])){
                $design_request = $design_request->orderBy('design_requests.created_at', 'desc')->paginate($request->length ?: 10);
            }else{
                $design_request = $design_request->orderBy('design_requests.created_at', 'desc')->get()->toArray();
            }
        }
        return MyHelper::checkGet($design_request);
    }

    public function updateDesignRequest(Request $request){
        $post = $request->all();
        if (isset($post['id_design_request']) && !empty($post['id_design_request'])) {
            DB::beginTransaction();
            if (isset($post['status'])) {
                $data_update['status'] = $post['status'];
            }
            if (isset($post['title'])) {
                $data_update['title'] = $post['title'];
            }
            if (isset($post['required_date'])) {
                $data_update['required_date'] =  date('Y-m-d',strtotime($post['required_date']));
            }
            if (isset($post['required_note'])) {
                $data_update['required_note'] = $post['required_note'];
            }
            
            if($data_update['status'] != 'Pending'){
                $data_update['id_approve'] = $request->user()->id;
                $data_update['update_status_date'] = date('Y-m-d');
                if (isset($post['estimated_date'])) {
                    $data_update['estimated_date'] =  date('Y-m-d',strtotime($post['estimated_date']));
                }
                if($data_update['status'] == 'Approved'){
                    $data_update['design_path'] = null;
                    $data_update['finished_note'] = null;
                }elseif($data_update['status'] == 'Finished' || $data_update['status'] == 'Done Finished'){
                    if (isset($post['finished_note'])) {
                        $data_update['finished_note'] =  $post['finished_note'];
                    }
                    if (isset($post['design_path'])) {
                        $extension = pathinfo($post['original_name_design_path'])['extension'];
                        $upload = MyHelper::uploadFile($post['design_path'], $this->saveFile, $extension, 'Design-Request-'.$post['id_design_request']);
                        if (isset($upload['status']) && $upload['status'] == "success") {
                            $data_update['design_path'] = $upload['path'];
                        } else {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed to update design request']]);
                        }
                    }
                }
            }else{
                $data_update['id_approve'] = null;
                $data_update['update_status_date'] = null;
                $data_update['estimated_date'] = null;
                $data_update['design_path'] = null;
                $data_update['finished_note'] = null;
            }
            
            $update = DesingRequest::where('id_design_request', $post['id_design_request'])->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update design request']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detailDesignRequest(Request $request){
        $post = $request->all();

        if(isset($post['id_design_request']) && !empty($post['id_design_request'])){
            $design_request = DesingRequest::with(['request','approve'])->where('id_design_request', $post['id_design_request'])->first();
            if(isset($design_request['design_path'])){
                $design_request['design_path'] = env('STORAGE_URL_API').$design_request['design_path'];
            }
            return response()->json(['status' => 'success', 'result' => [
                'design_request' => $design_request,
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
