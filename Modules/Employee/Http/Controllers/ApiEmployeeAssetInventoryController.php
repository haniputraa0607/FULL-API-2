<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Entities\Employee;
use Modules\Employee\Entities\EmployeeFamily;
use Modules\Employee\Entities\EmployeeMainFamily;
use Modules\Employee\Entities\EmployeeEducation;
use Modules\Employee\Entities\EmployeeEducationNonFormal;
use Modules\Employee\Entities\EmployeeJobExperience;
use Modules\Employee\Entities\EmployeeQuestions;
use Modules\Employee\Http\Requests\Reimbursement\Create;
use Modules\Employee\Http\Requests\Reimbursement\Detail;
use Modules\Employee\Http\Requests\Reimbursement\Update;
use Modules\Employee\Http\Requests\Reimbursement\Delete;
use Modules\Employee\Http\Requests\Reimbursement\history;
use Modules\Employee\Http\Requests\InputFile\CreateFile;
use Modules\Employee\Http\Requests\InputFile\UpdateFile;
use Modules\Employee\Http\Requests\update_pin;
use Modules\Employee\Http\Requests\AssetInventory\CreateLoan;
use Modules\Employee\Http\Requests\AssetInventory\CreateReturn;
use App\Http\Models\User;
use Session;
use DB;
use Modules\Employee\Entities\CategoryAssetInventory;
use Modules\Employee\Entities\AssetInventory;
use Modules\Employee\Entities\AssetInventoryLoan;
use Modules\Employee\Entities\AssetInventoryLog;
use Modules\Employee\Entities\AssetInventoryReturn;

class ApiEmployeeAssetInventoryController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/asset_inventory/log/"; 
        $this->saveFileLoan = "document/asset_inventory/loan/"; 
        $this->saveFileReturn = "document/asset_inventory/return/"; 
    }
   public function history() {
       $user = AssetInventoryLog::leftjoin('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
               ->select([
                   'id_asset_inventory_log',
                   'name_asset_inventory as name',
                   'code',
                   'status_asset_inventory as status',
                   'type_asset_inventory as type',
                   'qty_logs as qty',
                   'notes',
                   'asset_inventory_logs.created_at as date_create'
               ])
               ->orderby('asset_inventory_logs.created_at','desc')
               ->get();
       
        return MyHelper::checkGet($user);   
   }
   public function detail_history(Request $request) {
       if(!isset($request->id_asset_inventory_log)){
           return array(
               'status'=>'fail',
               'message'=>[
                   'Data Incomplete'
               ]
           );
       }
       $available = AssetInventoryLog::leftjoin('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
               ->where('id_asset_inventory_log',$request->id_asset_inventory_log)
               ->select([
                   'id_asset_inventory_log',
                   'name_asset_inventory as name',
                   'code',
                   'status_asset_inventory as status',
                   'type_asset_inventory as type',
                   'qty_logs as qty',
                   'notes',
                   'date_action',
                   'asset_inventory_logs.created_at as date_create'
               ])
               ->first();
       if($available){
           $url = null;  
           if($available->type == "Loan"){
               $loan = AssetInventoryLoan::where('id_asset_inventory_log',$available->id_asset_inventory_log)->first();
               if(isset($loan->attachment)){
                   $url =  env('STORAGE_URL_API').$loan->attachment_foto;
               }
           }else{
               $loan = AssetInventoryReturn::where('id_asset_inventory_log',$available->id_asset_inventory_log)->first();
               if(isset($loan->attachment)){
                   $url =  env('STORAGE_URL_API').$loan->attachment_foto;
               }
           }
           $date_action = null;
           if(isset($available->date_action)){
               $date_action = date('d F Y', strtotime($available->date_action));
           }
        $response = [
            'code' => $available->code,
            'name' => $available->name,
            'status' => $available->status,
            'type' => $available->type,
            'qty' => $available->qty,
            'notes' => $available->notes,
            'date_action' =>$date_action ,
            'date_create' => date('d F Y', strtotime($available->date_create)),
            'attachment' => $url,
        ];
        
        }
        return MyHelper::checkGet($response);   
   }
   
   public function category_asset() {
       $user = CategoryAssetInventory::select([
            'id_asset_inventory_category',
            'name_category_asset_inventory'
        ])->get();
        return MyHelper::checkGet($user);   
   }
   public function available_asset(Request $request) {
       $user = AssetInventory::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->leftjoin('asset_inventory_logs','asset_inventory_logs.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->where([
                    'id_asset_inventory_category'=>$request->id_asset_inventory_category
                ])->select([
                    'asset_inventorys.id_asset_inventory',
                    'asset_inventorys.name_asset_inventory',
                    'asset_inventorys.code',
                    'asset_inventorys.id_asset_inventory_category',
                    'asset_inventorys.qty',
                    'asset_inventorys.available'
                ])
                ->groupby('asset_inventorys.id_asset_inventory')
                ->get();
        $available = array();
        foreach ($user as $value) {
            if($value['available'] > 0){
                $available[]=$value;
            }
        }
        return MyHelper::checkGet($available);   
   }
   //loan
   public function create_loan(CreateLoan $request) {
       $post = $request->all();
      if(!empty($post['attachment'])){
            $file = $request->file('attachment');
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $attachment = MyHelper::encodeImage($file);
            $upload = MyHelper::uploadFile($attachment, $this->saveFileLoan, $ext, strtotime(date('Y-m-d H-i-s')));
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
        $logs = AssetInventoryLog::create([
            'id_user'=>Auth::user()->id,
            'id_asset_inventory'=>$request->id_asset_inventory,
        ]);
        
        $loan = AssetInventoryLoan::create([
            'id_asset_inventory_log'=>$logs->id_asset_inventory_log,
            'id_asset_inventory'=>$request->id_asset_inventory,
            'long'=>$request->long,
            'long_loan'=>$request->long_loan,
            'notes'=>$request->notes,
            'attachment'=>$attachment??null
        ]);
        $inven = AssetInventory::where('id_asset_inventory',$request->id_asset_inventory)->first();
        $qty = $request->qty_loan??1;
        $ava = $inven->available-$qty;
        $inven = AssetInventory::where('id_asset_inventory',$request->id_asset_inventory)->update([
            'available'=>$ava
        ]);
        $available = AssetInventoryLog::leftjoin('asset_inventory_loans','asset_inventory_loans.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                ->where(array('asset_inventory_logs.id_asset_inventory_log'=>$logs->id_asset_inventory_log))
                ->first();
        return MyHelper::checkGet($available);   
   }
   public function loan_asset(Request $request) {
        $available = AssetInventoryLoan::join('asset_inventory_logs','asset_inventory_logs.id_asset_inventory_log','asset_inventory_loans.id_asset_inventory_log')
                ->join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_loans.id_asset_inventory')
                ->where([
                'status_loan'=>"Active",
                'id_user'=>Auth::user()->id
                ])
                ->select([
                    'id_asset_inventory_loan',
                    'asset_inventorys.name_asset_inventory',
                    'asset_inventorys.code',
                    'qty_logs as qty'
                ])
                ->get();
        return MyHelper::checkGet($available);   
   }
   public function detail_loan(Request $request) {
       if(!isset($request->id_asset_inventory_loan)){
           return array(
               'status'=>'fail',
               'message'=>[
                   'Data Incomplete'
               ]
           );
       }
        $available = AssetInventoryLoan::join('asset_inventory_logs','asset_inventory_logs.id_asset_inventory_log','asset_inventory_loans.id_asset_inventory_log')
                ->join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_loans.id_asset_inventory')
                ->where([
                'status_loan'=>"Active",
                'id_asset_inventory_loan'=>$request->id_asset_inventory_loan,
                'id_user'=>Auth::user()->id
                ])
                ->select([
                    'asset_inventory_loans.*',
                    'asset_inventorys.*',
                    'asset_inventory_logs.attachment as attachment_foto',
                ])
                ->first();
        $response = [];
        if($available){
             if(isset($available->attachment_foto)){
                    $available->attachment_foto= env('STORAGE_URL_API').$available->attachment_foto;
                }
        $response = [
            'code' => $available->code,
            'name' => $available->name_asset_inventory,
            'start_date' => date('d F Y', strtotime($available->start_date_loan)),
            'end_date' => date('d F Y', strtotime($available->end_date_loan)),
            'long_loan' => $available->long.' '.$available->long_loan,
            'notes' => $available->notes,
            'attachment' => $available->attachment_foto,
        ];
        
        }
        return MyHelper::checkGet($response);   
   }
   public function loan_list_return(Request $request) {
        $available = AssetInventoryLoan::join('asset_inventory_logs','asset_inventory_logs.id_asset_inventory_log','asset_inventory_loans.id_asset_inventory_log')
                ->join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_loans.id_asset_inventory')
                ->where([
                'status_loan'=>"Active",
                'id_user'=>Auth::user()->id
                ])
                ->select([
                    'id_asset_inventory_loan',
                    'asset_inventorys.name_asset_inventory',
                    'asset_inventorys.code'
                ])
                ->get();
        return MyHelper::checkGet($available);   
   }
   
   //return 
   public function create_return(CreateReturn $request) {
       $post = $request->all();
       if(!empty($post['attachment'])){
            $file = $request->file('attachment');
            $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
            $attachment = MyHelper::encodeImage($file);
            $upload = MyHelper::uploadFile($attachment, $this->saveFileReturn, $ext, strtotime(date('Y-m-d H-i-s')));
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
        $loan = AssetInventoryLoan::where('id_asset_inventory_loan',$request->id_asset_inventory_loan)->first();
        if(empty($loan)){
            $result = [
                        'status'   => 'fail',
                        'messages' => ['Loan not found']
                    ];
                    return $result;
        }
        $logs = AssetInventoryLog::create([
            'id_user'=>Auth::user()->id,
            'id_asset_inventory'=>$loan->id_asset_inventory,
            'type_asset_inventory'=>"Return"
        ]);
        $return = AssetInventoryReturn::create([
            'id_asset_inventory_log'=>$logs->id_asset_inventory_log,
            'id_asset_inventory'=>$loan->id_asset_inventory,
            'id_asset_inventory_loan'=>$request->id_asset_inventory_loan,
            'date_return'=>date('Y-m-d'),
            'notes'=>$request->notes,
            'attachment'=>$attachment
        ]);
        $available = AssetInventoryLog::leftjoin('asset_inventory_returns','asset_inventory_returns.id_asset_inventory_log','asset_inventory_logs.id_asset_inventory_log')
                ->where(array('asset_inventory_logs.id_asset_inventory_log'=>$logs->id_asset_inventory_log))
                ->first();
        return MyHelper::checkGet($available);   
   }
}
