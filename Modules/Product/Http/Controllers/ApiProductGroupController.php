<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductGroup;
use DB;

class ApiProductGroupController extends Controller
{
    public function list(Request $request)
    {
        $data = ProductGroup::withCount('products')
            ->orderBy('id_product_group','desc')
            ->get();

        return MyHelper::checkGet($data);
    }

    public function create(Request $request)
    {
    	$exist = ProductGroup::where('product_group_code', $request->product_group_code)->first();

    	if ($exist) {
    		return ['status' => 'fail', 'messages' => ['Product Group Code ' . $request->product_group_code . ' already used']];
    	}

    	$create = ProductGroup::create([
    		'product_group_code' => $request->product_group_code,
    		'product_group_name' => $request->product_group_name
    	]);

    	return MyHelper::checkCreate($create);
    }

    public function update(Request $request)
    {
    	$exist = ProductGroup::where('product_group_code', $request->product_group_code)
				->where('id_product_group', '!=', $request->id_product_group)
				->first();

    	if ($exist) {
    		return ['status' => 'fail', 'messages' => ['Product Group Code ' . $request->product_group_code . ' already used']];
    	}

    	$update = ProductGroup::where('id_product_group', $request->id_product_group)->update([
    		'product_group_code' => $request->product_group_code,
    		'product_group_name' => $request->product_group_name
    	]);

    	return ['status' => 'success'];
    }

    public function delete(Request $request)
    {
    	$delete = ProductGroup::where('id_product_group', $request->id_product_group)->delete();
    	return MyHelper::checkDelete($delete);
    }

    public function detail(Request $request, $id_product_group)
    {
        $data = ProductGroup::where('id_product_group', $id_product_group)
        		->with('products')
	            ->first();

        return MyHelper::checkGet($data);
    }

    public function productList(Request $request)
    {
        $post = $request->json()->all();

        $listProduct = Product::where('product_type', 'product')
        				->where(function($q) use ($post) {
        					$q->whereNull('id_product_group')
        					->orWhere('id_product_group', '!=', $post['id_product_group']);
        				})
        				->get();

        return $listProduct;
    }

    public function addProduct(Request $request)
    {
        $post = $request->json()->all();

        $update = Product::whereIn('id_product', $post['products'])->update(['id_product_group' => $post['id_product_group']]);
	        
        return ['status' => 'success'];
    }

    public function removeProduct(Request $request)
    {
        $post = $request->json()->all();

        $update = Product::where('id_product', $post['id_product'])->update(['id_product_group' => null]);
	        
        return ['status' => 'success'];
    }

    public function updateProduct(Request $request)
    {
        $post = $request->json()->all();
        DB::beginTransaction();
        try {
	        foreach ($post['variants'] ?? [] as $id_product => $name) {
	        	$update = Product::where('id_product', $id_product)->update(['variant_name' => $name]);
	        }
	        DB::commit();
        } catch (\Exception $e) {
        	DB::rollBack();
        	return ['status' => 'fail', 'messages' => ['Failed to update product']];
        }
	        
        return ['status' => 'success'];
    }
}
