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
use Modules\Recruitment\Entities\HairstylistCategoryLoan;
use Modules\Recruitment\Entities\HairstylistLoan;
use Modules\Recruitment\Entities\HairstylistLoanReturn;
use Modules\Recruitment\Http\Requests\loan\CreateLoan;


class ApiHairStylistLoanController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }
   

    public function createCategory(Request $request){
        $post = $request->json()->all();
        $save = HairstylistCategoryLoan::create($post);
        return response()->json(MyHelper::checkUpdate($save));
    }

    public function listCategory(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_hairstylist_category_loan'])){
            $data = HairstylistCategoryLoan::where('id_hairstylist_category_loan', $post['id_hairstylist_category_loan'])->first();
        }else{
            $data = HairstylistCategoryLoan::get()->toArray();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function updateCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_hairstylist_category_loan'])){
            $save = HairstylistCategoryLoan::where('id_hairstylist_category_loan', $post['id_hairstylist_category_loan'])->update([
                'hairstylist_category_name' => $post['hairstylist_category_name']
            ]);
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function deleteCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_hairstylist_category_loan'])){
            $save = HairstylistCategoryLoan::where('id_hairstylist_category_loan', $post['id_hairstylist_category_loan'])->delete();
            return response()->json(MyHelper::checkDelete($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
    public function hs(Request $request){
            $save = UserHairStylist::select('id_user_hair_stylist','user_hair_stylist_code','fullname')->get();
            return response()->json(MyHelper::checkGet($save));
      
    }
    public function index(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_hairstylist_loan'])){
            $data = HairstylistLoan::join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','hairstylist_loans.id_user_hair_stylist')
                    ->join('hairstylist_category_loans','hairstylist_category_loans.id_hairstylist_category_loan','hairstylist_loans.id_hairstylist_category_loan')
                    ->where('id_hairstylist_loan', $post['id_hairstylist_loan'])
                    ->with(['loan'])
                    ->select(['hairstylist_loans.*','user_hair_stylist.user_hair_stylist_code','user_hair_stylist.fullname','hairstylist_category_loans.name_category_loan'])
                    ->first();
        }else{
            $data = HairstylistLoan::join('user_hair_stylist','user_hair_stylist.id_user_hair_stylist','hairstylist_loans.id_user_hair_stylist')
                    ->join('hairstylist_category_loans','hairstylist_category_loans.id_hairstylist_category_loan','hairstylist_loans.id_hairstylist_category_loan')
                    ->select(['hairstylist_loans.*','user_hair_stylist.user_hair_stylist_code','user_hair_stylist.fullname','hairstylist_category_loans.name_category_loan'])
                    ->orderby('hairstylist_loans.created_at','DESC')
                    ->get()->toArray();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function create(CreateLoan $request){
        $post = $request->json()->all();
        $save = HairstylistLoan::create($post);
        $date = (int) MyHelper::setting('hs_income_cut_off_end_date', 'value')??25;
        $now = date('d', strtotime($post['effective_date']));
        $i = 0;
        $amount = $post['amount']/$post['installment'];
        if($date<$now){
            $i = 1;
            $post['installment'] = $post['installment']+1;
        }
        if($save){
            for ($i;$i<$post['installment'];$i++){
                HairstylistLoanReturn::create([
                    'id_hairstylist_loan'=>$save['id_hairstylist_loan'],
                    'return_date'=>date('Y-m-'.$date,strtotime("+".$i."month")),
                    'amount_return'=>$amount,
                    'status_return'=>"Pending"
                ]);
            }
        }
        return response()->json(MyHelper::checkUpdate($save));
    }
}