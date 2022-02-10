<?php

namespace App\Jobs;

use App\Http\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\BusinessDevelopment\Entities\StepsLog;
use App\Lib\MyHelper;
use Modules\Product\Entities\ProductIcount;
use App\Lib\Icount;
use Storage;
use DB;


class SyncIcountItems implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $data,$camp;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data=$data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $icount = new Icount();
        $id_items = $this->data['id_items'];
        $data = $icount->ItemList($this->data['page']);
        if(isset($data)){
            if($data['response']['Message']=='Success'){
                $items = $data['response']['Data'];
                $items = $this->checkInputIcount($items);
                if($data['response']['Meta']['Pagination']['CurrentPage']==1){
                    $index = 0;
                }else{
                    $index = count($id_items);
                }
                foreach($items as $item){
                    $id_items[$index] = $item['id_item'];
                    $check_item = ProductIcount::where('id_item','=',$item['id_item'])->first();
                    if($check_item){
                        $update = ProductIcount::where('id_item','=',$item['id_item'])->update($item);
                        if(!$update){
                            return ['status' => 'fail', 'messages' => ['Failed to sync with ICount']];    
                        }
                    }else{
                        $store = ProductIcount::create($item);
                        if(!$store){
                            return ['status' => 'fail', 'messages' => ['Failed to sync with ICount']];    
                        }
                    }
                    $index++;
                }

                if($data['response']['Meta']['Pagination']['CurrentPage']<$data['response']['Meta']['Pagination']['LastPage']){
                    $new_page = $data['response']['Meta']['Pagination']['CurrentPage'] + 1;
                    SyncIcountItems::dispatch(['page'=> $new_page,'id_items' => $id_items]);
                    Setting::where('key','Sync Product Icount')->update(['value' => 'process']);
                }else{
                    ProductIcount::whereIn('id_item',$id_items)->update(['is_actived' => 'true']);
                    ProductIcount::whereNotIn('id_item',$id_items)->update(['is_actived' => 'false']);
                    Setting::where('key','Sync Product Icount')->update(['value' => 'finished']);
                }
            }
        }
    }

    public function checkInputIcount($array){
        if($array){
            $data = [];
            foreach($array as $key => $item){
                if (isset($item['ItemID'])) {
                    $data[$key]['id_item'] = $item['ItemID'];
                }
                if (isset($item['CompanyID'])) {
                    $data[$key]['id_company'] = $item['CompanyID'];
                }
                if (isset($item['Code']) ) {
                    $data[$key]['code'] = $item['Code'];
                }
                if (isset($item['Name']) && !empty($item['Name'])) {
                    $data[$key]['name'] = $item['Name'];
                }
                if (isset($item['BrandID']) && !empty($item['BrandID'])) {
                    $data[$key]['id_brand'] = $item['BrandID'];
                }else{
                    $data[$key]['id_brand'] = null;
                }  
                if (isset($item['CategoryID']) && !empty($item['CategoryID'])) {
                    $data[$key]['id_category'] = $item['CategoryID'];
                }else{
                    $data[$key]['id_category'] = null;
                }  
                if (isset($item['SubCategoryID']) && !empty($item['SubCategoryID'])) {
                    $data[$key]['id_sub_category'] = $item['SubCategoryID'];
                }else{
                    $data[$key]['id_sub_category'] = null;
                }  
                if (isset($item['GroupItem'])) {
                    $data[$key]['item_group'] = $item['GroupItem'];
                }
                if (isset($item['ItemImage']) && !empty($item['ItemImage'])) {
                    $decoded = base64_decode($item['ItemImage']);
                    $name = str_replace(' ','_',$item['Name']);
                    $name_im = $item['Code'].'_'.$name.'.png';
                    $upload = $this->saveImageIcount.$name_im;
                    if(Storage::disk(env('STORAGE'))->exists($upload)) {
                        (Storage::disk(env('STORAGE'))->delete($upload));
                    }
                    $save = Storage::disk(env('STORAGE'))->put($upload, $decoded, 'public');
                    if ($save) {
                        $data[$key]['image_item'] = $upload;
                    }
                    else {
                        $data[$key]['image_item'] = null;

                    }
                }else{
                    $data[$key]['image_item'] = null;
                }  
                if (isset($item['Unit1']) && !empty($item['Unit1'])) {
                    $data[$key]['unit1'] = $item['Unit1'];
                }else{
                    $data[$key]['unit1'] = null;
                }  
                if (isset($item['Unit2']) && !empty($item['Unit2'])) {
                    $data[$key]['unit2'] = $item['Unit2'];
                }else{
                    $data[$key]['unit2'] = null;
                }  
                if (isset($item['Unit3']) && !empty($item['Unit3'])) {
                    $data[$key]['unit3'] = $item['Unit3'];
                }else{
                    $data[$key]['unit3'] = null;
                }  
                if (isset($item['Ratio2'])) {
                    $data[$key]['ratio2'] = $item['Ratio2'];
                }
                if (isset($item['Ratio3'])) {
                    $data[$key]['ratio3'] = $item['Ratio3'];
                }
                if (isset($item['BuyPrice1'])) {
                    $data[$key]['buy_price_1'] = $item['BuyPrice1'];
                }
                if (isset($item['BuyPrice2'])) {
                    $data[$key]['buy_price_2'] = $item['BuyPrice2'];
                }
                if (isset($item['BuyPrice3'])) {
                    $data[$key]['buy_price_3'] = $item['BuyPrice3'];
                }
                if (isset($item['UnitPrice1'])) {
                    $data[$key]['unit_price_1'] = $item['UnitPrice1'];
                }
                if (isset($item['UnitPrice2'])) {
                    $data[$key]['unit_price_2'] = $item['UnitPrice2'];
                }
                if (isset($item['UnitPrice3'])) {
                    $data[$key]['unit_price_3'] = $item['UnitPrice3'];
                }
                if (isset($item['UnitPrice4'])) {
                    $data[$key]['unit_price_4'] = $item['UnitPrice4'];
                }
                if (isset($item['UnitPrice5'])) {
                    $data[$key]['unit_price_5'] = $item['UnitPrice5'];
                }
                if (isset($item['UnitPrice6'])) {
                    $data[$key]['unit_price_6'] = $item['UnitPrice6'];
                }
                if (isset($item['Notes']) && !empty($item['Notes'])) {
                    $data[$key]['notes'] = $item['Notes'];
                }else{
                    $data[$key]['notes'] = null;
                }  
                if (isset($item['IsSuspended'])) {
                    if($item['IsSuspended']==true){
                        $data[$key]['is_suspended'] = "true";
                    }else{
                        $data[$key]['is_suspended'] = "false";
                    }
                }else{
                    $data[$key]['is_suspended'] = null;
                }  
                if (isset($item['IsSellable'])) {
                    if($item['IsSellable']==true){
                        $data[$key]['is_sellable'] = "true";
                    }else{
                        $data[$key]['is_sellable'] = "false";
                    }
                }else{
                    $data[$key]['is_sellable'] = null;
                }  
                if (isset($item['IsBuyable'])) {
                    if($item['IsBuyable']==true){
                        $data[$key]['is_buyable'] = "true";
                    }else{
                        $data[$key]['is_buyable'] = "false";
                    }
                }else{
                    $data[$key]['is_buyable'] = null;
                }  
                if (isset($item['COGSID']) && !empty($item['COGSID'])) {
                    $data[$key]['id_cogs'] = $item['COGSID'];
                }else{
                    $data[$key]['id_cogs'] = null;
                }  
                if (isset($item['PurchaseID']) && !empty($item['PurchaseID'])) {
                    $data[$key]['id_purchase'] = $item['PurchaseID'];
                }else{
                    $data[$key]['id_purchase'] = null;
                }  
                if (isset($item['SalesID']) && !empty($item['SalesID'])) {
                    $data[$key]['id_sales'] = $item['SalesID'];
                }else{
                    $data[$key]['id_sales'] = null;
                }  
                if (isset($item['IsDeleted'])) {
                    if($item['IsDeleted']==true){
                        $data[$key]['is_deleted'] = "true";
                    }else{
                        $data[$key]['is_deleted'] = "false";
                    }
                }else{
                    $data[$key]['is_deleted'] = null;
                }  
            }
            return $data;
        }
    }
}
