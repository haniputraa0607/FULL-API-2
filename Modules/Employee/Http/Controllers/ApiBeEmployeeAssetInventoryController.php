<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Http\Requests\AssetInventory\CreateCategoryAssetInventory;
use Modules\Employee\Http\Requests\AssetInventory\CreateAssetInventory;
use Modules\Employee\Http\Requests\AssetInventory\ApproveLoan;
use Modules\Employee\Http\Requests\AssetInventory\ApproveReturn;
use App\Http\Models\User;
use Session;
use DB;
use Modules\Employee\Entities\AssetInventory;
use Modules\Employee\Entities\AssetInventoryReturn;
use Modules\Employee\Entities\AssetInventoryLog;
use Modules\Employee\Entities\AssetInventoryLoan;
use Modules\Employee\Entities\CategoryAssetInventory;

class ApiBeEmployeeAssetInventoryController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/asset_inventory/"; 
    }
    public function list_category() {
        $user = CategoryAssetInventory::select([
            'id_asset_inventory_category',
            'name_category_asset_inventory'
        ])->get();
        return MyHelper::checkGet($user);
    }
    public function create_category(CreateCategoryAssetInventory $request) {
        $post = $request->all();
        $user = CategoryAssetInventory::create($post);
        return MyHelper::checkGet($user);
    }
    public function delete_category(Request $request) {
        $post = $request->all();
        $user = CategoryAssetInventory::where(array(
            'id_asset_inventory_category'=>$request->id_asset_inventory_category
        ))->delete();
        return MyHelper::checkGet($user);
    }
    
    //asset
    function code(){
        $s = 1;
        $nom = AssetInventory::count();
        for ($x = 0; $x < $s; $x++) {
            $nom++;
            if($nom < 10 ){
                $nom = '000'.$nom;
            }elseif($nom < 100 && $nom >= 10){
                $nom = '00'.$nom;
            }elseif($nom < 1000 && $nom >= 100){
                $nom = '0'.$nom;
            }
            $no = $nom;
            $cek = AssetInventory::where('code',$no)->first();
            if($cek){
                $s++;
            }
        }
        return $no;
    }
    public function create(CreateAssetInventory $request) {
        $post = $request->all();
        $post['code'] = $this->code();
        $user = AssetInventory::create($post);
        return MyHelper::checkGet($user);
    }
    public function list() {
        $user = AssetInventory::get();
        return MyHelper::checkGet($user);
    }
    public function list_loan_pending() {
        $user = AssetInventoryLog::where([
            'status_asset_inventory'=>"Pending",
            'type_asset_inventory'=>"Loan",
        ])->get();
        return MyHelper::checkGet($user);
    }
    public function list_loan() {
        $user = AssetInventoryLog::where([
            'type_asset_inventory'=>"Loan",
        ])->where(
            'status_asset_inventory','!=',"Pending",
        )->get();
        return MyHelper::checkGet($user);
    }
    public function approve_loan(ApproveLoan $request) {
         $post = $request->all();
       if(!empty($post['attachment'])){
           $file = $request->file('attachment');
            $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, $file->getClientOriginalExtension());
            if (isset($upload['status']) && $upload['status'] == "success") {
                    $attachment = $upload['path'];
                } else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload file']
                    ];
                    return $result;
                }
            }
        $available = AssetInventoryLog::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                    ->where([
                    'asset_inventory_logs.id_asset_inventory_log'=>$request->id_asset_inventory_log
                    ])
                    ->select([
                      'asset_inventory_logs.*'
                    ])
                    ->with('loan')
                    ->first();
        $available['id_approved'] = Auth::user()->id;
        $available['date_action'] = date('Y-m-d H:i:s');
        $available['status_asset_inventory'] = $request->status_asset_inventory;
        $available['notes'] = $request->notes;
        $available['attachment'] = $attachment;
        if($request->status_asset_inventory == "Approved"){
            $loan = AssetInventoryLoan::where([
                'id_asset_inventory_log'=>$request->id_asset_inventory_log
            ])->update([
                'status_loan'=>"Active",
                'start_date_loan'=>date('Y-m-d'),
                'end_date_loan'=>date('Y-m-d', strtotime("+".$available['loan']['long'].$available['loan']['long_loan'])),
            ]);
        }else{
            $loan = AssetInventoryLoan::where([
                'id_asset_inventory_log'=>$request->id_asset_inventory_log
            ])->update([
                'status_loan'=>"Inactive"
            ]);
        }
        $available->save();
        $available = AssetInventoryLog::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                    ->where([
                    'asset_inventory_logs.id_asset_inventory_log'=>$request->id_asset_inventory_log
                    ])
                    ->select([
                      'asset_inventory_logs.*'
                    ])
                    ->with('loan')
                    ->first();
        return MyHelper::checkGet($available);
    }
    
    //return 
    public function list_return_pending() {
        $user = AssetInventoryLog::where([
            'status_asset_inventory'=>"Pending",
            'type_asset_inventory'=>"Return",
        ])->get();
        return MyHelper::checkGet($user);
    }
    public function list_return() {
        $user = AssetInventoryLog::where([
            'type_asset_inventory'=>"Return",
        ])->where(
            'status_asset_inventory','!=',"Pending",
        )->get();
        return MyHelper::checkGet($user);
    }
    public function approve_return(ApproveReturn $request) {
         $post = $request->all();
       if(!empty($post['attachment'])){
           $file = $request->file('attachment');
            $upload = MyHelper::uploadFile($request->file('attachment'), $this->saveFile, $file->getClientOriginalExtension());
            if (isset($upload['status']) && $upload['status'] == "success") {
                    $attachment = $upload['path'];
                } else {
                    $result = [
                        'status'   => 'fail',
                        'messages' => ['fail upload file']
                    ];
                    return $result;
                }
            }
        $available = AssetInventoryLog::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                    ->where([
                    'asset_inventory_logs.id_asset_inventory_log'=>$request->id_asset_inventory_log
                    ])
                    ->select([
                      'asset_inventory_logs.*'
                    ])
                    ->first();
        $available['id_approved'] = Auth::user()->id;
        $available['date_action'] = date('Y-m-d H:i:s');
        $available['status_asset_inventory'] = $request->status_asset_inventory;
        $available['notes'] = $request->notes;
        $available['attachment'] = $attachment;
        $return = AssetInventoryReturn::where('id_asset_inventory_log',$request->id_asset_inventory_log)->first();
        if($request->status_asset_inventory == "Approved"){
            $loan = AssetInventoryLoan::where([
                'id_asset_inventory_loan'=>$return->id_asset_inventory_loan
            ])->update([
                 'status_loan'=>"Inactive"
            ]);
        }else{
            $loan = AssetInventoryLoan::where([
                'id_asset_inventory_loan'=>$return->id_asset_inventory_loan
            ])->update([
                'status_loan'=>"Active"
            ]);
        }
        $available->save();
        $available = AssetInventoryLog::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                    ->where([
                    'asset_inventory_logs.id_asset_inventory_log'=>$request->id_asset_inventory_log
                    ])
                    ->select([
                      'asset_inventory_logs.*'
                    ])
                    ->with('loan')
                    ->first();
        return MyHelper::checkGet($available);
    }
}