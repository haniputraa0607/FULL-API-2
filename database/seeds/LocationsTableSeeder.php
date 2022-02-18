<?php

use Illuminate\Database\Seeder;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Http\Models\Outlet;

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
                    $item['Phone'] = str_replace(['(',')','+',' '], '', $item['Phone']);
                    $location = Location::updateOrCreate([
                        $company == 'ima' ? 'id_branch_ima' : 'id_branch' => $item['BranchID']
                    ],[
                        'name' => $item['Name'], 
                        'code' => $item['Code'],
                        'address' => $item['Address'], 
                        'id_city' => 3173, 
                        'pic_contact' => $item['Phone'],
                        'id_partner' => Partner::where($company == 'ima' ? 'id_business_partner_ima' : 'id_business_partner', $item['BusinessPartnerID'])->select('id_partner')->pluck('id_partner')->first(),
                        'status' => 'Active',
                        'step_loc' => 'Approved',
                    ]);

                    $outlet = Outlet::updateOrCreate(['outlet_code' => $item['Code']],[
                        'id_branch' => $item['BranchID'],
                        'branch_code' => $item['Code'],
                        'id_location' => $location->id_location,
                        'is_tax' => 0,
                        'outlet_code' => $item['Code'],
                        'outlet_name' => $item['Name'],
                        'outlet_description' => '',
                        'outlet_address' => $item['Address'],
                        'id_city' => 3173,
                        'outlet_phone' => $item['Phone'],
                        'outlet_email' => $item['Email'],
                    ]);

                    $boxes = $outlet->outlet_box;
                    if ($boxes->count() < 5) {
                        for ($i=0; $i<(5 - $boxes->count()); $i++) {
                            $outlet->outlet_box()->create([
                                'outlet_box_code' => $boxes->count() + 1 + $i,
                                'outlet_box_name' => 'BOX ' . ($boxes->count() + 1 + $i),
                                'outlet_box_url' => null,
                                'outlet_box_status' => 'Active',
                                'outlet_box_use_status' => 0
                            ]);
                        }
                    }
                }
            }
        }
    }
}
