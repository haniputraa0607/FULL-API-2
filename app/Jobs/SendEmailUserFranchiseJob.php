<?php

namespace App\Jobs;

use App\Http\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\Disburse\Entities\Disburse;
use Modules\Disburse\Entities\DisburseOutlet;
use DB;
use App\Lib\SendMail as Mail;
use Modules\Franchise\Entities\UserFranchise;
use Rap2hpoutre\FastExcel\FastExcel;
use File;
use Storage;
use App\Lib\MyHelper;

class SendEmailUserFranchiseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data   = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->data as $data){
            $pin = MyHelper::createRandomPIN(6, 'angka');
            $user = UserFranchise::where('id_user_franchise', $data)->first();
            $updatePin = UserFranchise::where('id_user_franchise', $data)->update(['password' => bcrypt($pin)]);

            if($updatePin){
                $autocrm = app('Modules\Autocrm\Http\Controllers\ApiAutoCrm')->SendAutoCRM(
                    'New User Franchise',
                    $user['email'],
                    [
                        'pin_franchise' => $pin,
                        'email' => $user['email'],
                        'name' => $user['name'],
                        'url' => env('URL_PORTAL_MITRA')
                    ], null, false, false, 'franchise', 1
                );
            }
        }

        return true;
    }
}
