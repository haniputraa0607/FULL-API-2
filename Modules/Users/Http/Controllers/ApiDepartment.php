<?php

namespace Modules\Users\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

use Modules\Users\Entities\Department;
use App\Jobs\SyncIcountDepartment;
use App\Http\Models\Setting;
use App\Lib\Icount;
use App\Lib\MyHelper;
use DB;
use Modules\Employee\Entities\DepartmentBudget;
use Modules\Employee\Entities\DepartmentBudgetLog;

class ApiDepartment extends Controller
{
	public function index(Request $request)
    {
        $post = $request->all();
        $department = Department::with(['department_parent', 'department_child']);

        if ($keyword = ($request->search['value']??false)) {
            $department->where('department_name', 'like', '%'.$keyword.'%')
                        ->orWhereHas('department_parent', function($q) use ($keyword) {
                                $q->where('department_name', 'like', '%'.$keyword.'%');
                            })
                        ->orWhereHas('department_child', function($q) use ($keyword) {
                            $q->where('department_name', 'like', '%'.$keyword.'%');
                        });
        }

        if(isset($post['get_child']) && $post['get_child'] == 1){
            $department = $department->whereNotNull('id_parent');
        }

        if(isset($post['page'])){
            $department = $department->orderBy('department_name')->paginate($request->length ?: 10);
        }else{
            $department = $department->orderBy('department_name')->get()->toArray();
        }

        return MyHelper::checkGet($department);
    }

	public function store(Request $request)
    {
        $post = $request->all();
        if (!empty($post['data'])) {
            DB::beginTransaction();
            $data_request = $post['data'];

            $store = Department::create(['department_name' => $data_request[0]['department_name']]);

            if ($store) {
                if (isset($data_request['child'])) {
                    $id = $store->id_department;
                    foreach ($data_request['child'] as $key => $child) {
                        $id_parent = null;
                        if ($child['parent'] == 0) {
                            $id_parent = $id;
                        } elseif (isset($data_request['child'][$child['parent']]['id'])) {
                            $id_parent = $data_request['child'][$child['parent']]['id'];
                        }

                        $store = Department::create([
                            'department_name' => $child['department_name'],
                            'id_parent' => $id_parent
                        ]);

                        if ($store) {
                            $data_request['child'][$key]['id'] = $store->id_department;
                        } else {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed add department']]);
                        }
                    }
                }
            } else {
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed add department']]);
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

        if(isset($post['id_department']) && !empty($post['id_department'])){
            $get_all_parent = Department::where(function ($q){
                $q->whereNull('id_parent')->orWhere('id_parent', 0);
            })->get()->toArray();

            $department = Department::where('id_department', $post['id_department'])->with(['department_parent', 'department_child'])->first();

            return response()->json(['status' => 'success', 'result' => [
                'all_parent' => $get_all_parent,
                'department' => $department
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

        if (!empty($post['id_department'])) {
            DB::beginTransaction();
            if (isset($post['department_name'])) {
                $data_update['department_name'] = $post['department_name'];
            }

            if (isset($post['id_parent'])) {
                $data_update['id_parent'] = $post['id_parent'];
            }

            $update = Department::where('id_department', $post['id_department'])->update($data_update);
            if ($update) {
                if (!empty($post['child'])) {
                    foreach ($post['child'] as $child) {
                        $data_update_child['id_parent'] = $post['id_department'];
                        if (isset($child['department_name'])) {
                            $data_update_child['department_name'] = $child['department_name'];
                        }

                        if (!empty($child['id_department'])) {
                        	$update = Department::updateOrCreate(['id_department' => $child['id_department']], $data_update_child);
                        } else {
                        	$update = Department::create($data_update_child);

                        }

                        if (!$update) {
                            DB::rollback();
                            return response()->json(['status' => 'fail', 'messages' => ['Failed update child department']]);
                        }
                    }
                }
            }else{
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update department']]);
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
        $id_department = $request->json('id_department');
        $delete = Department::where('id_department', $id_department)->delete();

        if($delete){
            $delete = $this->deleteChild($id_department);
        }

        return MyHelper::checkDelete($delete);
    }

    public function deleteChild($id_parent){
        $get = Department::where('id_parent', $id_parent)->first();
        if($get){
            $delete = Department::where('id_parent', $id_parent)->delete();
            $this->deleteChild($get['id_department']);
            return $delete;
        }else{
            return true;
        }
    }

    public function syncIcount(Request $request){
        $log = MyHelper::logCron('Sync Department Icount');
        try{
            $setting = Setting::where('key' , 'Sync Department Icount')->first();
            if($setting){
                if($setting['value'] != 'finished'){
                    return ['status' => 'fail', 'messages' => ['Cant sync now, because sync is in progress']]; 
                }
                $update_setting = Setting::where('key', 'Sync Department Icount')->update(['value' => 'start']);
            }else{
                $create_setting = Setting::updateOrCreate(['key' => 'Sync Department Icount'],['value' => 'start']);
            }
            $send = [
                'page' => 1,
                'id_departments' => null
            ];
            $sync_job = SyncIcountDepartment::dispatch($send);
            return ['status' => 'success', 'messages' => ['Success to sync with ICount']]; 
        } catch (\Exception $e) {
            $log->fail($e->getMessage());
        }    
    }

    public function resetBalance(){
        $log = MyHelper::logCron('Reset Department Balance');
        try{
            $setting = Setting::where('key' , 'department_balance_reset')->get()->toArray();
            $date_now = date('d F');
            DB::beginTransaction();
            foreach($setting ?? [] as $key => $set){
                if($set['value'] == $date_now){
                    $department_badget = DepartmentBudget::with(['logs' => function($q){$q->orderBy('created_at', 'desc')->first(); }])->orderBy('id_department_budget', 'asc')->get()->toArray();
                    foreach($department_badget ?? [] as $key_2 => $department){
                        $logs = [];
                        $logs[] = [
                            'id_department_budget' => $department['logs'][0]['id_department_budget'],
                            'date_budgeting' => date('Y-m-d'),
                            'source' => 'Reset Department Balance',
                            'balance' => -$department['logs'][0]['balance_total'],
                            'balance_before' => $department['logs'][0]['balance_total'],
                            'balance_after' => 0,
                            'balance_total' => 0,
                            'notes' => null
                        ];
                        $logs[] = [
                            'id_department_budget' => $department['id_department_budget'],
                            'date_budgeting' => date('Y-m-d'),
                            'source' => 'Reset Department Balance',
                            'balance' => $department['budget_balance'],
                            'balance_before' => 0,
                            'balance_after' => $department['budget_balance'],
                            'balance_total' => $department['budget_balance'],
                            'notes' => null
                        ];
                        $store = DepartmentBudgetLog::insert($logs);
                    }
                }
            }
            DB::commit();
            $log->success('success');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            DB::rollback();
            $log->fail($e->getMessage());
        } 
    }
}
