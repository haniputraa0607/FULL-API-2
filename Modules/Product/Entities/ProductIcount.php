<?php

namespace Modules\Product\Entities;

use CreateProductIcountOutletStockLogsTable;
use Illuminate\Database\Eloquent\Model;

class ProductIcount extends Model
{
    protected $table = 'product_icounts';
	protected $primaryKey = "id_product_icount";

	protected $fillable = [
        'id_item',
        'id_company',
        'code',
        'name',
        'id_brand',
        'id_category',
        'id_sub_category',
        'item_group',
        'image_item',
        'unit1',
        'unit2',
        'unit3',
        'ratio2',
        'ratio3',
        'buy_price_1',
        'buy_price_2',
        'buy_price_3',
        'unit_price_1',
        'unit_price_2',
        'unit_price_3',
        'unit_price_4',
        'unit_price_5',
        'unit_price_6',
        'notes',
        'is_suspended',
        'is_sellable',
        'is_buyable',
        'id_cogs',
        'id_purchase',
        'id_sales',
        'id_deleted',
	];

    public function addLogStockProductIcount($qty, $unit, $source, $id_refrence = null, $desctiption = null, $id_outlet = null){

        $id_product_icount = $this->id_product_icount;
        $id_outlet =  (empty($id_outlet) ? auth()->user()->id_outlet : $id_outlet);
        $current_stock = ProductIcountOutletStock::where('id_outlet',$id_outlet)
            ->where('id_product_icount',$id_product_icount)
            ->where('unit',$unit)->first();

        if($qty < 0){
            if(!$current_stock || ($current_stock['stock']+$qty)<0){
                return false;
            }
        }

        $stock = $current_stock['stock'] ?? 0;
        $create_log = [
            "id_outlet" => $id_outlet,
            "id_product_icount" => $id_product_icount,
            "unit" => $unit,
            "qty" => $qty,
            "stock_before" => $stock,
            "stock_after" => $stock + $qty,
            "id_reference" => $id_refrence,
            "source" => $source,
            "desctiption" => $desctiption
        ];
        $store_log = ProductIcountOutletStockLog::create($create_log);

        if($store_log){
            $new_outlet_stock = ProductIcountOutletStock::updateOrCreate(
                ["id_product_icount" => $id_product_icount,"id_outlet" => $id_outlet,"unit" => $unit],
                ["stock" => $store_log['stock_after']]
            );

            if($new_outlet_stock){
                $this->refreshStock($id_outlet, $unit);
            }
        }
    }

    public function refreshStock($id_outlet, $unit = null)
    {
        $id_product_icount = $this->id_product_icount;
        if (!$unit) {
            $unit = $this->unit1;
        }
        $product_uses = ProductProductIcount::where('id_product_icount', $id_product_icount)->where('unit', $unit)->get()->toArray();

        if($product_uses){
            foreach($product_uses as $key => $product_use){
                $get_product_uses = ProductProductIcount::where('id_product',$product_use['id_product'])->get()->toArray();
                if($get_product_uses){
                    $cek_use = true;
                    $value = 0;
                    $another_value = 0;
                    $service = true;
                    foreach($get_product_uses as $key_use => $get_product_use){
                        if($get_product_use['id_product_icount'] == $id_product_icount){
                            $cek_service = ProductIcount::where('id_product_icount',$get_product_use['id_product_icount'])->first();
                            if($cek_service['item_group'] == 'Service'){
                                if($service){
                                    $service = true;
                                }
                            }else{
                                $value = $new_outlet_stock['stock']/$get_product_use['qty'];
                                $value = floor($value);
                                if($service){
                                    $value = $value;
                                }else{
                                    if($value==0){
                                        $cek_use = false;
                                    }
                                    if($another_value != 0){
                                        if($value < $another_value){
                                            $value = $value;
                                        }else{
                                            $value = $another_value;
                                        }
                                    }
                                }
                                $service = false;
                            }
                        }else{
                            $cek_service = ProductIcount::where('id_product_icount',$get_product_use['id_product_icount'])->first();
                            if($cek_service['item_group'] == 'Service'){
                                if($service){
                                    $service = true;
                                }
                            }else{
                                $cek_another_use = ProductIcountOutletStock::where('id_product_icount',$get_product_use['id_product_icount'])->where('unit',$get_product_use['unit'])->first();
                                if($cek_another_use){
                                    $another_value = $cek_another_use['stock']/$get_product_use['qty'];
                                    $another_value = floor($another_value);
                                    if($service){
                                        $value = $another_value;
                                    }else{
                                        if($another_value==0){
                                            $cek_use = false;
                                        }
                                        if($value != 0){
                                            if($another_value < $value){
                                                $value = $another_value;
                                            }else{
                                                $value = $value;
                                            }
                                        }
                                    }

                                }else{
                                    $cek_use = false;
                                }
                                $service = false;
                            }
                        }
                    }

                    if(!$cek_use && !$service){
                        $value = 0;
                    }

                    if($value==0 && !$service){
                        $stock_status = 'Sold Out';
                    }elseif(!$service){
                        $stock_status = 'Available';
                    }

                    if($service){
                        $value = null;
                        $stock_status = 'Available';
                    }
                }

                $product_detail = ProductDetail::updateOrCreate(
                    ["id_product" => $product_use['id_product'],"id_outlet" => $id_outlet],
                    [
                        "product_detail_visibility" => 'Visible',
                        "product_detail_status" => 'Active',
                        "product_detail_stock_status" => $stock_status,
                        "product_detail_stock_item" => $value,
                        "product_detail_stock_service" => 0,
                        "max_order" => null,
                    ]
                );
            }
        }
    }
}
