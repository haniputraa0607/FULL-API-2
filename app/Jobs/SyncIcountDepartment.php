<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Users\Entities\Department;
use App\Http\Models\Setting;
use App\Lib\Icount;
use Storage;
use DB;

class SyncIcountDepartment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $id_departments = $this->data['id_departments'];
        \Log::debug($this->data['page']);
        $data = Icount::DepartmentList($this->data['page']);
        \Log::debug($data);
        if(isset($data) && isset($data['response'])){
            if(isset($data['response']['Message']) && $data['response']['Message']=='Success'){
                $departments = $data['response']['Data'];
                $departments = $this->checkInputIcount($departments);
                if($data['response']['Meta']['Pagination']['CurrentPage']==1){
                    $index = 0;
                }else{
                    $index = count($id_departments);
                }
                foreach($departments as $department){
                    $id_departments[$index] = $department['id_department_icount'];
                    $check_department = Department::where('id_department_icount','=',$department['id_department_icount'])->first();
                    if($check_department){
                        $update = Department::where('id_department_icount','=',$department['id_department_icount'])->update($department);
                        if(!$update){
                            return ['status' => 'fail', 'messages' => ['Failed to sync with ICount']];    
                        }
                    }else{
                        $store = Department::create($department);
                        if(!$store){
                            return ['status' => 'fail', 'messages' => ['Failed to sync with ICount']];    
                        }
                    }
                    $index++;
                }

                if($data['response']['Meta']['Pagination']['CurrentPage']<$data['response']['Meta']['Pagination']['LastPage']){
                    $new_page = $data['response']['Meta']['Pagination']['CurrentPage'] + 1;
                    SyncIcountDepartment::dispatch(['page'=> $new_page,'id_departments' => $id_departments])->onConnection('syncicountdepartments');  
                    Setting::where('key','Sync Department Icount')->update(['value' => 'process']);
                }else{
                    Department::where('from_icount',1)->whereIn('id_department_icount',$id_departments)->update(['is_actived' => 'true']);
                    Department::where('from_icount',1)->whereNotIn('id_department_icount',$id_departments)->update(['is_actived' => 'false']);
                    Setting::where('key','Sync Department Icount')->update(['value' => 'finished']);
                }
            }
        }
    }

    public function checkInputIcount($array){
        if($array){
            $data = [];
            foreach($array as $key => $department){
                if (isset($department['DepartmentID'])) {
                    $data[$key]['id_department_icount'] = $department['DepartmentID'];
                }
                if (isset($department['Code']) ) {
                    $data[$key]['code_icount'] = $department['Code'];
                }
                if (isset($department['Name']) && !empty($department['Name'])) {
                    $data[$key]['department_name'] = $department['Name'];
                }
                $data[$key]['id_parent'] = null;
                $data[$key]['from_icount'] = 1;
            }
            return $data;
        }
    }
}
