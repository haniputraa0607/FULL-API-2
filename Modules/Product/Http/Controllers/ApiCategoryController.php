<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Models\Product;
use App\Http\Models\ProductCategory;
use App\Http\Models\ProductDiscount;
use App\Http\Models\ProductPhoto;
use App\Http\Models\ProductPrice;
use App\Http\Models\NewsProduct;
use App\Http\Models\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Lib\MyHelper;
use Validator;
use Hash;
use DB;
use Mail;

use Modules\Product\Http\Requests\category\CreateProduct;
use Modules\Product\Http\Requests\category\UpdateCategory;
use Modules\Product\Http\Requests\category\DeleteCategory;

class ApiCategoryController extends Controller
{
    function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public $saveImage = "img/product/category/";

    /**
     * check inputan
     */
    function checkInputCategory($post=[], $type="update") {
        $data = [];

        if (isset($post['product_category_name'])) {
            $data['product_category_name'] = $post['product_category_name'];
        }

        if (isset($post['product_category_description'])) {
            $data['product_category_description'] = $post['product_category_description'];
        }

        if (isset($post['product_category_photo'])) {
            $save = MyHelper::uploadPhotoStrict($post['product_category_photo'], $this->saveImage, 300, 300);

            if (isset($save['status']) && $save['status'] == "success") {
                $data['product_category_photo'] = $save['path'];
            }
            else {
                $result = [
                    'error'    => 1,
                    'status'   => 'fail',
                    'messages' => ['fail upload image']
                ];

                return $result;
            }
        }

        if (isset($post['product_category_order'])) {
            $data['product_category_order'] = $post['product_category_order'];
        }
        else {
            // khusus create
            if ($type == "create") {
                if (isset($post['id_parent_category'])) {
                    $data['product_category_order'] = $this->searchLastSorting($post['id_parent_category']);
                }
                else {
                    $data['product_category_order'] = $this->searchLastSorting(null);
                }    
            }
        }

        if (isset($post['id_parent_category']) && $post['id_parent_category'] != null) {
            $data['id_parent_category'] = $post['id_parent_category'];
        } else {
			$data['id_parent_category'] = null;
		}

        return $data;
    }

    /**
     * create category
     */
    function create(CreateProduct $request) {

        $post = $request->json()->all();
        $data = $this->checkInputCategory($post, "create");

        if (isset($data['error'])) {
            unset($data['error']);
            
            return response()->json($data);
        }

        // create
        $create = ProductCategory::create($data);

        return response()->json(MyHelper::checkCreate($create));
    }

    /**
     * cari urutan ke berapa
     */
    function searchLastSorting($id_parent_category=null) {
        $sorting = ProductCategory::select('product_category_order')->orderBy('product_category_order', 'DESC');

        if (is_null($id_parent_category)) {
            $sorting->whereNull('id_parent_category');
        }
        else {
            $sorting->where('id_parent_category', $id_parent_category);
        }

        $sorting = $sorting->first();

        if (empty($sorting)) {
            return 1;
        }
        else {
            // kalo kosong otomatis jadiin nomer 1
            if (empty($sorting->product_category_order)) {
                return 1;
            }
            else {
                $sorting = $sorting->product_category_order + 1;
                return $sorting;
            }
        }
    }

    /**
     * update category
     */
    function update(UpdateCategory $request) {
        // info
        $dataCategory = ProductCategory::where('id_product_category', $request->json('id_product_category'))->get()->toArray();

        if (empty($dataCategory)) {
            return response()->json(MyHelper::checkGet($dataCategory));
        }

        $post = $request->json()->all();

        $data = $this->checkInputCategory($post);

        if (isset($data['error'])) {
            unset($data['error']);
            
            return response()->json($data);
        }
		 
        // update
        $update = ProductCategory::where('id_product_category', $post['id_product_category'])->update($data);

        // hapus file
        if (isset($data['product_category_photo'])) {
            MyHelper::deletePhoto($dataCategory[0]['product_category_photo']);
        }

        return response()->json(MyHelper::checkUpdate($update));
    }

    /**
     * delete (main)
     */
    function delete(DeleteCategory $request) {

        $id = $request->json('id_product_category');

        if ( ($this->checkDeleteParent($id)) && ($this->checkDeleteProduct($id)) ) {
            // info
            $dataCategory = ProductCategory::where('id_product_category', $request->json('id_product_category'))->get()->toArray();

            if (empty($dataCategory)) {
                return response()->json(MyHelper::checkGet($dataCategory));
            }

            $delete = ProductCategory::where('id_product_category', $id)->delete();

            // delete file
            MyHelper::deletePhoto($dataCategory[0]['product_category_photo']);
            
            return response()->json(MyHelper::checkDelete($delete));
        }
        else {
            $result = [
                'status' => 'fail',
                'messages' => ['category has been used.']
            ];

            return response()->json($result);
        }
    }

    /**
     * delete check digunakan sebagai parent
     */
    function checkDeleteParent($id) {
        $check = ProductCategory::where('id_parent_category', $id)->count();

        if ($check == 0) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * delete check digunakan sebagai product
     */
    function checkDeleteProduct($id) {
        $check = Product::where('id_product_category', $id)->count();

        if ($check == 0) {
            return true;
        }
        else {
            return false;
        }
        return true;
    }

    /**
     * list non tree
     * bisa by id parent category
     */
    function listCategory(Request $request) {
        $post = $request->json()->all();

        if (!empty($post)) {
            $list = $this->getData($post);
        } else {
            $list = ProductCategory::where('id_parent_category', null)->get();

            foreach ($list as $key => $value) {
                $child = ProductCategory::where('id_parent_category', $value['id_product_category'])->get();
                $list[$key]['child'] = $child;
            }
        }

        return response()->json(MyHelper::checkGet($list));
    }

    /**
     * list tree
     * bisa by id parent category
     */
    function listCategoryTree(Request $request) {
        $post = $request->json()->all();

        $category = $this->getData($post);
        
        if (!empty($category)) {
            $category = $this->createTree($category, $post);
        }
		
		if(isset($post['id_outlet'])){
			$uncategorized = Product::join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$post['id_outlet'])
									->where('product_prices.product_visibility','=','Visible')
									->where('product_prices.product_status','=','Active')
									->whereNull('products.id_product_category')
									->with(['photos'])
									->get()
									->toArray();
			
		} else {
			$defaultoutlet = Setting::where('key','=','default_outlet')->first();
			$uncategorized = Product::join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$defaultoutlet['value'])
									->where('product_prices.product_visibility','=','Visible')
									->where('product_prices.product_status','=','Active')
									->whereNull('products.id_product_category')
									->with(['photos'])
									->get()
									->toArray();
		}
		
		$result = array();
		$result['categorized'] = $category;
		$result['uncategorized_name'] = "Product";
		$result['uncategorized'] = $uncategorized;

        return response()->json(MyHelper::checkGet($result));
    }

    function getData($post=[]) {
        // $category = ProductCategory::select('*', DB::raw('if(product_category_photo is not null, (select concat("'.env('APP_API_URL').'", product_category_photo)), "'.env('APP_API_URL').'assets/pages/img/noimg-500-375.png") as url_product_category_photo'));
        $category = ProductCategory::with(['parentCategory'])->select('*');

        if (isset($post['id_parent_category'])) {

            if (is_null($post['id_parent_category']) || $post['id_parent_category'] == 0) {
                $category->master();
            }
            else {
                $category->parents($post['id_parent_category']);
            }
        }

        if (isset($post['id_product_category'])) {
            $category->id($post['id_product_category']);
        }

        $category = $category->orderBy('product_category_order')->get()->toArray();

        return $category;
    }

    /**
     * list 
     */

    public function createTree($root, $post=[]){
        // print_r($root); exit();
        $node = [];

        foreach($root as $i => $r){
            $child = $this->getData(['id_parent_category' => $r['id_product_category']]);

            if(count($child) > 0){
                $r['child'] = $this->createTree($child, $post);
            }
            else $r['child'] = [];

            $product = $this->getDataProduk($r['id_product_category'], $post);
            $r['product'] = $product;

            array_push($node,$r);
        }

        return $node;
    }

    public function getDataProduk($id, $post=[]) {
        if (isset($post['id_outlet'])) { 
			$product = Product::join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$post['id_outlet'])
									->where('product_prices.product_visibility','=','Visible')
									->where('product_prices.product_status','=','Active')
									->where('products.id_product_category', $id)
									->with(['photos'])
									->get();
        } else {
			$defaultoutlet = Setting::where('key','=','default_outlet')->first();
			$product = Product::join('product_prices','product_prices.id_product','=','products.id_product')
									->where('product_prices.id_outlet','=',$defaultoutlet['value'])
									->where('product_prices.product_visibility','=','Visible')
									->where('product_prices.product_status','=','Active')
									->where('products.id_product_category', $id)
									->with(['photos'])
									->get();
		}
        return $product;
    }
}
