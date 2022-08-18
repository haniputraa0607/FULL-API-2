<?php

namespace Modules\Recruitment\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Jobs\UpdateScheduleHSJob;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\Disburse\Entities\BankAccount;
use Modules\ProductService\Entities\ProductHairstylistCategory;
use Modules\Recruitment\Entities\HairstylistCategory;
use Modules\Recruitment\Entities\UserHairStylist;
use Modules\Recruitment\Entities\UserHairStylistDocuments;
use Modules\Recruitment\Entities\HairstylistSchedule;	
use Modules\Recruitment\Entities\HairstylistScheduleDate;
use Modules\Outlet\Entities\OutletBox;
use App\Http\Models\LogOutletBox;
use Modules\Recruitment\Entities\UserHairStylistTheory;
use Modules\Recruitment\Http\Requests\user_hair_stylist_create;
use Image;
use DB;
use Modules\Recruitment\Entities\UserHairStylistExperience;
use Modules\Transaction\Entities\TransactionHomeService;
use Modules\Transaction\Entities\TransactionProductService;
use App\Http\Models\Transaction;
use File;
use Storage;
use Modules\Recruitment\Entities\HairstylistIncome;
use Modules\Recruitment\Entities\HairstylistIncomeDetail;

class ApiHairStylistIncomeController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        $this->mitra 			= "Modules\Recruitment\Http\Controllers\ApiMitra";
    }
    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function index(Request $request) {
        $post = $request->all();
        $hairstylist = HairstylistIncome::where(
            "hairstylist_incomes.status",'!=',"Draft"
            )->where(
            "hairstylist_incomes.status",'!=',"Cancelled"
            )
            ->join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','hairstylist_incomes.id_user_hair_stylist')
            ->join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet');
        if(isset($post['rule']) && !empty($post['rule'])){
            $rule = 'and';
            if(isset($post['operator'])){
                $rule = $post['operator'];
            }
            if($rule == 'and'){
                foreach ($post['rule'] as $condition){
                    if(isset($condition['subject'])){
                        if($condition['subject']=='id_outlet'){
                            $hairstylist = $hairstylist->where('outlets'.$condition['subject'], $condition['parameter']);
                        }else{
                            $hairstylist = $hairstylist->where($condition['subject'], $condition['parameter']);
                        }
                        
                    }
                }
            }else{
                $hairstylist = $hairstylist->where(function ($q) use ($post){
                    foreach ($post['rule'] as $condition){
                        if(isset($condition['subject'])){
                                 if($condition['operator'] == 'like'){
                                      $q->orWhere($condition['subject'], 'like', '%'.$condition['parameter'].'%');
                                 }else{
                                     if($condition['subject']=='id_outlet'){
                                            $q->orWhere('outlets'.$condition['subject'], $condition['parameter']);
                                        }else{
                                             $q->orWhere($condition['subject'], $condition['parameter']);
                                        }
                                 }
                        }   
                    }
                });
            }
        }
            $hairstylist = $hairstylist->orderBy('hairstylist_incomes.periode', 'desc')
                            ->select(
                            'id_hairstylist_income',
                            'hairstylist_incomes.periode',
                            'hairstylist_incomes.start_date',
                            'hairstylist_incomes.end_date',
                            'hairstylist_incomes.amount',
                            'outlets.outlet_name',
                            'outlets.id_outlet',
                            'user_hair_stylist.fullname',
                            'user_hair_stylist.email',
                            'user_hair_stylist.phone_number',
                            'hairstylist_incomes.status',
                        )
                        ->paginate($request->length ?: 10);
        return MyHelper::checkGet($hairstylist);
   }
   public function detail(Request $request){
       $id = $request->id_hairstylist_income??0;
       $outlet = HairstylistIncome::where(array(
           'id_hairstylist_income'=>$id,
           ))
        ->join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','hairstylist_incomes.id_user_hair_stylist')
        ->join('outlets','outlets.id_outlet','user_hair_stylist.id_outlet')
        ->select(
                    'id_hairstylist_income',
                    'hairstylist_incomes.periode',
                    'hairstylist_incomes.start_date',
                    'hairstylist_incomes.end_date',
                    'hairstylist_incomes.amount',
                    'outlets.outlet_name',
                    'outlets.id_outlet',
                    'user_hair_stylist.fullname',
                    'user_hair_stylist.email',
                    'user_hair_stylist.phone_number',
                    'hairstylist_incomes.status',
                )
           ->with('hairstylist_income_details')
           ->first();
       return MyHelper::checkGet($outlet);
   }
   public function outlet(){
       $outlet = Outlet::where(array(
           'type'=>'Outlet',
           'outlet_status'=>'Active',
           ))->orderby('outlet_name','asc')
           ->select('id_outlet','outlet_name')
           ->get();
       return MyHelper::checkGet($outlet);
   }
}
