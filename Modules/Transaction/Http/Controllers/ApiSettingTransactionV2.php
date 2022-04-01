<?php

namespace Modules\Transaction\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

use App\Http\Models\Setting;
use App\Http\Models\Outlet;
use App\Http\Models\Product;
use App\Http\Models\ProductPrice;
use Modules\Product\Entities\ProductDetail;
use Modules\Product\Entities\ProductGlobalPrice;
use Modules\Product\Entities\ProductSpecialPrice;
use App\Http\Models\ProductModifier;
use App\Http\Models\ProductModifierPrice;
use App\Http\Models\ProductModifierGlobalPrice;
use Modules\ProductBundling\Entities\BundlingProduct;
use Modules\ProductVariant\Entities\ProductVariantGroup;
use App\Lib\MyHelper;

use DB;
use Modules\ProductVariant\Entities\ProductVariantGroupSpecialPrice;

class ApiSettingTransactionV2 extends Controller
{
    public function setting($value) {
        $setting = Setting::where('key', $value)->first();

        if (empty($setting->value)) {
            return response()->json(['Setting Not Found']);
        }

        return $setting->value;
    }

    public function grandTotal() {
        $grandTotal = $this->setting('transaction_grand_total_order');

        $grandTotal = explode(',', $grandTotal);
        foreach ($grandTotal as $key => $value) {
            if (substr($grandTotal[$key], 0, 5) == 'empty') {
                unset($grandTotal[$key]);
            }
        }

        $grandTotal = array_values($grandTotal);
        return $grandTotal;
    }

    public function discount() {
        $discount = $this->setting('transaction_discount_formula');
        return $discount;
    }

    public function tax() {
        $tax = $this->setting('transaction_tax_formula');

        $tax = preg_replace('/\s+/', '', $tax);
        return $tax;
    }

    public function service() {
        $service = $this->setting('transaction_service_formula');

        $service = preg_replace('/\s+/', '', $service);
        return $service;
    }

    public function point() {
        $point = $this->setting('point_acquisition_formula');

        $point = preg_replace('/\s+/', '', $point);
        return $point;
    }

    public function cashback() {
        $cashback = $this->setting('cashback_acquisition_formula');

        $cashback = preg_replace('/\s+/', '', $cashback);
        return $cashback;
    }

    public function pointCount() {
        $point = $this->setting('point_acquisition_formula');
        return $point;
    }

    public function cashbackCount() {
        $cashback = $this->setting('cashback_acquisition_formula');
        return $cashback;
    }

    public function pointValue() {
        $point = $this->setting('point_conversion_value');
        return $point;
    }

    public function cashbackValue() {
        $cashback = $this->setting('cashback_conversion_value');
        return $cashback;
    }

    public function cashbackValueMax() {
        $cashback = $this->setting('cashback_maximum');
        return $cashback;
    }

    public function serviceValue() {
        $service = $this->setting('service');
        return $service;
    }

    public function taxValue() {
        $tax = $this->setting('tax');
        return $tax;
    }

    public function convertFormula($value) {
        $convert = $this->$value();

        return $convert;
    }

    public function countTransaction($value, &$data,&$discount_promo=[]) {
        $subtotal = isset($data['subtotal']) ? $data['subtotal'] : 0;
        $service  = isset($data['service']) ? $data['service'] : 0;
        $tax      = isset($data['tax']) ? $data['tax'] : 0;
        $shipping = isset($data['shipping']) ? $data['shipping'] : 0;
        $discount = isset($data['discount']) ? $data['discount'] : 0;

        if (!isset($data['total_bundling'])) {
            $data['total_bundling'] = 0;
        }
        $totalBundling = isset($data['total_bundling']) ? $data['total_bundling'] : 0;

        // return $data;
        if ($value == 'subtotal') {
            $outlet = (empty($data['id_outlet']) ? []:Outlet::select('id_outlet', 'outlet_different_price', 'is_tax')->where('id_outlet',$data['id_outlet'])->first());
            $different_price = $outlet->outlet_different_price??0;
            $outlet_tax = $outlet->is_tax??0;
            $dataSubtotal = [];
            $dataSubtotalFinal = [];
            $dataSubtotalPerBrand = [];
            $loopable = [];
            if ($discount_promo['item'] ?? false) {
                $loopable = &$discount_promo['item'];
            } elseif(!empty($data['item'])) {
                $loopable = &$data['item'];
            }
            foreach ($loopable as $keyData => &$valueData) {
                $this_discount=0;
                $this_discount=$valueData['discount']??0;

                $product = Product::with('product_discounts', 'product_prices')->where('id_product', $valueData['id_product'])->first();
                if (empty($product)) {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Product Not Found']
                    ]);
                }

                if($different_price){
                    $productPrice = ProductSpecialPrice::where(['id_product' => $valueData['id_product'], 'id_outlet' => $data['id_outlet']])->first();
                    if($productPrice){
                        $productPrice['product_price'] = $productPrice['product_special_price'];
                    }
                }else{
                    $productPrice = ProductGlobalPrice::where(['id_product' => $valueData['id_product']])->first();
                    if($productPrice){
                        $productPrice['product_price'] = $productPrice['product_global_price'];
                    }
                }
                if (!isset($productPrice)) {
                    DB::rollback();
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Price Product Not Found'],
                        'product' => $product['product_name']
                    ]);
                }

                if($productPrice['product_price'] == null){
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Price Product Not Valid'],
                        'product' => $product['product_name']
                    ]);
                }

                if ($outlet_tax) {
                    $productPrice['product_tax'] = round($outlet_tax * $productPrice['product_price'] / (100 + $outlet_tax));
                    // $productPrice['product_price'] = $productPrice['product_price'] - $productPrice['product_tax'];
                }

                $mod_subtotal = 0;
                if ($valueData['id_product_variant_group'] ?? false) {
                    $product_variant_group = ProductVariantGroup::where('product_variant_groups.id_product_variant_group', $valueData['id_product_variant_group']);
                    if ($different_price) {
                        $product_variant_group->join('product_variant_group_special_prices', function($join) use ($data) {
                            $join->on('product_variant_group_special_prices.id_product_variant_group', '=', 'product_variant_groups.id_product_variant_group')
                                ->where('id_outlet', $data['id_outlet']);
                        })->select('product_variant_groups.id_product_variant_group', 'product_variant_groups.id_product', 'product_variant_group_special_prices.product_variant_group_price');
                    } else {
                        $product_variant_group->select('product_variant_groups.id_product_variant_group', 'product_variant_groups.id_product', 'product_variant_groups.product_variant_group_price');
                    }
                    $product_variant_group = $product_variant_group->first();
                    if (!$product_variant_group) {
                        return response()->json([
                            'status' => 'fail',
                            'messages' => ['Product Variant Group not found'],
                            'product' => $product['product_name']
                        ]);
                    }
                    $variantTree = Product::getVariantTree($valueData['id_product'], $outlet);
                    $variants = Product::getVariantPrice($product_variant_group, $variantTree['variants_tree']??[]);
                    if (!$variants) {
                        $valueData['variants'] = [];
                    } else {
                        $valueData['variants'] = $variants;
                    }
                    $productPrice['product_price'] = $variantTree['base_price'] ?? $productPrice['product_price'];
                    $valueData['transaction_variant_subtotal'] = $product_variant_group->product_variant_group_price - $productPrice['product_price'];
                } else {
                    $valueData['variants'] = [];
                    $valueData['transaction_variant_subtotal'] = 0;
                }
                $valueData['transaction_product_price'] = $productPrice['product_price'];
                foreach ($valueData['modifiers']??[] as $modifier) {
                    $id_product_modifier = is_numeric($modifier)?$modifier:$modifier['id_product_modifier'];
                    $qty_product_modifier = is_numeric($modifier)?1:$modifier['qty'];
                    if($different_price){
                        $mod_price = ProductModifierPrice::select('product_modifier_price')->where('id_outlet',$data['id_outlet'])->where('id_product_modifier',$id_product_modifier)->pluck('product_modifier_price')->first()?:0;
                    }else{
                        $mod_price = ProductModifierGlobalPrice::select('product_modifier_price')->where('id_product_modifier',$id_product_modifier)->pluck('product_modifier_price')->first()?:0;
                    }
                    $mod_subtotal += $mod_price*$qty_product_modifier;
                }
                // $price = $productPrice['product_price_base'] * $valueData['qty'];
                // remove discount from substotal
                // $price = (($productPrice['product_price']+$mod_subtotal) * $valueData['qty'])-$this_discount;
                $price = (($productPrice['product_price'] + $mod_subtotal + $valueData['transaction_variant_subtotal']) * $valueData['qty']);
                $valueData['transaction_product_subtotal'] = $price;
                $valueData['transaction_product_tax_subtotal'] = ($productPrice['product_tax'] ?? 0) * $valueData['qty'];
                $valueData['product_tax'] = ($productPrice['product_tax'] ?? 0);
                array_push($dataSubtotal, $price);
                array_push($dataSubtotalFinal, $price);

                if (isset($dataSubtotalPerBrand[$valueData['id_brand']])) {
                    $dataSubtotalPerBrand[$valueData['id_brand']] += $price;
                }else{
                    $dataSubtotalPerBrand[$valueData['id_brand']] = $price;
                }
            }

            if(isset($data['item_service']) && !empty($data['item_service'])){
                foreach ($data['item_service'] as $keyService => &$valueService) {
                    $service = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                        ->leftJoin('brand_product', 'brand_product.id_product', 'products.id_product')
                        ->where('products.id_product', $valueService['id_product'])
                        ->select('products.*', 'product_global_price as product_price', 'brand_product.id_brand')
                        ->first();

                    if (empty($service)) {
                        return response()->json([
                            'status' => 'fail',
                            'messages' => ['Product Service not found'],
                            'product' => $valueService['product_name']
                        ]);
                    }

                    if($different_price){
                        $service['product_price'] = ProductSpecialPrice::where('id_product', $valueService['id_product'])
                                ->where('id_outlet', $outlet['id_outlet'])->first()['product_special_price']??0;
                    }

                    if ($outlet_tax) {
                        $service['product_tax'] = round($outlet_tax * $service['product_price'] / (100 + $outlet_tax));
                        // $service['product_price'] = $service['product_price'] - $service['product_tax'];
                    }

                    if(empty($service['product_price'])){

                        return response()->json([
                            'status'    => 'fail',
                            'messages'  => ['Price Service Product Not Valid'],
                            'product' => $valueService['product_name']
                        ]);
                    }

                    $servicePrice = (int)$service['product_price'] * ($valueService['qty']??1);
                    $valueService['product_tax'] = ($service['product_tax'] ?? 0) * ($valueService['qty']??1);
                    array_push($dataSubtotal, $servicePrice);
                    array_push($dataSubtotalFinal, $servicePrice);
                }
            }

            if(isset($data['item_academy']) && !empty($data['item_academy'])){
                $academy = Product::leftJoin('product_global_price', 'product_global_price.id_product', 'products.id_product')
                    ->where('products.id_product', $data['item_academy']['id_product'])
                    ->select('products.*',  DB::raw('(CASE
                            WHEN (select outlets.outlet_different_price from outlets  where outlets.id_outlet = ' . $outlet['id_outlet'] . ' ) = 1 
                            THEN (select product_special_price.product_special_price from product_special_price  where product_special_price.id_product = products.id_product AND product_special_price.id_outlet = ' . $outlet['id_outlet'] . ' )
                            ELSE product_global_price.product_global_price
                        END) as product_price'))
                    ->first();

                if (empty($academy)) {
                    return response()->json([
                        'status' => 'fail',
                        'messages' => ['Product academy not found'],
                        'product' => $data['item_academy']['product_name']
                    ]);
                }

                if(empty($academy['product_price'])){
                    return response()->json([
                        'status'    => 'fail',
                        'messages'  => ['Price product academy not valid'],
                        'product' => $data['item_academy']['product_name']
                    ]);
                }

                if ($outlet_tax) {
                    $academy['product_tax'] = round($outlet_tax * $academy['product_price'] / (100 + $outlet_tax));
                    // $academy['product_price'] = $academy['product_price'] - $academy['product_tax'];
                }

                $academyPrice = (int)$academy['product_price'];
                $data['item_academy']['product_tax'] = $academy['product_tax'] ?? 0;
                array_push($dataSubtotal, $academyPrice);
                array_push($dataSubtotalFinal, $academyPrice);
            }

            return [
                'subtotal' => $dataSubtotal,
                'subtotal_final' => $dataSubtotalFinal,
                'subtotal_per_brand' => $dataSubtotalPerBrand
            ];
        }

        if ($value == 'discount') {
            $discountTotal = 0;

            foreach ($discount_promo['item']??$data['item']??[] as $keyData => $valueData) {
                $this_discount=0;
                $this_discount=$valueData['discount']??0;
                $discountTotal += $this_discount;
            }
            return $discountTotal;
        }

        if ($value == 'service') {
            $subtotal = $data['subtotal'];
            $serviceFormula = $this->convertFormula('service');
            $value = $this->serviceValue();

            $count = (eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $serviceFormula) . ';'));
            return $count;

        }

        if ($value == 'shipping') {
            return $shipping;
        }

        if ($value == 'tax') {
            $subtotal = $data['subtotal'];
            $taxFormula = $this->convertFormula('tax');
            $value = $this->taxValue();

            if ($discount_promo['item'] ?? false) {
                $loopable = $discount_promo['item'];
            } elseif(!empty($data['item'])) {
                $loopable = $data['item'];
            }

            $count = array_sum(array_column($loopable ?? [], 'transaction_product_tax_subtotal')) + array_sum(array_column($data['item_service'] ?? [], 'product_tax')) + ($data['item_academy']['product_tax'] ?? 0);
            return $count;

            //tax dari product price tax
            // $productTax = 0;
            // foreach ($data['item'] as $keyProduct => $valueProduct) {
            //     $checkProduct = Product::where('id_product', $valueProduct['id_product'])->first();
            //     if (empty($checkProduct)) {
            //         DB::rollback();
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Product Not Found'],
            //             'product' => $checkProduct['product_name']
            //         ]);
            //     }

            //     $checkPriceProduct = ProductPrice::where(['id_product' => $checkProduct['id_product'], 'id_outlet' => $data['id_outlet']])->first();
            //     if (empty($checkPriceProduct)) {
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Price Product Not Valid'],
            //             'product' => $checkProduct['product_name']
            //         ]);
            //     }

            //     if($checkPriceProduct['product_price'] == null || $checkPriceProduct['product_price_base'] == null || $checkPriceProduct['product_price_tax'] == null){
            //         return response()->json([
            //             'status'    => 'fail',
            //             'messages'  => ['Price Product Not Valid'],
            //             'product' => $checkProduct['product_name']
            //         ]);
            //     }

            //     $productTax += $checkPriceProduct['product_price_tax'] * $valueProduct['qty'];
            // }

            // return $productTax;

        }

        if ($value == 'point') {
            $subtotal = $data['subtotal'];
            $pointFormula = $this->convertFormula('point');
            $value = $this->pointValue();

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $pointFormula) . ';'));
            return $count;

        }

        if ($value == 'cashback') {
            $subtotal = $data['subtotal'];
            $cashbackFormula = $this->convertFormula('cashback');
            $value = $this->cashbackValue();
            $max = $this->cashbackValueMax();
            $settingIncludeBundling = MyHelper::setting('cashback_include_bundling');
            /**
             * This setting is used to calculate whether the cashback earned is also based on the bundled product
             * By default, subtotals include bundled products
             * So to calculate without bundling products, the formula used must be ( service + subtotal - totalBundling ) * value
             */
            if ($settingIncludeBundling) {
                /**
                 * When the setting allows bundling in the calculation, then the subtotal is not subtracted by the bundling product subtotal,
                 * or it can also be written a subtotal - 0
                 */
                $totalBundling = 0;
            }

            $count = floor(eval('return ' . preg_replace('/([a-zA-Z0-9]+)/', '\$$1', $cashbackFormula) . ';'));

            return $count;
        }
    }

    public function discountProduct($data)
    {
        $discountTotal = 0;
        $discount = 0;
        $totalAllDiscount = 0;
        $countSemen = 0;
        $discountFormula = $this->convertFormula('discount');
        // return $discountFormula;
        foreach ($data['item'] as $keyData => $valueData) {
            $product = Product::with('product_discounts')->where('id_product', $valueData['id_product'])->first();
            if (empty($product)) {
                DB::rollback();
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Product Not Found']
                ]);
            }

            $outlet = Outlet::where('id_outlet',$data['id_outlet'])->first();
            $different_price = $outlet->outlet_different_price ?? 0;
            if($different_price){
                $productPrice = ProductSpecialPrice::where(['id_product' => $valueData['id_product'], 'id_outlet' => $data['id_outlet']])->first();
                if($productPrice){
                    $productPrice['product_price'] = $productPrice['product_special_price'];
                }
            }else{
                $productPrice = ProductGlobalPrice::where(['id_product' => $valueData['id_product']])->first();
                if($productPrice){
                    $productPrice['product_price'] = $productPrice['product_global_price'];
                }
            }

            if ($outlet['is_tax']) {
                $productPrice['product_tax'] = round($outlet['is_tax'] * $productPrice['product_price'] / (100 + $outlet['is_tax']));
                // $productPrice['product_price'] = $productPrice['product_price'] - $productPrice['product_tax'];
            }

            if (empty($productPrice)) {
                continue;
                DB::rollback();
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Product Price Not Found']
                ]);
            }

            if (count($product['product_discounts']) > 0) {
                foreach ($product['product_discounts'] as $keyDiscount => $valueDiscount) {
                    if (!empty($valueDiscount['discount_percentage'])) {
                        $jat = $valueDiscount['discount_percentage'];

                        $count = $priceProduct['product_price'] * $jat / 100;
                    } else {
                        $count = $valueDiscount['discount_nominal'];
                    }

                    $now = date('Y-m-d');
                    $time = date('H:i:s');
                    $day = date('l');

                    if ($now < $valueDiscount['discount_start']) {
                        $count = 0;
                    }

                    if ($now > $valueDiscount['discount_end']) {
                        $count = 0;
                    }

                    if ($time < $valueDiscount['discount_time_start']) {
                        $count = 0;
                    }

                    if ($time > $valueDiscount['discount_time_end']) {
                        $count = 0;
                    }

                    if (strpos($valueDiscount['discount_days'], $day) === false) {
                        $count = 0;
                    }

                    $discountTotal = $valueData['qty'] * $count;
                    $countSemen += $discountTotal;
                    $discountTotal = 0;
                }
            }
        }

        return $countSemen;
    }

    public function countDis($data)
    {
        $discountTotal = 0;
        $discount = 0;
        $totalAllDiscount = 0;
        $countSemen = 0;
        $discountFormula = $this->convertFormula('discount');
        // return $discountFormula;
        foreach ($data['item'] as $keyData => $valueData) {
            $product = Product::with('product_discounts')->where('id_product', $valueData['id_product'])->first();
            if (empty($product)) {
                DB::rollback();
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Product Not Found']
                ]);
            }

            // $priceProduct = ProductPrice::where('id_product', $valueData['id_product'])->where('id_outlet', $data['id_outlet'])->first();
            $different_price = Outlet::select('outlet_different_price')->where('id_outlet',$data['id_outlet'])->pluck('outlet_different_price')->first();
            if($different_price){
                $productPrice = ProductSpecialPrice::where(['id_product' => $valueData['id_product'], 'id_outlet' => $data['id_outlet']])->first();
                if($productPrice){
                    $productPrice['product_price'] = $productPrice['product_special_price'];
                }
            }else{
                $productPrice = ProductGlobalPrice::where(['id_product' => $valueData['id_product']])->first();
                if($productPrice){
                    $productPrice['product_price'] = $productPrice['product_global_price'];
                }
            }
            if (empty($productPrice)) {
                DB::rollback();
                return response()->json([
                    'status' => 'fail',
                    'messages' => ['Product Price Not Found']
                ]);
            }

            if (count($product['product_discounts']) > 0) {
                foreach ($product['product_discounts'] as $keyDiscount => $valueDiscount) {
                    if (!empty($valueDiscount['discount_percentage'])) {
                        $jat = $valueDiscount['discount_percentage'];

                        $count = $priceProduct['product_price'] * $jat / 100;
                    } else {
                        $count = $valueDiscount['discount_nominal'];
                    }

                    $now = date('Y-m-d');
                    $time = date('H:i:s');
                    $day = date('l');

                    if ($now < $valueDiscount['discount_start']) {
                        $count = 0;
                    }

                    if ($now > $valueDiscount['discount_end']) {
                        $count = 0;
                    }

                    if ($time < $valueDiscount['discount_time_start']) {
                        $count = 0;
                    }

                    if ($time > $valueDiscount['discount_time_end']) {
                        $count = 0;
                    }

                    if (strpos($valueDiscount['discount_days'], $day) === false) {
                        $count = 0;
                    }

                    $discountTotal = $valueData['qty'] * $count;
                    $countSemen += $discountTotal;
                    $discountTotal = 0;
                }
            }
        }

        return $countSemen;
    }

    public function getrandomstring($length = 120) {

       global $template;
       settype($template, "string");

       $template = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring;
    }

    public function getrandomnumber($length) {

       global $template;
       settype($template, "string");

       $template = "0987654321";

       settype($length, "integer");
       settype($rndstring, "string");
       settype($a, "integer");
       settype($b, "integer");

       for ($a = 0; $a <= $length; $a++) {
               $b = rand(0, strlen($template) - 1);
               $rndstring .= $template[$b];
       }

       return $rndstring;
    }
}
