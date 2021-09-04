<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Lib\MyHelper;
use DB;

class ApiPartnersController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        if(isset($post['page'])){
            $partner = Partner::orderBy('updated_at', 'desc')->paginate($request->length ?: 10);
        }else{
            $partner = Partner::orderBy('updated_at', 'desc')->get()->toArray();
        }
        return MyHelper::checkGet($partner);
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
        $post = $request->all();
        return $post['data'];
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
    public function edit(Request $request)
    {
        $post = $request->all();
        if(isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])){
            $partner = Partner::where('id_user_franchise', $post['id_user_franchise'])->first();

            return response()->json(['status' => 'success', 'result' => [
                'partner' => $partner,
            ]]);
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Incompleted Data']]);
        }
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
        if (isset($post['id_user_franchise']) && !empty($post['id_user_franchise'])) {
            DB::beginTransaction();
            $data_update = [
                "name" => $post['name'],
                "phone" => $post['phone'],
                "email" => $post['email'],
                "address" => $post['address'],
                "ownership_status" => $post['ownership_status'],
                "cooperation_scheme" => $post['cooperation_scheme'],
                "id_bank_account" => $post['id_bank_account'],
                "status" => $post['status'],
                "password" => $post['password'],
            ];
            $update = Partner::where('id_user_franchise', $post['id_user_franchise'])->update($data_update);
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
    public function destroy(Request $request)
    {
        $id_user_franchise  = $request->json('id_user_franchise');
        $delete = Partner::where('id_user_franchise', $id_user_franchise)->delete();
        return MyHelper::checkDelete($delete);
    }
}
