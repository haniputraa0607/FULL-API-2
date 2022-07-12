<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Models\Feature;
use App\Http\Models\UserFeature;
use App\Http\Models\User;
use App\Http\Models\Subdistrict;
use App\Http\Models\City;
use App\Http\Models\Province;
use App\Http\Models\Level;
use App\Http\Models\Configs;
use App\Http\Models\Courier;
use App\Http\Models\Setting;
use Modules\Users\Entities\Role;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Lib\MyHelper;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	function __construct(){
      date_default_timezone_set('Asia/Jakarta');
    }
	
	function getFeatureControl(Request $request){
		$user = json_decode($request->user(), true);

		if($user['level'] == 'Super Admin'){
			$checkFeature = Feature::select('id_feature')->where('show_hide', 1)->get()->toArray();
		}else{
			$checkFeature = Role::join('roles_features', 'roles_features.id_role', 'roles.id_role')
							->join('features', 'features.id_feature', 'roles_features.id_feature')
							->where([
								['roles.id_role', $user['id_role']],
								['features.show_hide', 1]
							])
							->select('features.id_feature')->get()->toArray();
		}
		$result = [
			'status'  => 'success',
			'result'  => array_pluck($checkFeature, 'id_feature')
		];

      return response()->json($result);
    }
	
	function getFeature(Request $request){
	
		$checkFeature = Feature::where('show_hide', 1)->orderBy('order', 'asc')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => $checkFeature
		];
		return response()->json($result);
    }
	
	function getFeatureModule(Request $request){
	
		$checkFeature = Feature::select('feature_module')->where('show_hide', 1)->orderBy('order', 'asc')->groupBy('feature_module')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => $checkFeature
		];
		return response()->json($result);
    }
	
	function listCity(Request $request){
		$post = $request->json()->all();

		$query = City::select('*');
		if (isset($post['id_province'])) {
			$query->where('id_province', $post['id_province']);
		}

		$query = $query->get()->toArray();

		return [
    		'status' => 'success',
    		'result' => $query
    	];
	}

	function listProvince(Request $request){
		$query = (new Province)->newQuery();
		if($id_city=$request->json('id_city')){
			$query->whereHas('cities',function($query) use ($id_city){
				$query->where('id_city',$id_city);
			});
		}
		return MyHelper::checkGet($query->get()->toArray()); 
	}
	
	function listCourier(){
		$query = Courier::where('status','Active')->get()->toArray();
		return MyHelper::checkGet($query); 
	}
	
	function listRank(){
		$query = Level::get()->toArray();
		return MyHelper::checkGet($query); 
	}

	function getConfig(Request $request){
		$config = Configs::select('id_config')->where('is_active', '1')->get()->toArray();
		$result = [
			'status'  => 'success',
			'result'  => array_pluck($config, 'id_config')
		];

      return response()->json($result);
	}
	
	function uploadImageSummernote(Request $request) {
		$post = $request->json()->all();

		if (!file_exists('img/summernote/'.$post['type'])) {
			mkdir('img/summernote/'.$post['type'], 0777, true);
		}

        $upload = MyHelper::uploadPhotoSummerNote($request->json('image'), 'img/summernote/'.$post['type'].'/', null);
        
        if ($upload['status'] == "success") {
            $result = [
                'status' => 'success',
                'result' => [
                    'pathinfo' => config('url.storage_url_api').$upload['path'],
                    'path' => $upload['path']
                ]
            ];
        }
        else {
            $result = [
                'status' => 'fail'
            ];
        }

        return response()->json($result);
	}
	
    function deleteImageSummernote(Request $request) {
        if (MyHelper::deletePhoto($request->json('image'))) {
            $result = [
                'status' => 'success'
            ];
        }
        else {
            $result = [
                'status' => 'fail'
            ];
        }

        return response()->json($result);
    }

    function maintenance(){
        $get = Setting::where('key', 'maintenance_mode')->first();
        if($get){
            $dt = (array)json_decode($get['value_text']);
            $data['status'] = $get['value'];
            $data['message'] = $dt['message'];
            if($dt['image'] != ""){
                $data['image'] = config('url.storage_url_api').$dt['image'];
            }else{
                $data['image'] = config('url.storage_url_api').'img/maintenance/default.png';
            }
        }
        return view('webview.maintenance_mode', $data);
    }

    function listSubdistrict(Request $request){
		$post = $request->json()->all();

		$query = Subdistrict::join('cities', 'cities.id_city', 'subdistricts.id_city')
				->join('provinces', 'provinces.id_province', 'cities.id_province');

		if (isset($post['id_city'])) {
			$query = $query->where('subdistricts.id_city', $post['id_city']);
		}

		if (isset($post['keyword'])) {
			$query = $query->where(function ($q) use ($post){
				$q->where('subdistrict_name', 'like', '%' . $post['keyword'] . '%')
					->orWhere('city_name', 'like', '%' . $post['keyword'] . '%')
					->orWhere('province_name', 'like', '%' . $post['keyword'] . '%');
			});
		}

        if ($request->page) {
            $query = $query->paginate(10)->toArray();
        } else {
            $query = $query->get()->toArray();
        }

    	return [
    		'status' => 'success',
    		'result' => $query
    	];
	}

	public function postLog(Request $request)
	{
        $filename = 'log_post/log' . date('YmdHis') . '-' . rand(0,9) . '.json';
		file_put_contents($filename, json_encode($request->all()));
		return [
			'status' => 'success',
			'log_url' => url($filename),
			'result' => $request->all()
		];
	}

    public function getSidebarBadge(Request $request)
    {
    	return [
    		'status' => 'success',
    		'result' => [
    			// 'total_home' => 5,
    		],
    	];
    }
}
