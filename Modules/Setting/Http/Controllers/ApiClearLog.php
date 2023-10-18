<?php

namespace Modules\Setting\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use App\Jobs\ClearJob;
class ApiClearLog extends Controller
{
    public function queueClear() {
        ClearJob::dispatch(true)->allOnConnection('database');
        return 'true';
    }
    public function clearLog() {
        $date = Carbon::now()->subDays(30);
        $dates = Carbon::now()->subDays(90);
        $failed = \App\Http\Models\LogActivitiesApps::where('created_at', '<=', $date)->first();
        $failed = \App\Http\Models\LogActivitiesBE::where('created_at', '<=', $date)->delete();
        $failed = \App\Http\Models\LogActivitiesMitraApp::where('created_at', '<=', $date)->delete();
        $failed = \App\Http\Models\LogActivitiesOutletApps::where('created_at', '<=', $date)->delete();
        $failed = \Modules\Xendit\Entities\LogXendit::where('created_at', '<=', $dates)->delete();
        $failed = \App\Http\Models\FailedJob::where('failed_at', '<=', $date)->first();
        return true;
    }
}
