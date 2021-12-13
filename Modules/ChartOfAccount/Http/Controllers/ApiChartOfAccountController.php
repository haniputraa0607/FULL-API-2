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

class ApiChartOfAccountController extends Controller
{
     public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }
    public function index() {
        $data = ChartOfAccount::all();
        return response()->json(['status' => 'success', 'result' => $data]);
    }
    public function sync() {
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
}
