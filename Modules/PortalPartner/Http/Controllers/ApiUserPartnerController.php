<?php

namespace Modules\PortalPartner\Http\Controllers;

use App\Http\Models\Autocrm;
use App\Http\Models\Outlet;
use App\Http\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Lib\MyHelper;
use App\Jobs\SendEmailUserFranchiseJob;
use Illuminate\Support\Facades\Auth;
use Modules\BusinessDevelopment\Entities\Location;

class ApiUserPartnerController extends Controller
{
    public function __construct()
    {
        date_default_timezone_set('Asia/Jakarta');
        $this->autocrm          = "Modules\Autocrm\Http\Controllers\ApiAutoCrm";
    }

    function coba(Request $request) {
        $user = auth()->user();
        return response()->json(['success' => $user], 200);
    }
    function updateFirstPin(Request $request){
        $post = $request->json()->all();
        if(isset($post['password']) && !empty($post['password'])){
            if($post['password'] != $post['password2']){
                return response()->json(['status' => 'fail', 'messages' => ["Password don't match"]]);
            }
            $upadte = Partner::where('id_partner', auth()->user()->id_partner)->update(['password' => bcrypt($post['password']), 'first_update_password' => 1]);
            return response()->json(MyHelper::checkUpdate($upadte));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Password can not be empty']]);
        }
    }

    public function resetPassword(Request $request){
        $post = $request->json()->all();

        if(isset($post['email']) && !empty($post['email']) &&
            isset($post['phone']) && !empty($post['phone'])){
            $user = Partner::where('email', $post['email'])->where('phone', $post['phone'])->first();
            if(empty($user)){
                return response()->json(['status' => 'fail', 'messages' => ['User not found']]);
            }

            $pin = MyHelper::createrandom(6);
            $dataUpdate['password'] = bcrypt($pin);
            $dataUpdate['first_update_password'] =0;
            $update = Partner::where('id_partner', $user['id_partner'])->update($dataUpdate);

            if($update){
                $autocrm = app($this->autocrm)->SendAutoCRM(
                    'Reset Password Partner',
                    $post['phone'],
                    [
                        'password' => $pin,
                        'email' => $user['email'],
                        'phone' => $user['phone'],
                        'name' => $user['name'],
                    ], null, false, false, 'partners', null,true, 1
                );
            }
            return response()->json(MyHelper::checkUpdate($update));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Email can not be empty']]);
        }

    }
    function detail(Request $request){
        $post = $request->json()->all();
        $data = [];
        if(isset($post['username']) && !empty($post['username'])){
            $data = Partner::where('phone', $post['username'])->first();
        }elseif (isset($post['id_partner']) && !empty($post['id_partner'])){
            $data = Partner::where('id_partner', $post['id_partner'])->first();
        }
        return response()->json(MyHelper::checkGet($data));
    }

    public function idOutlet(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_partner']) && !empty($post['id_partner'])){
            $id_branch = Location::where('id_partner',$post['id_partner'])->get();
            $id_branch = $id_branch[0]['id_location'];
            if($id_branch){
                $data_outlet = Outlet::where('id_location',$id_branch)->get();
                if(count($data_outlet)>0){
                    return response()->json(['status' => 'success', 'result' => $data_outlet[0]['id_outlet']]);    
                }else{
                    return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
                }
            }else{
                return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
    public function listidOutlet(Request $request){
        $post = $request->json()->all();
        if(isset($post['id_partner']) && !empty($post['id_partner'])){
            $id_branch = Location::join('outlets','outlets.id_location','locations.id_location')
                        ->select('id_outlet','outlet_name')
                        ->where('locations.id_partner',$post['id_partner'])->get();
                    return response()->json(['status' => 'success', 'result' => $id_branch]);    
           
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incomplete data']]);
        }
    }
} 
 