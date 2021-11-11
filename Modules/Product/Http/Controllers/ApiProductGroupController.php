<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Product\Entities\ProductGroup;
use App\Http\Models\Transaction;
use App\Http\Models\Banner;
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

    	$photo = null;
		if (isset($request->photo)) {
			$upload = MyHelper::uploadPhotoStrict($request->photo, "img/product/product-group/", 300, 300);

			if (isset($upload['status']) && $upload['status'] == "success") {
				$photo = $upload['path'];
			} else {
				return [
					'status'   => 'fail',
					'messages' => ['failed to upload image']
				];
			}
        }

    	$create = ProductGroup::create([
    		'product_group_code' => $request->product_group_code,
    		'product_group_name' => $request->product_group_name,
    		'product_group_photo' => $photo,
    		'product_group_description' => $request->product_group_description
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

    	$productGroup = ProductGroup::where('id_product_group', $request->id_product_group)->first();

    	if (!$productGroup) {
    		return ['status' => 'fail', 'messages' => ['Product Group not found']];
    	}

    	$photo = $productGroup['product_group_photo'];
		if (isset($request->photo)) {

			$upload = MyHelper::uploadPhotoStrict($request->photo, "img/product/product-group/", 300, 300);

			if (isset($upload['status']) && $upload['status'] == "success") {
				$photo = $upload['path'];
			} else {
				return [
					'status'   => 'fail',
					'messages' => ['failed to upload image']
				];
			}

			MyHelper::deletePhoto($productGroup['product_group_photo']);
        }

    	$productGroup->update([
    		'product_group_code' => $request->product_group_code,
    		'product_group_name' => $request->product_group_name,
    		'product_group_photo' => $photo,
    		'product_group_description' => $request->product_group_description
    	]);

    	return ['status' => 'success'];
    }

    public function delete(Request $request)
    {
    	$trx = Transaction::leftJoin('transaction_products','transactions.id_transaction','transaction_products.id_transaction')
    			->join('products','transaction_products.id_product','products.id_product')
    			->where('transactions.transaction_payment_status','Completed')
    			->where('products.id_product_group',$request->id_product_group)
    			->first();

    	if ($trx) {
    		return ['status' => 'fail', 'messages' => ['Product Group already used on transaction']];
    	}

    	$productGroup = ProductGroup::where('id_product_group', $request->id_product_group)->first();

    	if (!$productGroup) {
    		return ['status' => 'fail', 'messages' => ['Product Group not found']];
    	}


    	$delete = ProductGroup::where('id_product_group', $request->id_product_group)->delete();
    	if ($delete) {
    		MyHelper::deletePhoto($productGroup['product_group_photo']);
    	}
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

    public function featuredList()
    {
    	$featured = Banner::select([
    					'banners.*',
    					DB::raw('
			            	(select product_groups.product_group_name from product_groups 
			            		WHERE product_groups.id_product_group = banners.id_reference 
			            		limit 1
		            		) as product_group_name
		            	'),
		            	DB::raw('
			            	(select product_groups.product_group_photo from product_groups 
			            		WHERE product_groups.id_product_group = banners.id_reference 
			            		limit 1
		            		) as product_group_photo
		            	')
    				])
    				->orderBy('position')
    				->where('type', 'product_group')
    				->get();

		foreach ($featured as $key => $value) {
            $featured[$key]['image_url'] = config('url.storage_url_api').$value['image'];
		}

        return MyHelper::checkGet($featured);
    }

    public function featuredCreate(Request $request)
    {
        $post = $request->except('_token');

        $image = null;
		if (isset($request->image)) {
			$upload = MyHelper::uploadPhotoStrict($request->image, "img/banner/", 750, 375);

			if (isset($upload['status']) && $upload['status'] == "success") {
				$image = $upload['path'];
			} else {
				return [
					'status'   => 'fail',
					'messages' => ['failed to upload image']
				];
			}
        }

		$createData = [
			'banner_start' => date('Y-m-d H:i:s', strtotime($post['banner_start'])),
			'banner_end' => date('Y-m-d H:i:s', strtotime($post['banner_end'])),
			'type' => 'product_group',
			'image' => $image,
			'id_reference' => $post['id_product_group']
		];

        $create = Banner::create($createData);

        return MyHelper::checkCreate($create);
    }

    public function featuredReorder(Request $request)
    {
        $post = $request->json()->all();

        DB::beginTransaction();
        foreach ($post['id_banner'] as $key => $id_banner) {
            // reorder
            $update = Banner::find($id_banner)->update(['position' => $key+1]);

            if (!$update) {
                DB:: rollback();
                return [
                    'status' => 'fail',
                    'messages' => ['Sort featured product group failed']
                ];
            }
        }
        DB::commit();

        return MyHelper::checkUpdate($update);
    }

    public function featuredUpdate(Request $request)
    {
        $post = $request->json()->all();

        $featured = Banner::find($post['id_banner']);

        $image = $featured->image;
		if (isset($request->image)) {
			$upload = MyHelper::uploadPhotoStrict($request->image, "img/banner/", 750, 375);

			if (isset($upload['status']) && $upload['status'] == "success") {
				$image = $upload['path'];
			} else {
				return [
					'status'   => 'fail',
					'messages' => ['failed to upload image']
				];
			}

			$delete = MyHelper::deletePhoto($featured->image);
        }

		$updateData = [
			'banner_start' => date('Y-m-d H:i:s', strtotime($post['banner_start'])),
			'banner_end' => date('Y-m-d H:i:s', strtotime($post['banner_end'])),
			'type' => 'product_group',
			'image' => $image,
			'id_reference' => $post['id_product_group']
		];

        $update = $featured->update($updateData);

        return MyHelper::checkCreate($update);
    }

    public function featuredDestroy(Request $request)
    {
        $post = $request->json()->all();
        $featured = Banner::find($post['id_banner']);
        if (!$featured) {
            return [
                'status' => 'fail',
                'messages' => ['Data not found']
            ];
        }

        $delete = Banner::where('id_banner', $featured->id_banner)->delete();

        if ($delete) {
        	$delete = MyHelper::deletePhoto($featured->image);
        }
        return MyHelper::checkDelete($delete);
    }

    public function activeList(Request $request)
    {
    	$featured = $this->featuredList()['result'] ?? [];
    	if (!is_array($featured)) {
    		$featured = $featured->toArray();
    	}
    	$id_product_groups = array_column($featured, 'id_reference');

    	$res = ProductGroup::whereHas('products')
    				->whereNotIn('id_product_group', $id_product_groups)
    				->get();

    	return MyHelper::checkGet($res);
    }
}
