<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Setting\Http\Requests\Version\VersionList;

use App\Http\Models\Setting;
use Modules\Setting\Entities\Version;

use App\Lib\MyHelper;
use DB;

class ApiVersion extends Controller
{
    public function index(VersionList $request)
    {
        /*Start check status maintenance mode for apps*/
        $getMaintenance = Setting::where('key', 'maintenance_mode')->first();
        if($getMaintenance && $getMaintenance['value'] == 1){
            $dt = (array)json_decode($getMaintenance['value_text']);
            $message = $dt['message'];
            if($dt['image'] != ""){
                $url_image = config('url.storage_url_api').$dt['image'];
            }else{
                $url_image = config('url.storage_url_api').'img/maintenance/default.png';
            }
            return response()->json([
                'status' => 'fail',
                'messages' => [$message],
                'maintenance' => config('url.api_url') ."api/maintenance-mode",
                'data_maintenance' => [
                    'url_image' => $url_image,
                    'text' => $message
                ]
            ], 200);
        }
        /*=======================End====================*/
        $post = $request->json()->all();
        $dbSetting = Setting::where('key', 'like', 'version_%')->get()->toArray();
        $dbDevice = Version::select('app_type', 'app_version')->orderBy('app_version', 'desc')->where('rules', '1')->get()->toArray();
        $setting = array();
        foreach ($dbSetting as $val) {
            $setting[$val['key']] = $val['value'];
        }
        $setting['Device'] = $dbDevice;
        $device = null;
        if (isset($post['device'])) {
            $device = $post['device'];
        } else {
            $agent = $_SERVER['HTTP_USER_AGENT'];
            if (stristr($agent, 'okhttp')) $device = 'android';
            if (stristr($agent, 'android')) $device = 'android';
            if (stristr($agent, 'ios')) $device = 'ios';
        }
        if ($device != null) {
            if ($device == 'android') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('Android', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_mobile'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_mobile']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_mobile'],
                    'text' => $setting['version_text_alert_mobile'],
                    'button_text' => $setting['version_text_button_mobile'],
                    'button_url' => $setting['version_playstore']
                ]);
            }
            if ($device == 'ios') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('IOS', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_mobile'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_mobile']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_mobile'],
                    'text' => $setting['version_text_alert_mobile'],
                    'button_text' => $setting['version_text_button_mobile'],
                    'button_url' => $setting['version_appstore']
                ]);
            }
            if ($device == 'outlet') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('OutletApp', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_outlet'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_outlet']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_outlet'],
                    'text' => $setting['version_text_alert_outlet'],
                    'button_text' => $setting['version_text_button_outlet'],
                    'button_url' => $setting['version_outletstore']
                ]);
            }
            if (strpos($device, 'mitra_ios') !== false) {
                foreach ($setting['Device'] as $value) {
                    if (in_array('MitraAppIOS', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_mitra'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_mitra']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_mitra'],
                    'text' => $setting['version_text_alert_mitra'],
                    'button_text' => $setting['version_text_button_mitra'],
                    'button_url' => $setting['version_mitra_appstore']
                ]);
            }
            if (strpos($device, 'mitra') !== false) {
                foreach ($setting['Device'] as $value) {
                    if (in_array('MitraApp', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_mitra'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_mitra']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_mitra'],
                    'text' => $setting['version_text_alert_mitra'],
                    'button_text' => $setting['version_text_button_mitra'],
                    'button_url' => $setting['version_mitrastore']
                ]);
            }
            if ($device == 'web') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('WebApp', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }

                if (empty($compare_version)) {
                	return response()->json(['status' => 'fail', 'message' => 'Versi tidak ditemukan']);
                }

                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert_web'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_web']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_web'],
                    'text' => $setting['version_text_alert_web'],
                    'button_text' => $setting['version_text_button_web'],
                    'button_url' => $setting['version_webstore']
                ]);
            }
            if ($device == 'employee_android') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('EmployeeAndroid', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_employee']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_employee'],
                    'text' => $setting['version_text_alert'],
                    'button_text' => $setting['version_text_button_employee'],
                    'button_url' => $setting['version_employee_playstore']
                ]);
            }
            if ($device == 'employee_ios') {
                foreach ($setting['Device'] as $value) {
                    if (in_array('EmployeeIOS', $value)) {
                        $value['app_type'] = strtolower($value['app_type']);
                        $compare_version[] = $value;
                    }
                }
                for ($i = 0; $i < count($compare_version); $i++) {
                    if ($post['version'] == $compare_version[$i]['app_version']) {
                        return response()->json(['status' => 'success']);
                    }
                }
                $versionRec = array_shift($compare_version);
                $setting['version_text_alert'] = str_replace('%version_app%', $versionRec['app_version'], $setting['version_text_alert_employee']);
                return response()->json([
                    'status' => 'fail',
                    'image' => config('url.storage_url_api') . $setting['version_image_employee'],
                    'text' => $setting['version_text_alert'],
                    'button_text' => $setting['version_text_button_employee'],
                    'button_url' => $setting['version_employee_appstore']
                ]);
            }
        } else {
            return response()->json(['status' => 'fail', 'message' => 'Device tidak teridentifikasi']);
        }
    }

    function getVersion()
    {
        $display = Setting::where('key', 'LIKE', 'version%')->get();
        $version = Version::select('app_type', 'app_version', 'rules')
        			->whereNotIn('app_type', ['OutletApp'])
        			->orderBy('app_version', 'desc')
        			->get()
        			->GroupBy('app_type');

        $result = [];
        foreach ($display as $data) {
            $result[$data['key']] = $data['value'];
        }

        $result['Android'] = $version['Android'] ?? [];
        $result['IOS'] = $version['IOS'] ?? [];
        $result['OutletApp'] = $version['OutletApp'] ?? [];
        $result['MitraApp'] = $version['MitraApp'] ?? [];
        $result['MitraAppIOS'] = $version['MitraAppIOS'] ?? [];
        $result['EmployeeAndroid'] = $version['EmployeeAndroid'] ?? [];
        $result['EmployeeIOS'] = $version['EmployeeIOS'] ?? [];
        $result['WebApp'] = $version['WebApp'] ?? [];

        return response()->json(MyHelper::checkGet($result));
    }

    function updateVersion(Request $request)
    {
        $post = $request->json()->all();
        DB::beginTransaction();
        foreach ($post as $key => $data) {
            if ($key == 'Display') {
                foreach ($data as $keyData => $value) {
                    if ($keyData == 'version_image_mobile' 
                    	|| $keyData == 'version_image_outlet' 
                    	|| $keyData == 'version_image_mitra'
                    	|| $keyData == 'version_image_employee'
                    	|| $keyData == 'version_image_web'
                    ) {
                        if (!file_exists('img/setting/version/')) {
                            mkdir('img/setting/version/', 0777, true);
                        }
                        $upload = MyHelper::uploadPhoto($value, 'img/setting/version/');
                        if (isset($upload['status']) && $upload['status'] == "success") {
                            $value = $upload['path'];
                        } else {
                            return false;
                        }
                    }
                    $setting = Setting::updateOrCreate(['key' => $keyData], ['value' => $value]);
                    if (!$setting) {
                        DB::rollBack();
                        return response()->json(['status' => 'fail', 'messages' => $setting]);
                    }
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            } else {
                $store = array_slice($data, -2, 2);
                foreach ($store as $keyStore => $value) {
                    $setting = Setting::updateOrCreate(['key' => $keyStore], ['value' => $value]);
                }
                if (!$setting) {
                    DB::rollBack();
                    return response()->json(['status' => 'fail', 'messages' => $setting]);
                }
                $sumVersion = array_pop($data);
                array_pop($data);
                // dd($data);
                if ($data == null) {
                    Version::where('app_type', $key)->delete();
                } else {
                    foreach ($data as $value) {
                        $reindex[] = $value;
                    }
                    for ($i = 0; $i < count($reindex); $i++) {
                        $reindex[$i]['app_type'] = $key;
                    }
                    foreach ($reindex as $value) {
                        if ($value['rules'] == 1) {
                            $checkData[] = $value;
                        }
                    }
                    if (count($checkData) > $sumVersion) {
                        asort($checkData);
                        $lastVersion = array_slice($checkData, -$sumVersion, $sumVersion);
                        $versionLast = array_column($lastVersion, 'app_version');
                    }
                    Version::where('app_type', $key)->delete();
                    foreach ($reindex as $value) {
                        if (!isset($versionLast)) {
                            $version = new Version;
                            $version->app_version = $value['app_version'];
                            $version->app_type = $value['app_type'];
                            $version->rules = $value['rules'];
                            $version->save();
                        } else {
                            if (in_array($value['app_version'], $versionLast)) {
                                $version = new Version;
                                $version->app_version = $value['app_version'];
                                $version->app_type = $value['app_type'];
                                $version->rules = $value['rules'];
                                $version->save();
                            } else {
                                $version = new Version;
                                $version->app_version = $value['app_version'];
                                $version->app_type = $value['app_type'];
                                $version->rules = 0;
                                $version->save();
                            }
                        }
                    }
                }
                DB::commit();
                return response()->json(['status' => 'success']);
            }
        }
    }
}
