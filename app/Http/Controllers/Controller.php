<?php

namespace App\Http\Controllers;

use App\Http\Models\Transaction;
use Illuminate\Http\Request;

use App\Http\Models\Feature;
use App\Http\Models\UserFeature;
use App\Http\Models\User;
use App\Http\Models\Subdistrict;
use App\Http\Models\City;
use App\Http\Models\Province;
use App\Http\Models\Level;
use App\Http\Models\Configs;
use App\Http\Models\Courier;
use App\Http\Models\Setting;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Transaction\Entities\TransactionAcademyScheduleDayOff;
use Modules\Users\Entities\Role;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Lib\MyHelper;
use Modules\Recruitment\Entities\HairstylistSalesPayment;
use Modules\Employee\Entities\EmployeePerubahanData;
use Modules\Employee\Entities\DesingRequest;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	function __construct(){
      date_default_timezone_set('Asia/Jakarta');
    }
	
	function getFeatureControl(Request $request){
		$user = json_decode($request->user(), true);

		if($user['level'] == 'Super Admin'){
			$checkFeature = Feature::select('id_feature')->where('show_hide', 1)->get()->toArray();
		}else{
			$checkFeature = Role::join('roles_features', 'roles_features.id_role', 'roles.id_role')
							->join('features', 'features.id_feature', 'roles_features.id_feature')
							->where([
								['roles.id_role', $user['id_role']],
								['features.show_hide', 1]
							])
							->select('features.id_feature')->get()->toArray();
		}
		$result = [
			'status'  => 'success',
			'result'  => array_pluck($checkFeature, 'id_feature')
		];

      return response()->json($result);
    }
	
	function getFeature(Request $request){
	
		$checkFeature = Feature::where('show_hide', 1)->orderBy('order', 'asc')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => $checkFeature
		];
		return response()->json($result);
    }
	
	function getFeatureModule(Request $request){
	
		$checkFeature = Feature::select('feature_module')->where('show_hide', 1)->orderBy('order', 'asc')->groupBy('feature_module')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => $checkFeature
		];
		return response()->json($result);
    }
	
	function listCity(Request $request){
		$post = $request->json()->all();

		$query = City::select('*');
		if (isset($post['id_province'])) {
			$query->where('id_province', $post['id_province']);
		}

		$query = $query->get()->toArray();

		return [
    		'status' => 'success',
    		'result' => $query
    	];
	}

	function listProvince(Request $request){
		$query = (new Province)->newQuery();
		if($id_city=$request->json('id_city')){
			$query->whereHas('cities',function($query) use ($id_city){
				$query->where('id_city',$id_city);
			});
		}
		return MyHelper::checkGet($query->get()->toArray()); 
	}
	
	function listCourier(){
		$query = Courier::where('status','Active')->get()->toArray();
		return MyHelper::checkGet($query); 
	}
	
	function listRank(){
		$query = Level::get()->toArray();
		return MyHelper::checkGet($query); 
	}

	function getConfig(Request $request){
		$config = Configs::select('id_config')->where('is_active', '1')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => array_pluck($config, 'id_config')
		];

      return response()->json($result);
	}
	
	function uploadImageSummernote(Request $request) {
		$post = $request->json()->all();

		if (!file_exists('img/summernote/'.$post['type'])) {
			mkdir('img/summernote/'.$post['type'], 0777, true);
		}

        $upload = MyHelper::uploadPhotoSummerNote($request->json('image'), 'img/summernote/'.$post['type'].'/', null);
        
        if ($upload['status'] == "success") {
            $result = [
                'status' => 'success',
                'result' => [
                    'pathinfo' => config('url.storage_url_api').$upload['path'],
                    'path' => $upload['path']
                ]
            ];
        }
        else {
            $result = [
                'status' => 'fail'
            ];
        }

        return response()->json($result);
	}
	
    function deleteImageSummernote(Request $request) {
        if (MyHelper::deletePhoto($request->json('image'))) {
            $result = [
                'status' => 'success'
            ];
        }
        else {
            $result = [
                'status' => 'fail'
            ];
        }

        return response()->json($result);
    }

    function maintenance(){
        $get = Setting::where('key', 'maintenance_mode')->first();
        if($get){
            $dt = (array)json_decode($get['value_text']);
            $data['status'] = $get['value'];
            $data['message'] = $dt['message'];
            if($dt['image'] != ""){
                $data['image'] = config('url.storage_url_api').$dt['image'];
            }else{
                $data['image'] = config('url.storage_url_api').'img/maintenance/default.png';
            }
        }
        return view('webview.maintenance_mode', $data);
    }

    function listSubdistrict(Request $request){
		$post = $request->json()->all();

		$query = Subdistrict::join('cities', 'cities.id_city', 'subdistricts.id_city')
				->join('provinces', 'provinces.id_province', 'cities.id_province');

		if (isset($post['id_city'])) {
			$query = $query->where('subdistricts.id_city', $post['id_city']);
		}

		if (isset($post['keyword'])) {
			$query = $query->where(function ($q) use ($post){
				$q->where('subdistrict_name', 'like', '%' . $post['keyword'] . '%')
					->orWhere('city_name', 'like', '%' . $post['keyword'] . '%')
					->orWhere('province_name', 'like', '%' . $post['keyword'] . '%');
			});
		}

        if ($request->page) {
            $query = $query->paginate(10)->toArray();
        } else {
            $query = $query->get()->toArray();
        }

    	return [
    		'status' => 'success',
    		'result' => $query
    	];
	}

	public function postLog(Request $request)
	{
        $filename = 'log_post/log' . date('YmdHis') . '-' . rand(0,9) . '.json';
		file_put_contents($filename, json_encode($request->all()));
		return [
			'status' => 'success',
			'log_url' => url($filename),
			'result' => $request->all()
		];
	}

    public function getSidebarBadge(Request $request)
    {
        $academySchedule = $this->academy_student_schedule();
        $academyDayOff = $this->academy_student_day_off();
        $sutendAllNotif = $academySchedule + $academyDayOff;
    	return [
    		'status' => 'success',
    		'result' => [
    		'total_sales_payment' => $this->total_sales_payment(),
                'employee'            => $this->employee(),
                'asset_inventory'     => $this->asset_inventory(),
                'asset_inventory_return_pending'=>$this->asset_inventory_return_pending(),
                'asset_inventory_loan_pending'=>$this->asset_inventory_loan_pending(),
                'candidate_list' => $this->hs_candidate_list(),
                'academy_student_schedule' => $academySchedule,
                'academy_student_day_off' => $academyDayOff,
                'academy_student_notif' =>($sutendAllNotif == 0 ? null : $sutendAllNotif),
                'request-employee-perubahan-data'=> $this->request_employee_perubahan_data(),
                'employee-reimbursement'=> $this->request_employee_reimbursement(),
                'employee-cash-advance'=> $this->request_employee_cash_advance(),
                'partners'=> $this->partners(),
                'candidate-partners'=> $this->candidate_partners(),
                'request-update-partners'=> $this->request_update_partners(),
                'locations'=> $this->locations(),
                'candidate-locations'=> $this->candidate_locations(),    
                'projects'=> $this->projects(),
                'process-project'=> $this->process_project(),
                'employee_attendance' => $this->employee_attendance(),      
                'employee_attendance_outlet' => $this->employee_attendance_outlet(),      
                'employee_timeoff_overtime' => $this->employee_timeoff_overtime(),      
                'employee_attendance_pending' => $this->employee_attendance_pending(),      
                'employee_attendance_request' => $this->employee_attendance_request(),      
                'employee_attendance_outlet_pending' => $this->employee_attendance_outlet_pending(),      
                'employee_attendance_outlet_request' => $this->employee_attendance_outlet_request(),      
                'employee_time_off' => $this->employee_time_off(),      
                'employee_overtime' => $this->employee_overtime(),      
                'employee_changeshift' => $this->employee_changeshift(),      
                'hairstylist_schedule' => $this->hairstylist_schedule(),      
                'hairstylist_attendance_pending' => $this->hairstylist_attendance_pending(),      
                'hairstylist_time_off' => $this->hairstylist_time_off(),      
                'hairstylist_overtime' => $this->hairstylist_overtime(),      
                'request_product' => $this->request_product(),      
                'list_request_product' => $this->list_request_product(),      
                'list_request_asset' => $this->list_request_asset(),      
                'delivery_product' => $this->delivery_product(),      
                'hairstylist_request_update' => $this->hairstylist_request_update(),      
                'request_hairstylist' => $this->request_hairstylist(),      
                'employee_recruitment' => $this->employee_recruitment(),      
                'employee_candidate' => $this->employee_candidate(),      
                'list_request_employee' => $this->list_request_employee(),      
                'design_request' => $this->design_request(),      
    		],
    	];
    }
    public function total_sales_payment()
	{
                $total = HairstylistSalesPayment::where('status','Pending')->count();
                        if($total==0){
                            $total = null;
                        }
                return $total;
	}
    public function employee()
	{
                $total = $this->asset_inventory()+
                         $this->request_employee_perubahan_data()+
                         $this->request_employee_reimbursement()+
                         $this->request_employee_cash_advance()+
                         $this->employee_attendance()+
                         $this->employee_attendance_outlet()+
                         $this->employee_timeoff_overtime()+
                         $this->employee_recruitment();
                if($total==0){
                    $total = null;
                }
                return $total;
	}

    public function employee_recruitment(){
        $total =  $this->employee_candidate()+$this->list_request_employee();
        return $total;
    }
    public function employee_candidate()
	{
                $total = User::where(array(
                            "employees.status"=>"candidate",
                            "users.level"=>"Customer"
                            ))->wherenotnull('employees.status_approved')->join('employees','employees.id_user','users.id')->count();
                if($total==0){
                    $total = null;
                }
                return $total;
	}
    public function asset_inventory()
	{
                $total = $this->asset_inventory_loan_pending()+$this->asset_inventory_return_pending();
                if($total==0){
                    $total = null;
                }
                return $total;
	}
    public function asset_inventory_return_pending()
	{
                $total = \Modules\Employee\Entities\AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->join('asset_inventory_returns','asset_inventory_returns.id_asset_inventory','asset_inventorys.id_asset_inventory')
                ->where([
                        'type_asset_inventory'=>"Return",
                    ])->with(['user'])
                    ->where([
                        'status_asset_inventory'=>"Pending",
                        'type_asset_inventory'=>"Return",
                    ])->count();
                if($total==0){
                    $total = null;
                }
                return $total;
	}
    public function asset_inventory_loan_pending()
	{
               $total = \Modules\Employee\Entities\AssetInventoryLog::join('asset_inventorys','asset_inventorys.id_asset_inventory','asset_inventory_logs.id_asset_inventory')
                ->join('asset_inventory_categorys','asset_inventory_categorys.id_asset_inventory_category','asset_inventorys.id_asset_inventory_category')
                ->where([
                'status_asset_inventory'=>"Pending",
                'type_asset_inventory'=>"Loan",
                ])->with(['user'])->count();
               if($total==0){
                    $total = null;
                }
                return $total;
	}

    public function hs_candidate_list(){
        $total = UserHairStylist::whereNotIn('user_hair_stylist_status', ['Active', 'Inactive', 'Rejected'])->count();
        if($total==0){
            $total = null;
        }

        return $total;
    }

    public function academy_student_schedule(){
        $id = Transaction::join('transaction_academy','transaction_academy.id_transaction','=','transactions.id_transaction')
            ->join('transaction_academy_schedules','transaction_academy_schedules.id_transaction_academy','=','transaction_academy.id_transaction_academy')
            ->where('transaction_from', 'academy')
            ->where('transaction_payment_status', 'Completed')
            ->whereRaw('transaction_academy.transaction_academy_total_meeting = transaction_academy_schedules.meeting')
            ->pluck('transactions.id_transaction')->toArray();

        $total = Transaction::join('users','transactions.id_user','=','users.id')
            ->where('transaction_from', 'academy')
            ->where('transaction_payment_status', 'Completed')
            ->whereNotIn('transactions.id_transaction', $id)
            ->groupBy('transactions.id_user')
            ->select('id_user')->get();

        $total = count($total);
        if($total==0){
            $total = null;
        }

        return $total;
    }

    public function academy_student_day_off(){
        $total = TransactionAcademyScheduleDayOff::join('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy_schedule', 'transaction_academy_schedule_day_off.id_transaction_academy_schedule')
            ->whereNull('approve_date')
            ->whereNull('reject_date')
            ->join('users','transaction_academy_schedules.id_user','=','users.id')->count();

        if($total==0){
            $total = null;
        }

        return $total;
    }
    public function request_employee_perubahan_data(){
        $total =EmployeePerubahanData::where('employee_perubahan_datas.status','Pending')
                ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function request_employee_reimbursement(){
        $total = \Modules\Employee\Entities\EmployeeReimbursement::join('users','users.id','employee_reimbursements.id_user')
               ->join('product_icounts','product_icounts.id_product_icount','employee_reimbursements.id_product_icount')
               ->join('employees','employees.id_user','employee_reimbursements.id_user')
               ->where('employee_reimbursements.status','!=','Successed')
               ->where('employee_reimbursements.status','!=','Approved')
               ->where('employee_reimbursements.status','!=','Rejected')
                ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function request_employee_cash_advance(){
        $total = \Modules\Employee\Entities\EmployeeCashAdvance::join('users','users.id','employee_cash_advances.id_user')
               ->join('employees','employees.id_user','employee_cash_advances.id_user')
              ->join('product_icounts','product_icounts.id_product_icount','employee_cash_advances.id_product_icount') 
              ->where('employee_cash_advances.status','!=','Success')
               ->where('employee_cash_advances.status','!=','Approve')
               ->where('employee_cash_advances.status','!=','Rejected')
               ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function partners(){
        $total = $this->request_update_partners()+$this->candidate_partners();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function request_update_partners(){
        $total = \Modules\BusinessDevelopment\Entities\PartnersLog::with(['original_data'])->join('partners', 'partners_logs.id_partner', '=', 'partners.id_partner')
                ->where('update_status','process')
                ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function candidate_partners(){
        $total = \Modules\BusinessDevelopment\Entities\Partner::where(function($query){$query->where('status', 'Candidate')->orWhere('status', 'Rejected');})
                ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function locations(){
        $total = $this->candidate_locations();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function candidate_locations(){
        $total = \Modules\BusinessDevelopment\Entities\Location::with(['location_partner','location_city','location_step'])->where(function($query){$query->where('status', 'Candidate');})
                ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function projects(){
        $total = $this->process_project();
        if($total==0){
            $total = null;
        }
        return $total;
    }
    public function process_project(){
        $total = \Modules\Project\Entities\Project::where('projects.status','Process')
                    ->join('locations','locations.id_location','projects.id_location')
                    ->join('partners','partners.id_partner','projects.id_partner')
                    ->select('projects.*','partners.name as name_partner','locations.name as name_location')
                ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_attendance(){
        $total = $this->employee_attendance_pending()+$this->employee_attendance_request();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_attendance_outlet(){
        $total = $this->employee_attendance_outlet_pending()+$this->employee_attendance_outlet_request();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_timeoff_overtime(){
        $total = +$this->employee_time_off()+$this->employee_overtime()+$this->employee_changeshift();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_attendance_pending(){
        $total = \Modules\Employee\Entities\EmployeeAttendanceLog::where('status', 'Pending')
            ->whereDate('datetime','>=',date('Y-m-1'))
            ->whereDate('datetime','<=',date('Y-m-d'))
            ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_attendance_request(){
        $total = \Modules\Employee\Entities\EmployeeAttendanceRequest::where('status', 'Pending')
            ->whereDate('created_at','>=',date('Y-m-1'))
            ->whereDate('created_at','<=',date('Y-m-d'))
            ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_attendance_outlet_pending(){
        $total = \Modules\Employee\Entities\EmployeeOutletAttendanceLog::where('status', 'Pending')
            ->whereDate('datetime','>=',date('Y-m-1'))
            ->whereDate('datetime','<=',date('Y-m-d'))
            ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_attendance_outlet_request(){
        $total = \Modules\Employee\Entities\EmployeeOutletAttendanceRequest::where('status', 'Pending')
            ->whereDate('created_at','>=',date('Y-m-1'))
            ->whereDate('created_at','<=',date('Y-m-d'))
            ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_time_off(){
        $total = \Modules\Employee\Entities\EmployeeTimeOff::whereNull('reject_at')->whereNull('approve_by')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_overtime(){
        $total = \Modules\Employee\Entities\EmployeeOvertime::whereNull('reject_at')->whereNull('approve_by')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function employee_changeshift(){
        $total = \Modules\Employee\Entities\EmployeeChangeShift::where('status','Pending')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function hairstylist_schedule(){
        $total = $this->hairstylist_attendance_pending()+$this->hairstylist_time_off()+$this->hairstylist_overtime();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function hairstylist_attendance_pending(){
        $total = \Modules\Recruitment\Entities\HairstylistAttendanceLog::where('status', 'Pending')
            ->whereDate('datetime','>=',date('Y-m-1'))
            ->whereDate('datetime','<=',date('Y-m-d'))
            ->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function hairstylist_time_off(){
        $total = \Modules\Recruitment\Entities\HairStylistTimeOff::whereNull('reject_at')->whereNull('approve_by')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function hairstylist_overtime(){
        $total = \Modules\Recruitment\Entities\HairstylistOverTime::whereNull('reject_at')->whereNull('approve_by')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function request_product(){
        $total = $this->list_request_product()+$this->list_request_asset();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function list_request_product(){
        $total = \Modules\Product\Entities\RequestProduct::where('from','Product')->where(function($query){$query->where('status','Draft')->orWhere('status','Pending');})->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function list_request_asset(){
        $total = \Modules\Product\Entities\RequestProduct::where('from','Asset')->where(function($query){$query->where('status','Draft')->orWhere('status','Pending');})->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }    
    
    public function delivery_product(){
        $total = \Modules\Product\Entities\DeliveryProduct::where(function($query){$query->where('status','Draft')->orWhere('status','On Progress');})->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function hairstylist_request_update(){
        $total = \Modules\Recruitment\Entities\HairstylistUpdateData::whereNull('reject_at')->whereNull('approve_by')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function request_hairstylist(){
        $total = \Modules\Recruitment\Entities\RequestHairStylist::where('status','Request')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function list_request_employee(){
        $total = \Modules\Employee\Entities\RequestEmployee::where('status','Request')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }

    public function design_request(){
        $total = DesingRequest::where('status','<>','Provided')->count();
        if($total==0){
            $total = null;
        }
        return $total;
    }


}
