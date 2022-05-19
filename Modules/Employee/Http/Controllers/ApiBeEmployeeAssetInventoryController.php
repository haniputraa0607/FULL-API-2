<?php

namespace Modules\Employee\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;
use Modules\Employee\Http\Requests\AssetInventory\CreateCategoryAssetInventory;
use App\Http\Models\User;
use Session;
use DB;
use Modules\Employee\Entities\AssetInventory;
use Modules\Employee\Entities\CategoryAssetInventory;

class ApiBeEmployeeAssetInventoryController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        if (\Module::collections()->has('Autocrm')) {
            $this->autocrm  = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
        }
        $this->saveFile = "document/asset_inventory/"; 
    }
    public function list_category() {
        $user = CategoryAssetInventory::select([
            'id_asset_inventory_category',
            'name_category_asset_inventory'
        ])->get();
        return MyHelper::checkGet($user);
    }
    public function create_category(CreateCategoryAssetInventory $request) {
        $post = $request->all();
        $user = CategoryAssetInventory::create($post);
        return MyHelper::checkGet($user);
    }
    public function delete_category(Request $request) {
        $post = $request->all();
        $user = CategoryAssetInventory::where(array(
            'id_asset_inventory_category'=>$request->id_asset_inventory_category
        ))->delete();
        return MyHelper::checkGet($user);
    }
}
