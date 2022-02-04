<?php

namespace Modules\ChartOfAccount\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use Modules\BusinessDevelopment\Entities\PartnersLog;
use Modules\BusinessDevelopment\Entities\Location;
use App\Lib\MyHelper;
use DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Models\City;
use App\Http\Models\Setting;
use Illuminate\Support\Facades\App;
use Modules\Brand\Entities\Brand;
use PDF;
use Storage;
use Modules\ChartOfAccount\Entities\ChartOfAccount;
use App\Lib\Icount;
use App\Jobs\SyncIcountChartOfAccount;

class ApiChartOfAccountController extends Controller
{
     public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }
    function index(Request $request) 
    {
    	$post = $request->json()->all();
        $data = ChartOfAccount::Select('chart_of_account.*');
        if ($request->json('rule')){
             $this->filterList($data,$request->json('rule'),$request->json('operator')??'and');
        }
        $data = $data->paginate($request->length ?: 10);
        //jika mobile di pagination
        if (!$request->json('web')) {
            $resultMessage = 'Data tidak ada';
            return response()->json(MyHelper::checkGet($data, $resultMessage));
        }
        else{
           
            return response()->json(MyHelper::checkGet($data));
        }
    }
   
    public function filterList($query,$rules,$operator='and'){
        $newRule=[];
        foreach ($rules as $var) {
            $rule=[$var['operator']??'=',$var['parameter']];
            if($rule[0]=='like'){
                $rule[1]='%'.$rule[1].'%';
            }
            $newRule[$var['subject']][]=$rule;
        }
        $where=$operator=='and'?'where':'orWhere';
        $subjects=['ChartOfAccountID','CompanyID','GroupAccountID','AccountNo','Description','ParentID','Type'];
         $i = 1;
        foreach ($subjects as $subject) {
            if($rules2=$newRule[$subject]??false){
                foreach ($rules2 as $rule) {
                    if($i<=1){
                    $query->where($subject,$rule[0],$rule[1]);
                    }else{
                    $query->$where($subject,$rule[0],$rule[1]);    
                    }
                    $i++;
                }
            }
        }
    }
    public function indexold() {
        $data = ChartOfAccount::all();
        return response()->json(['status' => 'success', 'result' => $data]);
    }
    public function sync() {
        $send = [
            'page' => 1,
            'id_chart' => null
        ];
        $sync_job = SyncIcountChartOfAccount::dispatch($send);
        return ['status' => 'success', 'messages' => ['Success to sync with ICount']]; 
        $icount = new Icount();
        $data = $icount->ChartOfAccount();
        if($data['response']['Status']==0 && $data['response']['Message']=='Success'){
            foreach ($data['response']['Data'] as $value) {
                $query = ChartOfAccount::where(array('ChartOfAccountID'=>$value['ChartOfAccountID']))->first();
                if($query){
                   $query->ChartOfAccountID = $value['ChartOfAccountID'];
                   $query->CompanyID        = $value['CompanyID'];
                   $query->GroupAccountID   = $value['GroupAccountID'];
                   $query->AccountNo        = $value['AccountNo'];
                   $query->Description      = $value['Description'];
                   $query->ParentID         = $value['ParentID'];
                   $query->IsChildest       = $value['IsChildest'];
                   $query->IsBank           = $value['Description'];
                   $query->Type             = $value['Type'];
                   $query->IsDeleted        = $value['Description'];
                   $query->save();
                }else{
                   $create =  ChartOfAccount::create($value);
                }
            }
            $data = ChartOfAccount::all();
        return response()->json(['status' => 'success', 'result' => $data]);
        }else{
            return response()->json(['status' => 'fail', 'message' => ['Fail']]);
        }
    }
    public function list() {
        $data = ChartOfAccount::all();
        return response()->json($data);
    }
}
