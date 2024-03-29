<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Models\Setting;
use App\Http\Models\User;
use App\Lib\MyHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Employee\Entities\EmployeeDevice;

class ApiEmployeeAppController extends Controller
{
    public function saveDeviceUser(Request $request)
    {
        $user = $request->user();
        $data = $request->all();
        if (isset($data['device_id']) && isset($data['device_token']) && isset($data['device_type'])) {
            $device = $this->updateDeviceUser($user, $data['device_id'], $data['device_token'], $data['device_type']);
            if ($device) {
                return response()->json(['status' => 'success', 'messages' => ['Success to update Device User']]);
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Failed to update Device User']]);
            }
        } else {
            if(isset($data['device_type']) && $data['device_type'] == 'web'){
                return response()->json(['status' => 'success', 'messages' => []]);
            }
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }

    }

    public function updateDeviceUser($user, $device_id, $device_token, $device_type)
    {
        $dataUpdate = [
            'device_id'    => $device_id,
            'device_token' => $device_token,
            'device_type'  => $device_type,
        ];

        $checkDevice = EmployeeDevice::where('id_employee', $user->id)
            ->where('device_id', $device_id)
            ->where('device_type', $device_type)
            ->count();

        $update = EmployeeDevice::updateOrCreate(['device_id' => $device_id], [
            'id_employee'  => $user->id,
            'device_token' => $device_token,
            'device_type'  => $device_type,
        ]);

        $result = false;
        if ($update) {
            if ($device_type == 'Android') {
                $query = User::where('id', '=', $user->id)->update(['android_device' => $device_id]);
            }

            if ($device_type == 'IOS') {
                $query = User::where('id', '=', $user->id)->update(['ios_device' => $device_id]);
            }

            $result = true;
        }

        return $result;
    }

    public function splash(Request $request)
    {
        $splash   = Setting::where('key', '=', 'default_splash_screen_employee_apps')->first();
        $duration = Setting::where('key', '=', 'default_splash_screen_employee_apps_duration')->pluck('value')->first();

        if (!empty($splash)) {
            $splash = config('url.storage_url_api') . $splash['value'] . '?v=' . time();
            $ext    = explode('.', $splash);
        } else {
            $splash = null;
            $ext    = null;
        }

        $result = [
            'status' => 'success',
            'result' => [
                'splash_screen_url'      => $splash,
                'splash_screen_duration' => (int) ($duration ?? 5),
                'splash_screen_ext'      => $ext ? '.' . end($ext) : null,
            ],
        ];
        return $result;
    }

    public function loggedUser(Request $request)
    {
        $user = $request->user();
        $user->load('outlet', 'role');
        return MyHelper::checkGet([
            'id_user' => $user->id,
            'name' => $user->name,
            'office_branch' => optional($user->outlet)->outlet_name ?: '-',
            'role' => optional($user->role)->role_name ?: '-',
        ]);
    }
}
