<?php

namespace Modules\Academy\Http\Controllers;

use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPhoto;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Academy\Entities\Theory;
use Modules\Academy\Entities\TheoryCategory;
use Modules\Franchise\Entities\Setting;
use Modules\Outlet\Http\Requests\Outlet\OutletList;
use Modules\POS\Http\Requests\reqBulkMenu;
use Modules\Product\Entities\ProductDetail;
use DB;
use App\Lib\MyHelper;
use Modules\Recruitment\Entities\UserHairStylistTheory;
use Modules\Transaction\Entities\TransactionAcademyScheduleTheory;

class ApiTheoryController extends Controller
{
    public function createCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['parent_name'])){
            $save = TheoryCategory::create([
                'id_parent_theory_category' => 0,
                'theory_category_name' => $post['parent_name']
            ]);

            $childs = [];
            if($save && !empty($post['child'])){
                foreach ($post['child'] as $child){
                    $childs[] = [
                        'id_parent_theory_category' => $save['id_theory_category'],
                        'theory_category_name' => $child,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }

                $save = TheoryCategory::insert($childs);
            }
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Parent name can not be empty']]);
        }
    }

    public function listCategory(Request $request){
        $post = $request->json()->all();
        if(!empty($post['id_theory_category'])){
            $list = TheoryCategory::where('id_theory_category', $post['id_theory_category'])->first();

            if(!empty($list)){
                $list = $list->toArray();
                $child = TheoryCategory::where('id_parent_theory_category', $list['id_theory_category'])->get()->toArray();
                $list['child'] = $child;
            }

        }else{
            $list = TheoryCategory::where('id_parent_theory_category', 0)->get()->toArray();

            foreach ($list as $key => $value) {
                $child = TheoryCategory::where('id_parent_theory_category', $value['id_theory_category'])->get()->toArray();
                $list[$key]['child'] = $child;
            }

        }

        return response()->json(MyHelper::checkGet($list));
    }

    public function updateCategory(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_theory_category'])){
            $save = TheoryCategory::where('id_theory_category', $post['id_theory_category'])->update([
                'theory_category_name' => $post['parent_name']
            ]);

            if($save && !empty($post['child'])){
                $idChild = [];
                foreach ($post['child'] as $child){
                    if(!empty($child['id_theory_category'])){
                        $idChild[] = $child['id_theory_category'];
                        $save = TheoryCategory::where('id_theory_category', $child['id_theory_category'])->update(['theory_category_name' => $child['title']]);
                    }else{
                        $save = TheoryCategory::create([
                            'id_parent_theory_category' => $post['id_theory_category'],
                            'theory_category_name' => $child['title']
                        ]);
                        if($save){
                            $idChild[] = $save['id_theory_category'];
                        }
                    }
                }

                $idChild = array_filter($idChild);
                $notIn = TheoryCategory::whereNotIn('id_theory_category', $idChild)->where('id_parent_theory_category', $post['id_theory_category'])
                        ->pluck('id_theory_category')->toArray();
                if(!empty($notIn)){
                    $save = TheoryCategory::whereIn('id_theory_category', $notIn)->delete();
                }
            }
            return response()->json(MyHelper::checkUpdate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function theoryList(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_theory'])){
            $data = Theory::leftJoin('theory_categories', 'theory_categories.id_theory_category', 'theories.id_theory_category')
                    ->where('id_theory', $post['id_theory'])->first();

            if(!empty($data)){
                $data['parent_name'] = TheoryCategory::where('id_theory_category', $data['id_parent_theory_category'])->first()['theory_category_name']??'';
            }
        }else{
            $data = Theory::leftJoin('theory_categories', 'theory_categories.id_theory_category', 'theories.id_theory_category')
                    ->leftJoin('theory_categories as parent', 'theory_categories.id_parent_theory_category', 'parent.id_theory_category');

            if(!empty($post['id_category']) && $post['id_category'] != 'all'){
                $check = strpos($post['id_category'],"all");
                if($check === false){
                    $data = $data->where('theory_categories.id_theory_category', $post['id_category']);
                }else{
                    $id = str_replace('all-', '', $post['id_category']);
                    $data = $data->where('parent.id_theory_category', $id);
                }
            }

            $data = $data->select('theories.*', 'theory_categories.*', 'parent.theory_category_name as parent_name')->get()->toArray();
        }

        return response()->json(MyHelper::checkGet($data));
    }

    public function theoryCreate(Request $request){
        $post = $request->json()->all();

        if(!empty($post['theory'])){
            $insert = [];
            foreach ($post['theory'] as $theory){
                $insert[] =[
                    'id_theory_category' => $post['id_theory_category'],
                    'theory_title' => $theory['title'],
                    'minimum_score' => $theory['minimum_score'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            $save = Theory::insert($insert);
            return response()->json(MyHelper::checkCreate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['Theory can not be empty']]);
        }
    }

    public function theoryUpdate(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_theory'])){
            $save = Theory::where('id_theory', $post['id_theory'])->update([
                'id_theory_category' => $post['id_theory_category'],
                'theory_title' => $post['theory_title'],
                'minimum_score' => $post['minimum_score']
            ]);
            return response()->json(MyHelper::checkCreate($save));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function theoryDelete(Request $request){
        $post = $request->json()->all();

        if(!empty($post['id_theory'])){
            $checkInHS = UserHairStylistTheory::where('id_theory', $post['id_theory'])->first();
            if(!empty($checkInHS)){
                return response()->json(['status' => 'fail', 'messages' => ['Can not delete this theory. Theory already use in hair stylist or academy.']]);
            }

            $checkITrx = TransactionAcademyScheduleTheory::where('id_theory', $post['id_theory'])->first();
            if(!empty($checkITrx)){
                return response()->json(['status' => 'fail', 'messages' => ['Can not delete this theory. Theory already use in hair stylist or academy.']]);
            }

            $delete = Theory::where('id_theory', $post['id_theory'])->delete();
            return response()->json(MyHelper::checkDelete($delete));
        }else{
            return response()->json(['status' => 'fail', 'messages' => ['ID can not be empty']]);
        }
    }

    public function categoryTheory(){
        $data = TheoryCategory::where('id_parent_theory_category', 0)->with(['theory'])->get()->toArray();

        foreach ($data as $key=>$dt){
            $child = TheoryCategory::where('id_parent_theory_category', $dt['id_theory_category'])->with(['theory'])->get()->toArray();
            $data[$key]['child'] = $child;
        }

        return response()->json(MyHelper::checkGet($data));
    }
}
