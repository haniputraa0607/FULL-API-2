<?php

use Illuminate\Database\Seeder;
use Modules\Product\Entities\ProductIcount;

class ProductIcountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = ['ima', 'ims'];
        $resource = 'item';

        foreach ($companies as $company) {
            $basePath = base_path("database/seeds/fetch/$company/$resource/");
            $files = array_diff(scandir($basePath), array('.', '..'));
            foreach ($files as $file) {
                $json = json_decode(file_get_contents($basePath . $file), true);
                $items = $json['Data'];
                foreach ($items as $item) {
                    $location = ProductIcount::updateOrCreate([
                        'id_item' => $item['ItemID'],
                        'company_type' => $company,
                    ],[
                        'id_company' => $item['CompanyID'],
                        'code' => $item['Code'],
                        'name' => $item['Name'],
                        'id_brand' => $item['BrandID'],
                        'id_category' => $item['CategoryID'],
                        'id_sub_category' => $item['SubCategoryID'],
                        'item_group' => $item['GroupItem'],
                        'image_item' => null,
                        'unit1' => $item['Unit1'],
                        'unit2' => $item['Unit2'],
                        'unit3' => $item['Unit3'],
                        'ratio2' => $item['Ratio2'],
                        'ratio3' => $item['Ratio3'],
                        'buy_price_1' => $item['BuyPrice1'],
                        'buy_price_2' => $item['BuyPrice2'],
                        'buy_price_3' => $item['BuyPrice3'],
                        'unit_price_1' => $item['UnitPrice1'],
                        'unit_price_2' => $item['UnitPrice2'],
                        'unit_price_3' => $item['UnitPrice3'],
                        'unit_price_4' => $item['UnitPrice4'],
                        'unit_price_5' => $item['UnitPrice5'],
                        'unit_price_6' => $item['UnitPrice6'],
                        'notes' => $item['Notes'],
                        'is_suspended' => $item['IsSuspended'],
                        'is_sellable' => $item['IsSellable'],
                        'is_buyable' => $item['IsBuyable'],
                        'id_cogs' => $item['COGSID'],
                        'id_purchase' => $item['PurchaseID'],
                        'id_sales' => $item['SalesID'],
                        'is_deleted' => $item['IsDeleted'],
                        'is_actived' => 'true',
                    ]);
                }
            }
        }
    }
}
