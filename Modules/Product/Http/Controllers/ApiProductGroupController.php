<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductGroup;

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
}
