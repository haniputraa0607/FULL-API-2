<?php

namespace Modules\Users\Http\Controllers;

use App\Http\Models\User;
use App\Http\Models\UserFeature;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use DB;
use Modules\Users\Entities\JobLevel;
use Modules\Users\Entities\RolesFeature;
use Hash;
use Modules\Users\Entities\Role;

class ApiRoleController extends Controller
{
    public function listAll()
    {
        $list = Role::get()->toArray();
        return response()->json(MyHelper::checkGet($list));
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $data = Role::LeftJoin('departments', 'departments.id_department', 'roles.id_department')
                ->LeftJoin('job_levels', 'job_levels.id_job_level', 'roles.id_job_level')
                ->select('roles.*', 'departments.department_name', 'job_levels.job_level_name');

        if ($keyword = ($request->search['value']??false)) {
            $data->where('role_name', 'like', '%'.$keyword.'%')
                ->orWhere('department_name', 'like', '%'.$keyword.'%')
                ->orWhere('job_level_name', 'like', '%'.$keyword.'%');
        }

        $data = $data->paginate(20);

        return response()->json(MyHelper::checkGet($data));
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->all();
        $user = User::where('id', $request->user()->id)->first()->makeVisible('password');

        if (!Hash::check($post['password_permission'], $user['password'])) {
            $result = [
                'status'    => 'fail',
                'messages'    => ['Update permission failed. Wrong PIN']
            ];
            return response()->json($result);
        }

        DB::beginTransaction();

        $store = Role::create(
            [
                'role_name' => $post['role_name'],
                'id_department' => $post['id_department'],
                'id_job_level' => $post['id_job_level'],
                'created_by' => $request->user()->id
            ]
        );

        if($store){
            $arr = [];
            foreach ($post['module'] as $id_feature) {
                $arr[] = [
                    'id_role' => $store['id_role'],
                    'id_feature' => $id_feature
                ];
            }

            $insert = false;
            if(!empty($arr)){
                $insert = RolesFeature::insert($arr);
            }

            DB::commit();
            return response()->json(MyHelper::checkCreate($insert));
        }else{
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Failed add role']]);
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

        if(isset($post['id_role']) && !empty($post['id_role'])){
            $detail = Role::where('id_role', $post['id_role'])->with(['roles_features'])->first();
            return response()->json(MyHelper::checkGet($detail));
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
        $user = User::where('id', $request->user()->id)->first()->makeVisible('password');

        if(isset($post['id_role']) && !empty($post['id_role'])){
            if (!Hash::check($post['password_permission'], $user['password'])) {
                $result = [
                    'status'    => 'fail',
                    'messages'    => ['Update permission failed. Wrong PIN']
                ];
                return response()->json($result);
            }

            DB::beginTransaction();

            $update = Role::where('id_role', $post['id_role'])->update(
                [
                    'role_name' => $post['role_name'],
                    'id_department' => $post['id_department'],
                    'id_job_level' => $post['id_job_level'],
                    'updated_by' => $request->user()->id
                ]
            );

            if($update){
                RolesFeature::where('id_role', $post['id_role'])->delete();
                $arr = [];
                foreach ($post['module'] as $id_feature) {
                    $arr[] = [
                        'id_role' => $post['id_role'],
                        'id_feature' => $id_feature
                    ];
                }

                $insert = false;
                if(!empty($arr)){
                    $insert = RolesFeature::insert($arr);
                }

                DB::commit();
                return response()->json(MyHelper::checkCreate($insert));
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add role']]);
            }
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
        $post = $request->all();
        if(isset($post['id_role']) && !empty($post['id_role'])){
            $delete = Role::where('id_role', $post['id_role'])->delete();

            if($delete){
                $delete = RolesFeature::where('id_role', $post['id_role'])->delete();
            }

            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
