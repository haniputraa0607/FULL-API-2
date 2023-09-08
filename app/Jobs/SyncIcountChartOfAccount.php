<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Http\Models\Setting;
use App\Lib\Icount;
use Modules\ChartOfAccount\Entities\ChartOfAccount;


class SyncIcountChartOfAccount implements ShouldQueue
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
        $id_chart = $this->data['id_chart'];
        $data = Icount::ChartOfAccount($this->data['page']);
        if(isset($data) && isset($data['response'])){
            if(isset($data['response']['Message']) && $data['response']['Status']==0 && $data['response']['Message']=='Success'){
                if($data['response']['Meta']['Pagination']['CurrentPage']==1){
                    $index = 0;
                }else{
                    $index = count($id_chart);
                }
                foreach ($data['response']['Data'] as $value) {
                    $id_chart[$index] = $value['ChartOfAccountID'];
                    $query = ChartOfAccount::where(array('ChartOfAccountID'=>$value['ChartOfAccountID']))->first();
                    if($query){
                       $query->ChartOfAccountID = $value['ChartOfAccountID'];
                       $query->CompanyID        = $value['CompanyID'];
                       $query->GroupAccountID   = $value['GroupAccountID'];
                       $query->AccountNo        = $value['AccountNo'];
                       $query->Description      = $value['Description'];
                       $query->ParentID         = $value['ParentID'];
                       $query->IsChildest       = $value['IsChildest'];
                       $query->IsSuspended      = $value['IsSuspended'];
                       $query->IsBank           = $value['Description'];
                       $query->Type             = $value['Type'];
                       $query->IsDeleted        = $value['Description'];
                       $query->save();
                    }else{
                       $create =  ChartOfAccount::create($value);
                    }
                    $index++;
                }
                if($data['response']['Meta']['Pagination']['CurrentPage']<$data['response']['Meta']['Pagination']['LastPage']){
                    $new_page = $data['response']['Meta']['Pagination']['CurrentPage'] + 1;
                    SyncIcountChartOfAccount::dispatch(['page'=> $new_page,'id_chart' => $id_chart])->onConnection('syncicountchartofaccount');  
                    Setting::where('key','Sync Chart Icount')->update(['value' => 'process']);
                }else{
                    ChartOfAccount::whereIn('ChartOfAccountID',$id_chart)->update(['is_actived' => 'true']);
                    ChartOfAccount::whereNotIn('ChartOfAccountID',$id_chart)->update(['is_actived' => 'false']);
                    Setting::where('key','Sync Chart Icount')->update(['value' => 'finished']);
                }
            }
        }
    }
}
