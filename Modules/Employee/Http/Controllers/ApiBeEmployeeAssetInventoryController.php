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
        $post['available'] = $request->qty;
        $user = AssetInventory::create($post);
        return MyHelper::checkGet($user);
    }
    public function delete(Request $request) {
        $post = $request->all();
        $user = AssetInventory::where(array(
            'id_asset_inventory'=>$request->id_asset_inventory
        ))->delete();
        return MyHelper::checkGet($user);
    }
    public function list() {
        $user = AssetInventory::join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')->get();
        return MyHelper::checkGet($user);
    }
    public function list_loan_pending() {
        $user = AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->where([
            'status_asset_inventory'=>"Pending",
            'type_asset_inventory'=>"Loan",
        ])->with(['user'])->get();
        return MyHelper::checkGet($user);
    }
    public function list_loan() {
        $user = AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->where([
            'type_asset_inventory'=>"Loan",
        ])->with(['user'])->where(
            'status_asset_inventory','!=',"Pending",
        )->get();
        return MyHelper::checkGet($user);
    }
    public function detail_loan(Request $request) {
        $user = AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_loans','asset_inventory_loans.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->leftjoin('users','users.id','asset_inventory_logs.id_approved')
                ->where([
                    'asset_inventory_logs.id_asset_inventory_log'=>$request->id_asset_inventory_log,
                ])->select('asset_inventory_logs.*',
                    'asset_inventorys.*',
                    'asset_inventory_loans.*',
                    'asset_inventory_categorys.*',
                    'users.*',
                    'asset_inventory_logs.attachment as attachment_logs',)
                ->first();
        return MyHelper::checkGet($user);
    }
    public function approve_loan(ApproveLoan $request) {
         $post = $request->all();
       if(!empty($post['attachment'])){
                    $upload = MyHelper::uploadFile($post['attachment'],$this->saveFile, $post['ext']);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $path = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document']]);
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
        $available['id_approved'] = $post['id_user_approved'] ?? Auth::user()->id;
        $available['date_action'] = date('Y-m-d H:i:s');
        $available['status_asset_inventory'] = $request->status_asset_inventory;
        $available['notes'] = $request->notes;
        $available['attachment'] = $path ?? null;
        if($request->status_asset_inventory == "Approved"){
            $loan = AssetInventoryLoan::where([
                'id_asset_inventory_log'=>$request->id_asset_inventory_log
            ])->update([
                'status_loan'=>"Active",
                'start_date_loan'=>date('Y-m-d'),
                'end_date_loan'=>date('Y-m-d', strtotime("+".$available['loan']['long'].$available['loan']['long_loan'])),
            ]);
        }else{
            $inven = AssetInventory::where('id_asset_inventory',$available->id_asset_inventory)->first();
            $qty = $available->qty_logs??1;
            $ava = $inven->available+$qty;
            $inven = AssetInventory::where('id_asset_inventory',$available->id_asset_inventory)->update([
                'available'=>$ava
            ]);
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
        $user = AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->where([
                    'status_asset_inventory'=>"Pending",
                   'type_asset_inventory'=>"Return",
               ])->with(['user'])->get();
        return MyHelper::checkGet($user);
    }
    public function list_return() {
        $user = AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->join('asset_inventory_returns','asset_inventory_returns.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->where([
            'type_asset_inventory'=>"Return",
        ])->with(['user'])
        ->where(
            'status_asset_inventory','!=',"Pending",
        )->get();
        return MyHelper::checkGet($user);
    }
    public function detail_return(Request $request) {
        $user = AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->leftjoin('asset_inventory_returns','asset_inventory_returns.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                ->leftjoin('users','users.id','asset_inventory_logs.id_approved')
                ->where([
                        'asset_inventory_logs.id_asset_inventory_log'=>$request->id_asset_inventory_log,
                    ])
                ->select([
                    'asset_inventory_logs.*',
                    'asset_inventorys.*',
                    'asset_inventory_returns.*',
                    'asset_inventory_categorys.*',
                    'users.*',
                    'asset_inventory_logs.attachment as attachment_logs',
                ])
                ->first();
        return MyHelper::checkGet($user);
    }
    public function approve_return(ApproveReturn $request) {
       $post = $request->all();
       if(!empty($post['attachment'])){
                   $upload = MyHelper::uploadFile($post['attachment'],$this->saveFile, $post['ext']);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                       $path = $upload['path'];
                    }else {
                        return response()->json(['status' => 'fail', 'messages' => ['Failed upload document']]);
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
        $available['id_approved'] = $post['id_user_approved'] ?? Auth::user()->id;
        $available['date_action'] = date('Y-m-d H:i:s');
        $available['status_asset_inventory'] = $request->status_asset_inventory;
        $available['notes'] = $request->notes;
        $available['attachment'] = $path ?? null;
        $return = AssetInventoryReturn::where('id_asset_inventory_log',$request->id_asset_inventory_log)->first();
        if($request->status_asset_inventory == "Approved"){
            $inven = AssetInventory::where('id_asset_inventory',$available->id_asset_inventory)->first();
            $qty = $available->qty_logs??1;
            $ava = $inven->available+$qty;
            $inven = AssetInventory::where('id_asset_inventory',$available->id_asset_inventory)->update([
                'available'=>$ava
            ]);
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
