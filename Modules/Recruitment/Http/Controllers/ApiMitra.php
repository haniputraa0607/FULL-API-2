<?php

namespace Modules\Recruitment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;

use App\Lib\MyHelper;
use DB;

class ApiMitra extends Controller
{
    public function __construct() {
        date_default_timezone_set('Asia/Jakarta');
    }

    public function splash(Request $request){
    	$getSetting = Setting::whereIn('key',[
			    		'default_splash_screen_mitra_apps', 
			    		'default_splash_screen_mitra_apps_duration'
			    	])->get()->keyBy('key');

        $splash = $getSetting['default_splash_screen_mitra_apps']['value'] ?? null;
        $duration = $getSetting['default_splash_screen_mitra_apps_duration']['value'] ?? 5;

        if (!empty($splash)) {
            $splash = config('url.storage_url_api').$splash;
        } else {
            $splash = null;
        }
        
        $ext = explode('.', $splash);
        $result = [
            'status' => 'success',
            'result' => [
                'splash_screen_url' => $splash."?update=".time(),
                'splash_screen_duration' => $duration,
                'splash_screen_ext' => '.'.end($ext)
            ]
        ];
        return $result;
    }
}
