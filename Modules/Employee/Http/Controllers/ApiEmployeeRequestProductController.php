<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use DB;

use Modules\Employee\Http\Requests\StoreBudgeting;
use App\Http\Models\Setting;
use Maatwebsite\Excel\Concerns\ToArray;
use Modules\Employee\Entities\EmployeeInbox;
use App\Lib\MyHelper;
use Modules\Users\Entities\RolesFeature;
use Modules\Product\Entities\RequestProduct;
use Modules\Product\Entities\RequestProductDetail;
use Modules\Product\Entities\RequestProductImage;
use Modules\Product\Entities\ProductIcount;
use Modules\Product\Entities\ProductCatalog;
use Modules\Product\Entities\ProductCatalogDetail;
use App\Http\Models\Province;
use App\Http\Models\Outlet;
use App\Http\Models\User;
use Modules\Users\Entities\Department;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\Employee\Entities\EmployeeScheduleDate;
use Modules\Employee\Entities\DepartmentBudget;
use Modules\Employee\Entities\DepartmentBudgetLog;
use Modules\Employee\Http\Requests\AssetInventory\ApproveLoan;
use Modules\Employee\Http\Requests\AssetInventory\ApproveReturn;


class ApiEmployeeRequestProductController extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->request_path = "img/product/request_product/";
    }

    public function storeBudgeting(StoreBudgeting $request){
        $post = $request->all();
        
        $department = Department::where('id_department_icount', $post['DepartmentID'])->first();
        if($department){
            DB::beginTransaction();
            $old_budget = DepartmentBudget::where('id_department',$department['id_department'])->first();
            if($old_budget){
                $balance_before = $old_budget['budget_balance'];
            }else{
                $balance_before = 0;
            }

            $budget_balance = $old_budget['budget_balance'] + $post['balance'];

            $store_department = DepartmentBudget::updateOrCreate(['id_department'=>$department['id_department']],['budget_balance'=>$budget_balance]);
            if(!$store_department){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed to create or updaate department balance']]);
            }

            $log = DepartmentBudgetLog::create([
                'id_department_budget' => $store_department['id_department_budget'],
                'date_budgeting' => date('Y-m-d'),
                'source' => 'Department Budgeting',
                'balance' => $post['balance'],
                'balance_before' => $balance_before,
                'balance_after' => $balance_before+$post['balance'],
                'balance_total' => $store_department['budget_balance'],
                'notes' => $post['notes'] ?? null
            ]);

            if(!$log){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed to create or updaate department balance']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }
        return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
    }

    public function createRequest(Request $request){
        $post = $request->all();
       
        $user = $request->user();
        $id_employee = $user['id'];
        $roles = RolesFeature::where('id_role', $user['id_role'])->select('id_feature')->get()->toArray();
        $roles = array_pluck($roles, 'id_feature');
        $send = [];

        $department = DepartmentBudget::join('departments', 'departments.id_department', 'department_budgets.id_department')->join('roles', 'roles.id_department', 'departments.id_department')->where('roles.id_role',$user['id_role'])->first();
        if(!$department){
            $department = Department::join('roles', 'roles.id_department', 'departments.id_department')->where('roles.id_role',$user['id_role'])->first();
        }
        
        $list_outlet = [];
        if(in_array('410',$roles) ){
            $list_outlet= Outlet::where('outlets.outlet_status', 'Active')->select('id_outlet', 'outlet_name')->orderBy('outlet_name', 'asc')->get()->toArray();
            $list_outlet = array_map(function($value){
                unset($value['call']);
                unset($value['url']);
                return $value;
            },$list_outlet);
        }else{
            $outlet = Outlet::where('id_outlet',$user['id_outlet'])->first();
            $list_outlet[] = [
                'id_outlet' => $outlet['id_outlet'],
                'outlet_name' => $outlet['outlet_name'],
            ];
        }

        $send = [
            'id_department'  => $department['id_department'],   
            'name_department' => $department['department_name'],
            'budget_department' => $department['budget_balance'] ?? 0,
            'list_outlet' => $list_outlet,
            'type_request' => [
                [
                    'type' => 'Sell',
                    'name_type' => 'Untuk Dijual'
                ],
                [
                    'type' => 'Use',
                    'name_type' => 'Untuk Digunakan'
                ]
            ]
        ];

        return MyHelper::checkGet($send);
    }

    public function listCatalog(Request $request){
        $post = $request->all();
        $outlet = Location::join('outlets','outlets.id_location','locations.id_location')->where('id_outlet',$post['id_outlet'])->first();
        if(!$outlet){
            return response()->json(['status' => 'fail', 'messages' => ['Outlet tidak ditemukan']]);
        }
        $outlet = Location::join('outlets','outlets.id_location','locations.id_location')->where('id_outlet',$post['id_outlet'])->first();
        $company = [
            'PT IMA' => 'ima',
            'PT IMS' => 'ims',
        ];
        $starter = ProductCatalog::whereHas('product_catalog_details')->where('company_type',$company[$outlet['company_type']])->where('status', 1)->select('id_product_catalog','name')->get()->toArray();
        return MyHelper::checkGet($starter);
    }

    public function listProduct(Request $request){
        $post = $request->all();
        $products = ProductCatalogDetail::join('product_icounts', 'product_icounts.id_product_icount', 'product_catalog_details.id_product_icount')
                                        ->join('product_catalogs', 'product_catalogs.id_product_catalog', 'product_catalog_details.id_product_catalog')
                                        ->where('product_catalogs.id_product_catalog', $post['id_product_catalog'])
                                        ->select('product_catalog_details.id_product_icount','product_catalog_details.filter as category','product_icounts.name','product_icounts.unit1','product_icounts.unit2','product_icounts.unit3','product_icounts.ratio2','product_icounts.ratio3')
                                        ->get()->toArray();

        $products = array_map(function($value){
            if($value['unit1']){
                $value['unit'][] = $value['unit1'];
            }
            if($value['unit2'] && $value['ratio2']>0){
                $value['unit'][] = $value['unit2'];
            }
            if($value['unit3'] && $value['ratio3']>0){
                $value['unit'][] = $value['unit3'];
            }
            unset($value['unit1']);
            unset($value['unit2']);
            unset($value['unit3']);
            return $value;
        },$products);
        
        $send = [];
        foreach($products ?? [] as $key => $p){
                $send[$p['category']][] = $p;
        }
        return MyHelper::checkGet($send);

    }

    public function storeRequest(Request $request){
        $request->validate([
            'id_outlet' => 'integer|required',
            'type' => 'string|required',
            'id_product_catalog' => 'integer|required',
            'requirement_date' => 'required',
            'attachment.*'  => 'mimes:jpeg,jpg,bmp,png|max:2000'
        ]);
        $post = $request->all();
        $user = $request->user();
        $id_employee = $user['id'];

        $department = DepartmentBudget::join('departments', 'departments.id_department', 'department_budgets.id_department')->join('roles', 'roles.id_department', 'departments.id_department')->where('roles.id_role',$user['id_role'])->first();
        if(!$department){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Balance Department tidak mencukupi untuk melakukan permintaan barang']]);
        }
        $balance_now = DepartmentBudgetLog::where('id_department_budget',$department['id_department_budget'])->orderBy('created_at', 'desc')->first(); 
        
        $total_cost = 0;

        if(!isset($post['products']) && empty($post['products'])){
            return response()->json(['status' => 'fail', 'messages' => ['Gagal membuat permintaan, tidak ada produk yang dipilih']]);
        }
        
        DB::beginTransaction();

        if(!$department){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal membuat permintaan']]);
        }
        $arr_request = [
            'code' =>  app('\Modules\Product\Http\Controllers\ApiRequestProductController')->codeGenerate(),
            'id_outlet' => $post['id_outlet'],
            'type' => $post['type'],
            'id_product_catalog' => $post['id_product_catalog'],
            'requirement_date' => date('Y-m-d',strtotime($post['requirement_date'])) ?? date('Y-m-d'),
            'id_user_request' => $id_employee,
            'note_request' => $post['notes'] ?? null,
            'status' => 'Pending',
            'from' => 'Product',
            'use_department_budget' => 1
        ];

        $store_request = RequestProduct::create($arr_request);
        if(!$store_request){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Gagal membuat permintaan']]);
        }

        $arr_detail = [];
        foreach($post['products'] ?? [] as $key => $product){
            $product_icount = ProductCatalogDetail::join('product_icounts', 'product_icounts.id_product_icount', 'product_catalog_details.id_product_icount')
                                        ->join('product_catalogs', 'product_catalogs.id_product_catalog', 'product_catalog_details.id_product_catalog')
                                        ->where('product_catalogs.id_product_catalog', $post['id_product_catalog'])
                                        ->where('product_catalog_details.id_product_icount', $product['id_product_icount'])
                                        ->select('product_catalog_details.id_product_icount','product_catalog_details.filter as category','product_catalog_details.budget_code','product_icounts.name','product_icounts.unit1','product_icounts.unit2','product_icounts.unit3','product_icounts.ratio2','product_icounts.ratio3','product_icounts.unit_price_1')
                                        ->first();
            if($product['unit']==$product_icount['unit1']){
                $cost = $product_icount['unit_price_1'] * $product['value'];
            }
            if($product['unit']==$product_icount['unit2'] && $product_icount['unit2'] && $product_icount['ratio2']>0){
                $cost = $product_icount['unit_price_1'] * $product_icount['ratio2'] * $product['value'];
            }
            if($product['unit']==$product_icount['unit3'] && $product_icount['unit3'] && $product_icount['ratio3']>0){
                $cost = $product_icount['unit_price_1'] * $product_icount['ratio3'] * $product['value'];
            }
            $total_cost = $total_cost + $cost;

            $arr_detail[] = [
                'id_request_product' => $store_request['id_request_product'],
                'id_product_icount' => $product_icount['id_product_icount'],
                'unit' => $product['unit'],
                'value' => $product['value'],
                'filter' => $product['category'],
                'budget_code' => $product_icount['budget_code']
            ];
        }
        if($balance_now['balance_total']<$total_cost){
            DB::rollback();
            return response()->json(['status' => 'fail', 'messages' => ['Balance Department tidak mencukupi untuk melakukan permintaan barang']]);
        }

        if($arr_detail){
            $store_detail = RequestProductDetail::insert($arr_detail);
            if(!$store_detail){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Gagal membuat permintaan']]);
            }
        }

        if(isset($post['attachment'])){
            $delete_image = RequestProductImage::where('id_request_product',$store_request['id_request_product'])->delete();
    
            $files = [];
            foreach ($post['attachment'] as $i => $attachment){
                if(!empty($attachment)){
                    try{
                        $encode = base64_encode(fread(fopen($attachment, "r"), filesize($attachment)));
                    }catch(\Exception $e) {
                        DB::rollBack();
                        return response()->json(['status' => 'fail', 'messages' => ['Ukuran file lebih besar dari 2 MB']]);
                    }
                    $originalName = $attachment->getClientOriginalName();
                    if($originalName == ''){
                        $ext = 'png';
                        $name = $request->user()->name.'_'.$i;
                        $name = str_replace(' ','_',$name);
                    }else{
                        $name = pathinfo($originalName, PATHINFO_FILENAME);
                        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                        if(strpos($name, '_blob')){
                            $name = str_replace('_blob','',$name);
                            $ext='jpeg';
                        }
                    }
                    $upload = MyHelper::uploadFile($encode, $this->request_path, $ext, date('YmdHis').'_'.$name);
                    if (isset($upload['status']) && $upload['status'] == "success") {
                        $save_image = [
                            "id_request_product" => $store_request['id_request_product'],
                            "path"               => $upload['path']
                        ];
                        $storage_image = RequestProductImage::create($save_image);
                    }else {
                        DB::rollback();
                        return response()->json([
                            'status'=>'fail',
                            'messages'=>['Gagal menyimpan file']
                        ]);
                    }
                }
            }
        }

        DB::commit();
        return response()->json(['status' => 'success']);

    }
}