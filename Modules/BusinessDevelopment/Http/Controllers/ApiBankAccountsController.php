<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Lib\MyHelper;
use DB;
use Modules\Disburse\Entities\BankAccount;

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
    public function store(Request $request)
    {
        $post= $request->all();
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
            DB::commit();
            return response()->json(MyHelper::checkCreate($store));
        } else {
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }    
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('businessdevelopment::show');
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
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
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
}
