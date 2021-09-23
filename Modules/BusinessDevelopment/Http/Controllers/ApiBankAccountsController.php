<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use DB;
use Modules\Disburse\Entities\BankAccount;
use Illuminate\Support\Facades\Auth;
use Modules\BusinessDevelopment\Entities\Partner;

class ApiBankAccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('businessdevelopment::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('businessdevelopment::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function detail(Request $request)
    {
        $id_bank_account = $request['id_bank_account'];
        if(isset($id_bank_account) && !empty($id_bank_account)){
            $bank_account = BankAccount::where('id_bank_account', $id_bank_account)->first();
            if($bank_account==null){
                return response()->json(['status' => 'success', 'result' => [
                    'bank_account' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'bank_account' => $bank_account,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('businessdevelopment::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    
    public function update(Request $request)
    {
        $post = $request->all();
        if (isset($post['id_bank_account']) && !empty($post['id_bank_account'])) {
            DB::beginTransaction();
            $data_update = [
                "id_bank_name" => $post['id_bank_name'],
                "beneficiary_name" => $post['beneficiary_name'],
                "beneficiary_account" => $post['beneficiary_account']
            ];
            if (isset($post['beneficiary_alias'])) {
                $data_update['beneficiary_alias'] = $post['beneficiary_alias'];
            }
            if (isset($post['beneficiary_email'])) {
                $data_update['beneficiary_email'] = $post['beneficiary_email'];
            }
            if (isset($post['send_email_to'])) {
                $data_update['send_email_to'] = $post['send_email_to'];
            }
            // return $data_update;
            $update = BankAccount::where('id_bank_account', $post['id_bank_account'])->update($data_update);
            if(!$update){
                DB::rollback();
                return response()->json(['status' => 'fail', 'messages' => ['Failed update product variant']]);
            }
            DB::commit();
            return response()->json(['status' => 'success']);
        }else{
            $data_store = [
                "id_bank_name"   => $post['id_bank_name'],
                "beneficiary_name"   => $post['beneficiary_name'],
                "beneficiary_account"   => $post['beneficiary_account'],
            ];
            if (isset($post['beneficiary_alias'])) {
                $data_store['beneficiary_alias'] = $post['beneficiary_alias'];
            }
            if (isset($post['beneficiary_email'])) {
                $data_store['beneficiary_email'] = $post['beneficiary_email'];
            }
            if (isset($post['send_email_to'])) {
                $data_store['send_email_to'] = $post['send_email_to'];
            }
            if (!empty($post)) {
                DB::beginTransaction();
                $store = BankAccount::create($data_store);
                if(!$store) {
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add bank accunt']]);
                }
                $data_partner = [
                    "id_bank_account" => $store['id_bank_account'],
                ];
                $update_partner = Partner::where('id_partner', $post['id_partner'])->update($data_partner);
                if(!$update_partner){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed add bank accunt']]);
                }
                DB::commit();
                return response()->json(MyHelper::checkCreate($store));
            } else {
                return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
            }   
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
    
    public function updateBanKPartner(Request $request){
        $user = Auth::user();
        $id_partner = $user['id_partner'];
        $post= $request->all();
        if(isset($id_partner) && !empty($id_partner)){
            $id_bank_account = Partner::where('id_partner', $id_partner)->get('id_bank_account')->first()['id_bank_account'];
            if($id_bank_account==null){
                $data_store = [
                    "id_bank_name"   => $post['id_bank_name'],
                    "beneficiary_name"   => $post['beneficiary_name'],
                    "beneficiary_account"   => $post['beneficiary_account'],
                ];
                if (isset($post['beneficiary_alias'])) {
                    $data_store['beneficiary_alias'] = $post['beneficiary_alias'];
                }
                if (isset($post['beneficiary_email'])) {
                    $data_store['beneficiary_email'] = $post['beneficiary_email'];
                }
                if (isset($post['send_email_to'])) {
                    $data_store['send_email_to'] = $post['send_email_to'];
                }
                if (!empty($post)) {
                    DB::beginTransaction();
                    $store = BankAccount::create($data_store);
                    if(!$store) {
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed add bank accunt']]);
                    }
                    $data_partner = [
                        "id_bank_account" => $store['id_bank_account'],
                    ];
                    $update_partner = Partner::where('id_partner', $id_partner)->update($data_partner);
                    if(!$update_partner){
                        DB::rollback();
                        return response()->json(['status' => 'fail', 'messages' => ['Failed add bank accunt']]);
                    }
                    DB::commit();
                    return response()->json(MyHelper::checkCreate($store));
                } else {
                    return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
                }   
            } else {
                $id_bank_account = Partner::where('id_partner', $id_partner)->get('id_bank_account')->first()['id_bank_account'];
                DB::beginTransaction();
                $data_update = [
                    "id_bank_name" => $post['id_bank_name'],
                    "beneficiary_name" => $post['beneficiary_name'],
                    "beneficiary_account" => $post['beneficiary_account']
                ];
                if (isset($post['beneficiary_alias'])) {
                    $data_update['beneficiary_alias'] = $post['beneficiary_alias'];
                }
                if (isset($post['beneficiary_email'])) {
                    $data_update['beneficiary_email'] = $post['beneficiary_email'];
                }
                if (isset($post['send_email_to'])) {
                    $data_update['send_email_to'] = $post['send_email_to'];
                }
                $update = BankAccount::where('id_bank_account', $id_bank_account)->update($data_update);
                if(!$update){
                    DB::rollback();
                    return response()->json(['status' => 'fail', 'messages' => ['Failed update bank account']]);
                }
                DB::commit();
                return response()->json(['status' => 'success', 'messages' => ['Success update bank account']]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }

    public function detailBanKPartner()
    {
        $user = Auth::user();
        $id_partner = $user['id_partner'];
        if(isset($id_partner) && !empty($id_partner)){
            $id_bank_account = Partner::where('id_partner', $id_partner)->get('id_bank_account')->first()['id_bank_account'];
            $bank_account = BankAccount::where('id_bank_account', $id_bank_account)->first();
            if($bank_account==null){
                return response()->json(['status' => 'success', 'result' => [
                    'bank_account' => 'Empty',
                ]]);
            } else {
                return response()->json(['status' => 'success', 'result' => [
                    'bank_account' => $bank_account,
                ]]);
            }
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
    }
}
