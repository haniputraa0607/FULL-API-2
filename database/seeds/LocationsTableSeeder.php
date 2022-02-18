<?php

use Illuminate\Database\Seeder;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;

class LocationsTableSeeder extends Seeder
{
    public function run()
    {
        $companies = ['ima', 'ims'];
        $resource = 'branch';

        foreach ($companies as $company) {
            $basePath = base_path("database/seeds/fetch/$company/$resource/");
            $files = array_diff(scandir($basePath), array('.', '..'));
            foreach ($files as $file) {
                $json = json_decode(file_get_contents($basePath . $file), true);
                $items = $json['Data'];
                foreach ($items as $item) {
                    $partner = Location::updateOrCreate([
                        $company == 'ima' ? 'id_branch_ima' : 'id_branch' => $item['BranchID']
                    ],[
                        'name' => $item['Name'], 
                        'code' => $item['Code'],
                        'address' => $item['Address'], 
                        'id_city' => 1, 
                        'pic_contact' => $item['Phone'],
                        'id_partner' => Partner::where($company == 'ima' ? 'id_business_partner_ima' : 'id_business_partner_ima', $item['BusinessPartnerID'])->select('id_partner')->pluck('id_partner')->first(),
                        'status' => 'Active',
                        'step_loc' => 'Approved',
                    ]);
                }
            }
        }
    }
}
