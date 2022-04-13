<?php

namespace Modules\Product\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Http\Models\Product;
use App\Http\Models\Outlet;
use Modules\Product\Entities\ProductIcount;
use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;
use Image;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductProductIcount;
use Modules\Product\Entities\ProductOutletStock;

class ApiProductProductIcountController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }
    public function index(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_product']) && !empty($post['id_product'])){
            $pivots = ProductProductIcount::where('id_product', $post['id_product'])->with(['products','product_icounts'])->get()->toArray();
            if($pivots==null){
                return response()->json(['status' => 'success', 'result' => [
                    'pivots' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'pivots' => $pivots,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('product::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_product']) && !empty($post['id_product'])){
            $outlets = Outlet::where('outlet_status', 'Active')->get();
            DB::beginTransaction();
            foreach($post['product_icount'] as $product_icount){
                $store = ProductProductIcount::create([
                    "id_product"   => $post['id_product'],
                    "id_product_icount"   => $product_icount['id_product_icount'],
                    "unit"   => $product_icount['unit'],
                    "qty"   => $product_icount['qty'],
                ]);
                if(!$store){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add data']]);
                }
                foreach ($outlets as $outlet) {
                    ProductIcount::find($product_icount['id_product_icount'])->refreshStock($outlet->id_outlet, $product_icount['unit']);
                }
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('product::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('product::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_product']) && !empty($post['id_product'])){
            DB::beginTransaction();
            $delete = ProductProductIcount::where('id_product', $post['id_product'])->where('company_type', $post['company_type'])->delete();
            $insert = [];
            foreach($post['product_icount'] ?? [] as $product_icount){
                $insert[] = [
                    "id_product"   => $post['id_product'],
                    "id_product_icount"   => $product_icount['id_product_icount'],
                    "unit"   => $product_icount['unit'],
                    "qty"   => $product_icount['qty'],
                    "company_type" => $post['company_type'],
                    "optional" => isset($product_icount['optional']) ? 1 : 0
                ];
            }
            if(!empty($insert)){
                $save = ProductProductIcount::insert($insert);
                if(!$save){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add data']]);
                }
                DB::commit();
                $outlets = ProductDetail::where('id_product',$post['id_product'])->select('id_outlet')->get()->toArray();
                foreach($post['product_icount'] ?? [] as $product_icount){

                        $id_product_icount = $product_icount['id_product_icount'];
                        $unit = $product_icount['unit'];
                        foreach($outlets ?? [] as $outlet){
                            $product_icount = New ProductIcount();
                            $refresh_stock = $product_icount->find($id_product_icount)->refreshStock($outlet['id_outlet'],$unit);
    
                        }

                }
                return response()->json(MyHelper::checkUpdate($save));
            }
            DB::commit();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
