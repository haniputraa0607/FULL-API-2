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
use Modules\Transaction\Entities\TransactionAcademySchedule;
use Modules\Transaction\Entities\TransactionAcademyScheduleDayOff;
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
                'users.*'
            )
            ->groupBy('transactions.id_user');

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
        $list->orderBy('transactions.id_transaction', $column['dir'] ?? 'DESC');

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
                        ->where('transactions.id_user', $post['id_user'])->with(['user', 'outlet'])
                        ->select('transactions.*', 'products.product_name', 'transaction_academy.*', 'transaction_products.*')
                        ->with('transaction_academy.user_schedule')
                        ->get()->toArray();

            return response()->json(MyHelper::checkGet($listTrx));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID user can not be empty']]);
        }
    }

    public function updateScheduleUserAcademy(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_transaction_academy'])){
            if(empty($post['date'])){
                return response()->json(['status' => 'fail', 'messages' => ['Date can not be empty']]);
            }

            foreach ($post['date'] as $key=>$value){
                if(!empty($value['id_transaction_academy_schedule'])){
                    $save = TransactionAcademySchedule::where('id_transaction_academy_schedule', $value['id_transaction_academy_schedule'])->update([
                        'schedule_date' => date('Y-m-d H:i:s', strtotime($value['date'])),
                        'transaction_academy_schedule_status' => $value['transaction_academy_schedule_status']
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
}
