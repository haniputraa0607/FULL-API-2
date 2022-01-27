<?php

namespace Modules\BusinessDevelopment\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\BusinessDevelopment\Entities\OutletStarterBundling;
use App\Lib\MyHelper;
use Modules\Product\Entities\ProductIcount;

class ApiOutletStarterBundlingController extends Controller
{
    public function index(Request $request)
    {
        $bundlings = (new OutletStarterBundling)->newQuery();
        $bundlings->orderBy('name');
        return MyHelper::checkGet($bundlings->paginate());
    }

    public function show(Request $request)
    {
        $bundling = OutletStarterBundling::with('bundling_products')->where('id_outlet_starter_bundling', $request->id_outlet_starter_bundling)->orWhere('code', $request->code)->first();
        if (!$bundling) {
            abort(404);
        }

        return MyHelper::checkGet($bundling);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:outlet_starter_bundlings,code',
            'name' => 'required|string',
            'bundling_products.*.id_product_icount' => 'required|exists:product_icounts,id_product_icount',
            'bundling_products.*.qty' => 'required|numeric|min:1',
            'bundling_products.*.unit' => 'required|string',
            'bundling_products.*.budget_code' => 'required|string|in:Invoice,Beban,Assets',
        ]);

        $bundling = OutletStarterBundling::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ? 1 : 0,
        ]);

        if (!$bundling) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Failed create outlet starter bundling'
                ]
            ];
        }

        foreach ($request->bundling_products ?? [] as $bundlingProduct) {
            $bundling->bundling_products()->create([
                'id_product_icount' => $bundlingProduct['id_product_icount'],
                'qty' => $bundlingProduct['qty'],
                'unit' => $bundlingProduct['unit'],
                'budget_code' => $bundlingProduct['budget_code'],
                'description' => $bundlingProduct['description'] ?? null,
            ]);
        }

        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success add outlet starter bundling'
            ]
        ];
    }

    public function update(Request $request)
    {
        $request->validate([
            'id_outlet_starter_bundling' => 'required|exists:outlet_starter_bundlings,id_outlet_starter_bundling',
            'code' => 'required|string',
            'name' => 'required|string',
            'bundling_products.*.id_product_icount' => 'required|exists:product_icounts,id_product_icount',
            'bundling_products.*.qty' => 'required|numeric|min:1',
            'bundling_products.*.unit' => 'required|string',
            'bundling_products.*.budget_code' => 'required|string|in:Invoice,Beban,Assets',
        ]);

        if (OutletStarterBundling::where('code', $request->code)->where('id_outlet_starter_bundling', '<>', $request->id_outlet_starter_bundling)->exists()) {
            return [
                'status' => 'fail',
                'messages' => [
                    'Code already used by another starter bundling'
                ],
            ];
        }

        $bundling = OutletStarterBundling::find($request->id_outlet_starter_bundling);
        \DB::beginTransaction();
        $bundling->update([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'status' => $request->status ? 1 : 0,
        ]);

        $bundling->bundling_products()->delete();
        foreach ($request->bundling_products ?? [] as $bundlingProduct) {
            $bundling->bundling_products()->create([
                'id_product_icount' => $bundlingProduct['id_product_icount'],
                'qty' => $bundlingProduct['qty'],
                'unit' => $bundlingProduct['unit'],
                'budget_code' => $bundlingProduct['budget_code'],
                'description' => $bundlingProduct['description'] ?? null,
            ]);
        }

        \DB::commit();
        return [
            'status' => 'success',
            'result' => [
                'message' => 'Success update outlet starter bundling'
            ]
        ];
    }

    public function delete(Request $request)
    {
        $bundling = OutletStarterBundling::with('bundling_products')->find($request->id_outlet_starter_bundling);
        if (!$bundling) {
            abort(404);
        }

        $delete = $bundling->delete();

        return MyHelper::checkDelete($delete);
    }

    public function productIcountList(Request $request)
    {
        $products = ProductIcount::get();
        return MyHelper::checkGet($products);
    }
}
