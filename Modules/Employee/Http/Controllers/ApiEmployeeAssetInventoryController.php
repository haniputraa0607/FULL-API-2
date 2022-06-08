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
       $user = AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
               ->select([
                   'id_asset_inventory_log',
                   'name_asset_inventory as name',
                   'code',
                   'status_asset_inventory as status',
                   'type_asset_inventory as type',
                   'asset_inventory_logs.created_at as date_create'
               ])
               ->orderby('asset_inventory_logs.created_at','desc')
               ->get();
       
        return MyHelper::checkGet($user);   
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
                    DB::raw('
                        sum(
                            CASE WHEN
                            asset_inventory_loans.status_loan = "Active" or asset_inventory_logs.status_asset_inventory != "Rejected" THEN 1 ELSE 0
                            END
                        ) as jumlah
                    ')
                ])
                ->groupby('id_asset_inventory')
                ->get();
        $available = array();
        foreach ($user as $value) {
            if($value['qty'] > $value['jumlah']){
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
            'attachment'=>$attachment
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
                ->paginate(10);
        return MyHelper::checkGet($available);   
   }
   public function detail_loan(Request $request) {
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
        $response = [
            'code' => $available->code,
            'name' => $available->name_asset_inventory,
            'start_date' => date('d/m/Y', strtotime($available->start_date_loan)),
            'end_date' => date('d/m/Y', strtotime($available->end_date_loan)),
            'long_loan' => $available->long.' '.$available->long_loan,
            'notes' => $available->notes,
            'attachment' => $available->attachment_foto,
        ];
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
