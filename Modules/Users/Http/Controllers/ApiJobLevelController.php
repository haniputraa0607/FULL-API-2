<?php

namespace Modules\Users\Http\Controllers;

use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Routing\Controller;
use App\Http\Models\Product;
use Illuminate\Support\Facades\Artisan;
use DB;
use Illuminate\Support\Facades\Log;
use Modules\Users\Entities\JobLevel;

class ApiJobLevelController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        $list = JobLevel::with(['job_level_parent', 'job_level_child']);

        if ($keyword = ($request->search['value']??false)) {
            $list->where('job_level_name', 'like', '%'.$keyword.'%')
                        ->orWhereHas('job_level_parent', function($q) use ($keyword) {
                                $q->where('job_level_name', 'like', '%'.$keyword.'%');
                            })
                        ->orWhereHas('job_level_child', function($q) use ($keyword) {
                            $q->where('job_level_name', 'like', '%'.$keyword.'%');
                        });
        }

        if(isset($post['get_child']) && $post['get_child'] == 1){
            $list = $list->whereNotNull('id_parent');
        }

        if(isset($post['page'])){
            $list = $list->orderBy('updated_at', 'desc')->paginate($request->length?:10);
        }else{
            $list = $list->orderBy('job_level_order', 'asc')->get()->toArray();
        }

        return MyHelper::checkGet($list);
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->all();
        if(isset($post['data']) && !empty($post['data'])){
            DB::beginTransaction();
            $data_request = $post['data'];

            $store = JobLevel::create([
                            'job_level_name' => $data_request[0]['job_level_name'],
                            'job_level_visibility' => 'Visible']);

            if($store){
                if(isset($data_request['child'])){
                    $id = $store['id_job_level'];
                    foreach ($data_request['child'] as $key=>$child){
                        $id_parent = NULL;

                        if($child['parent'] == 0){
                            $id_parent = $id;
                        }elseif(isset($data_request['child'][(int)$child['parent']]['id'])){
                            $id_parent = $data_request['child'][(int)$child['parent']]['id'];
                        }

                        $store = JobLevel::create([
                            'job_level_name' => $child['job_level_name'],
                            'job_level_visibility' => 'Visible',
                            'id_parent' => $id_parent]);

                        if($store){
                            $data_request['child'][$key]['id'] = $store['id_job_level'];
                        }else{
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add job level']]);
                        }
                    }
                }
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add job level']]);
            }

            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit(Request $request)
    {
        $post = $request->all();

        if(isset($post['id_job_level']) && !empty($post['id_job_level'])){
            $get_all_parent = JobLevel::where(function ($q){
                $q->whereNull('id_parent')->orWhere('id_parent', 0);
            })->get()->toArray();

            $job_level = JobLevel::where('id_job_level', $post['id_job_level'])->with(['job_level_parent', 'job_level_child'])->first();

            return response()->json(['status' => 'success', 'result' => [
                'all_parent' => $get_all_parent,
                'job_level' => $job_level
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->all();

        if(isset($post['id_job_level']) && !empty($post['id_job_level'])){
            DB::beginTransaction();
            if(isset($post['job_level_name'])){
                $data_update['job_level_name'] = $post['job_level_name'];
            }

            if(isset($post['id_parent'])){
                $data_update['id_parent'] = $post['id_parent'];
            }

            $update = JobLevel::where('id_job_level', $post['id_job_level'])->update($data_update);

            if($update){
                if(isset($post['child']) && !empty($post['child'])){
                    foreach ($post['child'] as $child){
                        $data_update_child['id_parent'] = $post['id_job_level'];
                        if(isset($child['job_level_name'])){
                            $data_update_child['job_level_name'] = $child['job_level_name'];
                        }

                        $data_update_child['job_level_visibility'] = 'Visible';

                        $update = JobLevel::updateOrCreate(['id_job_level' => $child['id_job_level']], $data_update_child);

                        if(!$update){
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed update child job level']]);
                        }
                    }
                }
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update job level']]);
            }

            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy(Request $request)
    {
        $id_job_level = $request->json('id_job_level');
        $delete              = JobLevel::where('id_job_level', $id_job_level)->delete();

        if($delete){
            $delete = $this->deleteChild($id_job_level);
        }
        return MyHelper::checkDelete($delete);
    }

    public function deleteChild($id_parent){
        $get = JobLevel::where('id_parent', $id_parent)->first();
        if($get){
            $delete  = JobLevel::where('id_parent', $id_parent)->delete();
            $this->deleteChild($get['id_job_level']);
            return $delete;
        }else{
            return true;
        }
    }

    public function position(Request $request){
        $post = $request->all();

        if(empty($post)){
            $data = JobLevel::orderBy('job_level_order', 'asc')->where(function ($q){
                $q->whereNull('id_parent')->orWhere('id_parent', 0);
            })->with('job_level_child')->get()->toArray();
            return MyHelper::checkGet($data);
        }else{
            foreach ($request->position as $position => $id_job_level) {
                JobLevel::where('id_job_level', $id_job_level)->update(['job_level_order' => $position]);
            }
            return MyHelper::checkUpdate(true);
        }
    }
}
