<?php

namespace Modules\PortalPartner\Http\Controllers;


//use Modules\Franchise\Entities\Deal;
use App\Http\Models\Deal;
use App\Http\Models\Setting;
use Modules\Franchise\Entities\PromoCampaign;

use Modules\Franchise\Entities\Subscription;

use Modules\Franchise\Entities\Bundling;

use Modules\Franchise\Entities\SubscriptionUserVoucher;

use Modules\Franchise\Entities\TransactionBundlingProduct;

use Modules\Brand\Entities\Brand;
use App\Http\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Subscription\Http\Requests\ListSubscription;
use App\Lib\MyHelper;
use DB;
use Modules\PromoCampaign\Lib\PromoCampaignTools;
use Modules\BusinessDevelopment\Entities\Partner;

class ApiOutletController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function outlet(Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $deals = Outlet::join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))->select('outlets.*')->get();
             return response()->json(MyHelper::checkGet($deals));
       
    }
    public function brand( Request $request)
    {
    	$post = $request->json()->all();
        if(!$request->id_partner){
        	return response()->json(['status' => 'fail', 'messages' => ['ID partner can not be empty']]);
        }
        $deals = Brand::join('brand_outlet','brand_outlet.id_brand','brands.id_brand')
                 ->join('outlets','outlets.id_outlet','brand_outlet.id_outlet')
                 ->join('cities','cities.id_city','outlets.id_city')
                 ->join('locations','locations.id_city','cities.id_city')
                 ->join('partners','partners.id_partner','locations.id_partner')
                 ->where(array('partners.id_partner'=>$request->id_partner))
                ->Select('brands.*')->get();
            return response()->json(MyHelper::checkGet($deals));
       
    }
    
    
}
