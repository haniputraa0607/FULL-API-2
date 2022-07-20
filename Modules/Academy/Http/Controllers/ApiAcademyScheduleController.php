<?php

namespace Modules\Academy\Http\Controllers;

use App\Jobs\SyncronPlasticTypeOutlet;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Outlet;
use App\Http\Models\OutletDoctor;
use App\Http\Models\OutletDoctorSchedule;
use App\Http\Models\OutletHoliday;
use App\Http\Models\UserOutletApp;
use App\Http\Models\Holiday;
use App\Http\Models\DateHoliday;
use App\Http\Models\OutletPhoto;
use App\Http\Models\City;
use App\Http\Models\User;
use App\Http\Models\UserOutlet;
use App\Http\Models\Configs;
use App\Http\Models\OutletSchedule;
use App\Http\Models\Setting;
use App\Http\Models\OauthAccessToken;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Modules\Academy\Entities\ProductAcademyTheoryCategory;
use Modules\Academy\Entities\Theory;
use Modules\Academy\Entities\TheoryCategory;
use Modules\Outlet\Entities\DeliveryOutlet;
use Modules\Outlet\Entities\OutletBox;
use Modules\POS\Http\Requests\reqMember;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use Modules\Franchise\Entities\UserFranchise;
use Modules\Franchise\Entities\UserFranchiseOultet;
use Modules\Outlet\Entities\OutletScheduleUpdate;

use App\Imports\ExcelImport;
use App\Imports\FirstSheetOnlyImport;

use App\Lib\MyHelper;
use Modules\Transaction\Entities\TransactionAcademy;
use Modules\Transaction\Entities\TransactionAcademyInstallment;
use Modules\Transaction\Entities\TransactionAcademySchedule;
use Modules\Transaction\Entities\TransactionAcademyScheduleDayOff;
use Modules\Transaction\Entities\TransactionAcademyScheduleTheory;
use Modules\Transaction\Entities\TransactionAcademyScheduleTheoryCategory;
use Validator;
use Hash;
use DB;
use Mail;
use Excel;
use Storage;

use Modules\Brand\Entities\BrandOutlet;
use Modules\Brand\Entities\Brand;

use Modules\Outlet\Http\Requests\Outlet\Upload;
use Modules\Outlet\Http\Requests\Outlet\Update;
use Modules\Outlet\Http\Requests\Outlet\UpdateStatus;
use Modules\Outlet\Http\Requests\Outlet\UpdatePhoto;
use Modules\Outlet\Http\Requests\Outlet\UploadPhoto;
use Modules\Outlet\Http\Requests\Outlet\Create;
use Modules\Outlet\Http\Requests\Outlet\Delete;
use Modules\Outlet\Http\Requests\Outlet\DeletePhoto;
use Modules\Outlet\Http\Requests\Outlet\Nearme;
use Modules\Outlet\Http\Requests\Outlet\Filter;
use Modules\Outlet\Http\Requests\Outlet\OutletList;
use Modules\Outlet\Http\Requests\Outlet\OutletListOrderNow;

use Modules\Outlet\Http\Requests\UserOutlet\Create as CreateUserOutlet;
use Modules\Outlet\Http\Requests\UserOutlet\Update as UpdateUserOutlet;

use Modules\Outlet\Http\Requests\Holiday\HolidayStore;
use Modules\Outlet\Http\Requests\Holiday\HolidayEdit;
use Modules\Outlet\Http\Requests\Holiday\HolidayUpdate;
use Modules\Outlet\Http\Requests\Holiday\HolidayDelete;

use Modules\PromoCampaign\Entities\PromoCampaignPromoCode;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use App\Http\Models\Transaction;

use App\Jobs\SendOutletJob;
use function Clue\StreamFilter\fun;

class ApiAcademyScheduleController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');

        $this->autocrm   = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    public function listUserAcademy(Request $request)
    {
        $list = Transaction::where('transaction_from', 'academy')
            ->join('users','transactions.id_user','=','users.id')
            ->with('user')
            ->select(
                'users.*', DB::raw('(Select transaction_date from transactions where transaction_from = "academy" and transactions.id_user = users.id order by transaction_date desc limit 1) as last_date_transaction')
            )
            ->groupBy('transactions.id_user');

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->count();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                '',
                'name',
                'phone',
                'email',
                'last_date_transaction'
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $list->orderBy($colname, $column['dir']);
                }
            }
        }else{
            $list->orderBy('last_date_transaction', 'DESC');
        }

        if ($request->page) {
            $list = $list->paginate($request->length ?: 15);
            $list->each(function($item) {
                $item->images = array_map(function($item) {
                    return config('url.storage_url_api').$item;
                }, json_decode($item->images) ?? []);
            });
            $list = $list->toArray();
            if (is_null($countTotal)) {
                $countTotal = $list['total'];
            }
            // needed by datatables
            $list['recordsTotal'] = $countTotal;
            $list['recordsFiltered'] = $list['total'];

            foreach($list['data']??[] as $key=>$dt){
                $id = Transaction::join('transaction_academy','transaction_academy.id_transaction','=','transactions.id_transaction')
                    ->join('transaction_academy_schedules','transaction_academy_schedules.id_transaction_academy','=','transaction_academy.id_transaction_academy')
                    ->where('transaction_from', 'academy')
                    ->where('transaction_payment_status', 'Completed')
                    ->whereRaw('transaction_academy.transaction_academy_total_meeting = transaction_academy_schedules.meeting')
                    ->where('transactions.id_user', $dt['id'])
                    ->pluck('transactions.id_transaction')->toArray();

                $total = Transaction::join('users','transactions.id_user','=','users.id')
                    ->where('transaction_from', 'academy')
                    ->where('transaction_payment_status', 'Completed')
                    ->whereNotIn('transactions.id_transaction', $id)
                    ->where('transactions.id_user', $dt['id'])
                    ->pluck('id_transaction')->toArray();

                $count = array_unique($total);
                $list['data'][$key]['status_schedule_not_setting'] = count($count);
            }
        } else {
            $list = $list->get();
        }
        return MyHelper::checkGet($list);
    }

    public function filterList($model, $rule, $operator = 'and')
    {
        $new_rule = [];
        $where    = $operator == 'and' ? 'where' : 'orWhere';
        foreach ($rule as $var) {
            $var1 = ['operator' => $var['operator'] ?? '=', 'parameter' => $var['parameter'] ?? null, 'hide' => $var['hide'] ?? false];
            if ($var1['operator'] == 'like') {
                $var1['parameter'] = '%' . $var1['parameter'] . '%';
            }
            $new_rule[$var['subject']][] = $var1;
        }
        $model->where(function($model2) use ($model, $where, $new_rule){
            $inner = ['name', 'phone', 'email'];
            foreach ($inner as $col_name) {
                if ($rules = $new_rule[$col_name] ?? false) {
                    foreach ($rules as $rul) {
                        $model2->$where('users.'.$col_name, $rul['operator'], $rul['parameter']);
                    }
                }
            }
        });
    }

    public function detailScheduleUserAcademy(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_user'])){
            $listTrx = Transaction::join('transaction_academy', 'transaction_academy.id_transaction', 'transactions.id_transaction')
                        ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                        ->leftJoin('products', 'products.id_product', 'transaction_products.id_product')
                        ->where('transaction_from', 'academy')
                        ->where('transactions.id_user', $post['id_user'])->with(['user', 'outlet', 'transaction_academy.completed_installment', 'transaction_academy.all_installment'])
                        ->select('transactions.*', 'products.product_name', 'transaction_academy.*', 'transaction_products.*')
                        ->with('transaction_academy.user_schedule')
                        ->orderBy('transaction_date', 'desc')
                        ->get()->toArray();

            foreach ($listTrx as $key=>$trx){
                $status = 0;
                if($trx['transaction_payment_status'] == 'Completed'){
                    $check = TransactionAcademy::join('transaction_academy_schedules','transaction_academy_schedules.id_transaction_academy','=','transaction_academy.id_transaction_academy')
                        ->where('transaction_academy_schedules.id_transaction_academy', $trx['id_transaction_academy'])
                        ->whereRaw('transaction_academy.transaction_academy_total_meeting = transaction_academy_schedules.meeting')
                        ->get()->toArray();

                    if(empty($check)){
                        $status = 1;
                    }
                }


                $listTrx[$key]['status_schedule_not_setting'] = $status;
            }
            return response()->json(MyHelper::checkGet($listTrx));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID user can not be empty']]);
        }
    }

    public function listScheduleAcademy(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction_academy'])){
            $listSchedule =  Transaction::join('transaction_academy', 'transaction_academy.id_transaction', 'transactions.id_transaction')
                ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                ->leftJoin('products', 'products.id_product', 'transaction_products.id_product')
                ->where('transaction_academy.id_transaction_academy', $post['id_transaction_academy'])->with(['user', 'outlet'])
                ->select('transactions.*', 'products.product_name', 'transaction_academy.*', 'transaction_products.*')
                ->with('transaction_academy.user_schedule')
                ->first();

            if(!empty($listSchedule)){
                $listSchedule['status_dp'] = true;
                if($listSchedule['trasaction_payment_type'] == 'Installment'){
                    $completedInstallment = TransactionAcademyInstallment::where('id_transaction_academy', $listSchedule['id_transaction_academy'])->whereNotNull('completed_installment_at')->sum('percent');
                    $listSchedule['status_dp'] = ($completedInstallment < 50 ? false:true);
                }
            }
            return response()->json(MyHelper::checkGet($listSchedule));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction academy can not be empty']]);
        }
    }

    public function updateScheduleUserAcademy(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction_academy'])){
            if(empty($post['date'])){
                return response()->json(['status' => 'fail', 'messages' => ['Date can not be empty']]);
            }

            foreach ($post['date'] as $key=>$value){
                if(empty($value['date'])){
                    continue;
                }
                
                if(!empty($value['id_transaction_academy_schedule'])){
                    $save = TransactionAcademySchedule::where('id_transaction_academy_schedule', $value['id_transaction_academy_schedule'])->update([
                        'schedule_date' => date('Y-m-d H:i:s', strtotime($value['date']))
                    ]);
                }else{
                    $save = TransactionAcademySchedule::create([
                        'id_transaction_academy' => $post['id_transaction_academy'],
                        'id_user' => $post['id_user'],
                        'schedule_date' => date('Y-m-d H:i:s', strtotime($value['date'])),
                        'transaction_academy_schedule_status' => $value['transaction_academy_schedule_status']??'Not Started',
                        'meeting' => $key
                    ]);
                }
            }

            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID transaction academy can not be empty']]);
        }
    }

    public function listDayOffUserAcademy(Request $request)
    {
        $list = TransactionAcademyScheduleDayOff::join('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy_schedule', 'transaction_academy_schedule_day_off.id_transaction_academy_schedule')
            ->join('users','transaction_academy_schedules.id_user','=','users.id')
            ->leftJoin('users as approve', 'approve.id', 'transaction_academy_schedule_day_off.approve_by')
            ->leftJoin('users as reject', 'reject.id', 'transaction_academy_schedule_day_off.reject_by')
            ->select(
                'approve.name as approve_by_name', 'reject.name as reject_by_name',
                'users.id', 'users.name', 'users.email', 'users.phone',
                'transaction_academy_schedule_day_off.*',
                DB::raw('DATE_FORMAT(transaction_academy_schedule_day_off.created_at, "%d %b %Y %H:%i") as request_date'),
                DB::raw('DATE_FORMAT(transaction_academy_schedule_day_off.schedule_date_old, "%d %b %Y %H:%i") as schedule_date_old'),
                DB::raw('DATE_FORMAT(transaction_academy_schedule_day_off.schedule_date_new, "%d %b %Y %H:%i") as schedule_date_new')
            );

        $countTotal = null;

        if ($request->rule) {
            $countTotal = $list->count();
            $this->filterList($list, $request->rule, $request->operator ?: 'and');
        }

        if (is_array($orders = $request->order)) {
            $columns = [
                'name',
                'phone',
                'email'
            ];

            foreach ($orders as $column) {
                if ($colname = ($columns[$column['column']] ?? false)) {
                    $list->orderBy($colname, $column['dir']);
                }
            }
        }
        $list->orderBy('transaction_academy_schedule_day_off.updated_at', $column['dir'] ?? 'DESC');

        if ($request->page) {
            $list = $list->paginate($request->length ?: 15);
            $list->each(function($item) {
                $item->images = array_map(function($item) {
                    return config('url.storage_url_api').$item;
                }, json_decode($item->images) ?? []);
            });
            $list = $list->toArray();
            if (is_null($countTotal)) {
                $countTotal = $list['total'];
            }
            // needed by datatables
            $list['recordsTotal'] = $countTotal;
            $list['recordsFiltered'] = $list['total'];
        } else {
            $list = $list->get();
        }
        return MyHelper::checkGet($list);
    }

    public function actionDayOffUserAcademy(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction_academy_schedule_day_off'])){
            $check = TransactionAcademyScheduleDayOff::join('transaction_academy_schedules', 'transaction_academy_schedules.id_transaction_academy_schedule', 'transaction_academy_schedule_day_off.id_transaction_academy_schedule')
                ->join('transaction_academy', 'transaction_academy.id_transaction_academy', 'transaction_academy_schedules.id_transaction_academy')
                ->join('users','transaction_academy_schedules.id_user','=','users.id')
                ->where('id_transaction_academy_schedule_day_off', $post['id_transaction_academy_schedule_day_off'])
                ->first();
            if(empty($check)){
                return response()->json(['status' => 'fail', 'messages' => ['Data schedule not found']]);
            }


            if($post['status'] == 'approve'){
                if($check['transaction_academy_schedule_status'] != 'Not Started'){
                    return response()->json(['status' => 'fail', 'messages' => ['Can not approve meeting already started']]);
                }

                $save = TransactionAcademyScheduleDayOff::where('id_transaction_academy_schedule_day_off', $post['id_transaction_academy_schedule_day_off'])->update([
                    'schedule_date_new' => date('Y-m-d H:i:s', strtotime($post['new_date'])),
                    'approve_by' => $request->user()->id,
                    'approve_date' => date('Y-m-d H:i:s'),
                ]);

                if($save){
                    $save = TransactionAcademySchedule::where('id_transaction_academy_schedule', $check['id_transaction_academy_schedule'])->update([
                        'schedule_date' => date('Y-m-d H:i:s', strtotime($post['new_date'])),
                        'change_schedule' => 1,
                        'count_change_schedule' => $check['count_change_schedule']+1
                    ]);

                    if($save){
                        $autocrm = app($this->autocrm)->SendAutoCRM(
                            'Approve Day Off User Academy',
                            $check['phone'],
                            [
                                'id_transaction' => $check['id_transaction'],
                                'schedule_old' => $check['schedule_date_old'],
                                'schedule_new' => $post['new_date']
                            ]
                        );
                    }
                }
            }elseif($post['status'] == 'reject'){
                $save = TransactionAcademyScheduleDayOff::where('id_transaction_academy_schedule_day_off', $post['id_transaction_academy_schedule_day_off'])->update([
                    'reject_by' => $request->user()->id,
                    'reject_date' => date('Y-m-d H:i:s'),
                ]);

                if($save){
                    $autocrm = app($this->autocrm)->SendAutoCRM(
                        'Reject Day Off User Academy',
                        $check['phone'],
                        [
                            'id_transaction' => $check['id_transaction'],
                            'schedule_old' => $check['schedule_date_old'],
                            'schedule_new' => $check['schedule_date_new']
                        ]
                    );
                }
            }

            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function outletCourseAcademy(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_outlet'])){
            $listCourse = Transaction::join('outlets', 'outlets.id_outlet', 'transactions.id_outlet')
                        ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                        ->join('products', 'products.id_product', 'transaction_products.id_product')
                        ->where('transaction_from', 'academy')
                        ->where('transactions.id_outlet', $post['id_outlet'])
                        ->groupBy('transaction_products.id_product')
                        ->select('products.id_product', 'products.product_code', 'products.product_name', DB::raw('COUNT(transactions.id_user) as total_student'))
                        ->get()->toArray();
            return response()->json(MyHelper::checkGet($listCourse));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function detailOutletCourseAcademy(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_outlet']) && !empty($post['id_product'])){
            $outlet = Outlet::where('id_outlet', $post['id_outlet'])->first();
            $course = Product::where('id_product', $post['id_product'])->first();
            $listUser = Transaction::join('users', 'users.id', 'transactions.id_user')
                ->join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                ->join('transaction_academy', 'transaction_academy.id_transaction', 'transactions.id_transaction')
                ->where('transaction_from', 'academy')
                ->where('transactions.id_outlet', $post['id_outlet'])
                ->where('transaction_products.id_product', $post['id_product'])
                ->select('users.name', 'users.id', 'users.phone', 'users.email', 'transaction_academy.id_transaction_academy', 'final_score');

            if(!empty($request->rule)){
                $this->filterList($listUser, $request->rule, $request->operator ?: 'and');
            }

            $listUser = $listUser->get()->toArray();
            foreach($listUser as $key=>$user){
                $nextMetting = TransactionAcademySchedule::where('id_transaction_academy', $user['id_transaction_academy'])
                                ->where('id_user', $user['id'])->where('transaction_academy_schedule_status', 'Not Started')
                                ->orderBy('schedule_date', 'asc')->first();
                $listUser[$key]['next_meeting'] = $nextMetting;
            }

            $res = [
                'outlet' => $outlet,
                'course' => $course,
                'users' => $listUser
            ];
            return response()->json(MyHelper::checkGet($res));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function attendanceOutletCourseAcademy(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_transaction_academy_schedule']) && !empty($post['id_transaction_academy'])){
            $save = TransactionAcademySchedule::where('id_transaction_academy_schedule', $post['id_transaction_academy_schedule'])->update(['transaction_academy_schedule_status' => $post['transaction_academy_schedule_status']]);
            if($save && !empty($post['theory'])){
                $insert = [];
                foreach ($post['conclusion_score'] as $id=>$value){
                    $saveCategory = TransactionAcademyScheduleTheoryCategory::updateOrCreate(['id_theory_category' => $id, 'id_transaction_academy' => $post['id_transaction_academy']],
                                ['conclusion_score' => $value]);

                    if($saveCategory){
                        foreach ($post['theory'] as $theory){
                            if(!empty($theory['id_theory']) && $theory['parent_category'] == $id && !empty($theory['score'])){
                                $insert[] = [
                                    'id_transaction_academy_schedule' => $post['id_transaction_academy_schedule'],
                                    'id_transaction_academy_schedule_theory_category' => $saveCategory['id_transaction_academy_schedule_theory_category'],
                                    'id_theory' => $theory['id_theory'],
                                    'theory_title' => $theory['title'],
                                    'score' => $theory['score'],
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                            }
                        }
                    }
                }

                if(!empty($insert)){
                    $save = TransactionAcademyScheduleTheory::insert($insert);
                }
            }

            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function detailAttendanceOutletCourseAcademy(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_transaction_academy_schedule'])){
            $detailSchedule = TransactionAcademySchedule::where('id_transaction_academy_schedule', $post['id_transaction_academy_schedule'])->first();

            if(!empty($detailSchedule)){
                $detailSchedule['last_meeting'] = TransactionAcademySchedule::where('id_transaction_academy', $detailSchedule['id_transaction_academy'])->orderBy('meeting', 'desc')->first()['meeting']??null;
                $trx = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                    ->join('transaction_academy', 'transaction_academy.id_transaction', 'transactions.id_transaction')
                    ->where('id_transaction_academy', $detailSchedule['id_transaction_academy'])->first();

                if(!empty($trx)){
                    $user = User::where('id', $trx['id_user'])->first();
                    $outlet = Outlet::where('id_outlet', $trx['id_outlet'])->first();
                    $course = Product::where('id_product', $trx['id_product'])->first();
                    $theoryCategory = TheoryCategory::join('product_academy_theory_categories', 'product_academy_theory_categories.id_theory_category', 'theory_categories.id_theory_category')
                                    ->where('id_product', $trx['id_product'])
                                    ->where('id_parent_theory_category', 0)
                                    ->with('theory')->get()->toArray();
                    $allConclusion = TransactionAcademyScheduleTheoryCategory::where('id_transaction_academy', $detailSchedule['id_transaction_academy'])->get()->toArray();

                    foreach ($theoryCategory as $key=>$tc){
                        $checkConclusion = array_search($tc['id_theory_category'], array_column($allConclusion, 'id_theory_category'));
                        if(!empty($allConclusion[$checkConclusion]['conclusion_score'])){
                            $theoryCategory[$key]['conclusion_score'] = $allConclusion[$checkConclusion]['conclusion_score'];
                        }

                        $allIDTheory = TransactionAcademyScheduleTheory::join('transaction_academy_schedule_theory_categories', 'transaction_academy_schedule_theories.id_transaction_academy_schedule_theory_category',
                                        'transaction_academy_schedule_theory_categories.id_transaction_academy_schedule_theory_category')
                                        ->where('id_theory_category', $tc['id_theory_category'])->get()->toArray();

                        $allID = array_column($allIDTheory, 'id_theory');
                        $allScore = array_column($allIDTheory, 'score');

                        foreach ($tc['theory'] as $i=>$theory){
                            $check = array_search($theory['id_theory'],$allID);
                            if($check === false){
                                $theoryCategory[$key]['theory'][$i]['checked'] = 0;
                                $theoryCategory[$key]['theory'][$i]['score'] = 0;
                            }else{
                                $theoryCategory[$key]['theory'][$i]['checked'] = 1;
                                $theoryCategory[$key]['theory'][$i]['score'] = $allScore[$check];
                            }
                        }

                        $child = TheoryCategory::where('id_parent_theory_category', $tc['id_theory_category'])->with('theory')->get()->toArray();
                        foreach ($child as $keyChild=>$c){
                            foreach ($c['theory'] as $j=>$theoryChild){
                                $check = array_search($theoryChild['id_theory'],$allID);
                                if($check === false){
                                    $child[$keyChild]['theory'][$j]['checked'] = 0;
                                    $child[$keyChild]['theory'][$j]['score'] = 0;
                                }else{
                                    $child[$keyChild]['theory'][$j]['checked'] = 1;
                                    $child[$keyChild]['theory'][$j]['score'] = $allScore[$check];
                                }
                            }
                        }
                        $theoryCategory[$key]['child'] = $child;
                    }

                    $res = [
                        'detail' => $detailSchedule,
                        'user' => $user,
                        'outlet' => $outlet,
                        'course' => $course,
                        'theory_category' => $theoryCategory
                    ];
                    return response()->json(MyHelper::checkGet($res));
                }
            }

            return response()->json(MyHelper::checkGet([]));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function finalScoreOutletCourseAcademy(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_transaction_academy'])){
            $update = TransactionAcademy::where('id_transaction_academy', $post['id_transaction_academy'])->update(['final_score' => $post['final_score']]);
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function courseDetailHistory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_transaction_academy'])){
            $trx = Transaction::join('transaction_products', 'transaction_products.id_transaction', 'transactions.id_transaction')
                ->join('transaction_academy', 'transaction_academy.id_transaction', 'transactions.id_transaction')
                ->where('id_transaction_academy', $post['id_transaction_academy'])->first();
            if(!empty($trx)){
                $user = User::where('id', $trx['id_user'])->first();
                $outlet = Outlet::where('id_outlet', $trx['id_outlet'])->first();
                $course = Product::where('id_product', $trx['id_product'])->first();
                $conclusion = TransactionAcademyScheduleTheoryCategory::join('theory_categories', 'theory_categories.id_theory_category', 'transaction_academy_schedule_theory_categories.id_theory_category')
                                ->where('id_transaction_academy', $post['id_transaction_academy'])->get()->toArray();
                $schedule = TransactionAcademySchedule::where('id_transaction_academy', $post['id_transaction_academy'])->orderBy('schedule_date', 'asc')->get()->toArray();

                foreach ($schedule as $j=>$s){
                    $learn = TransactionAcademyScheduleTheory::where('id_transaction_academy_schedule', $s['id_transaction_academy_schedule'])->get()->toArray();
                    $schedule[$j]['theory'] = $learn;
                }

                $res = [
                    'final_score' => $trx['final_score']??0,
                    'user' => $user,
                    'outlet' => $outlet,
                    'course' => $course,
                    'conclusion' => $conclusion,
                    'schedule' => $schedule
                ];
                return response()->json(MyHelper::checkGet($res));
            }

            return response()->json(MyHelper::checkGet([]));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }
}
