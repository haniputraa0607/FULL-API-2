<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\Location;
use App\Lib\MyHelper;
use DB;

class ApiLocationsController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index(Request $request)
    {
        $post = $request->all();
        if(isset($post['page'])){
            $locations = Location::orderBy('updated_at', 'desc')->paginate($request->length ?: 10);
        }else{
            $locations = Location::orderBy('updated_at', 'desc')->get()->toArray();
        }
        return MyHelper::checkGet($locations);
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
        //
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
        if(isset($post['id_location']) && !empty($post['id_location'])){
            $location = Location::where('id_location', $post['id_location'])->first();

            return response()->json(['status' => 'success', 'result' => [
                'location' => $location,
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
        if (isset($post['id_location']) && !empty($post['id_location'])) {
            DB::beginTransaction();
            $data_update = [
                "name" => $post['name'],
                "address" => $post['address'],
                "id_city" => $post['id_city'],
                "latitude" => $post['latitude'],
                "longitude" => $post['longitude'],
                "pic_name" => $post['pic_name'],
                "pic_contact" => $post['pic_contact'],
                "id_user_franchise" => $post['id_user_franchise'],
            ];
            $update = Location::where('id_location', $post['id_location'])->update($data_update);
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
        $id_location  = $request->json('id_location');
        $delete = Location::where('id_location', $id_location)->delete();
        return MyHelper::checkDelete($delete);
    }
}
