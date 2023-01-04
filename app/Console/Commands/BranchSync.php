<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\BusinessDevelopment\Entities\Location;
use Modules\BusinessDevelopment\Entities\Partner;
use App\Http\Models\Outlet;

class BranchSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'branch:sync {--branch-id=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import Branch to DB';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $companies = ['ima', 'ims'];
        $resource = 'branch';
        $found = 0;

        foreach ($companies as $company) {
            $this->comment("Looking at $company...");
            $basePath = base_path("database/seeds/fetch/$company/$resource/");
            $files = array_diff(scandir($basePath), array('.', '..'));
            foreach ($files as $file) {
                $json = json_decode(file_get_contents($basePath . $file), true);

                $items = $json['Data'];
                foreach ($items as $item) {
                    $location = Location::where('code', $item['Code'])->first();
                    if ($location) {
                        $this->updateLocation($company, $location, $item);
                    } else {
                        $this->createLocation($company, $item);
                    }
                }
            }
        }
    }

    public function updateLocation($company, $location, $item)
    {
        $column = 'id_branch' . ($company == 'ima' ? '_ima' : '');
        if ($location->$column != $item['BranchID']) {
            $continue = $this->confirm("Update branch id $location[name] in $company to $item[BranchID]? Previously {$location->$column}");
            if ($continue) {
                $location->update([$column => $item['BranchID']]);
                Outlet::where('id_location', $location->id)->update(['id_branch' => $item['BranchID']]);
            }
        }
    }

    function createLocation($company, $item) {
        $this->synchLocation($company, $item["BranchID"]);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function synchLocation($company, ...$branchIds)
    {
        if (!$branchIds) {
            $branchIds = $this->option('branch-id');
        }
        // $companies = ['ima', 'ims'];
        $companies = [$company];
        $resource = 'branch';
        $found = 0;

        foreach ($companies as $company) {
            $this->comment("Looking at $company...");
            $basePath = base_path("database/seeds/fetch/$company/$resource/");
            $files = array_diff(scandir($basePath), array('.', '..'));
            foreach ($files as $file) {
                $json = json_decode(file_get_contents($basePath . $file), true);

                $items = $json['Data'];
                foreach ($items as $item) {
                    if (!in_array($item['BranchID'], $branchIds)) {
                        continue;
                    }
                    $found = 1;
                    $continue = $this->confirm("Found $item[Name] ($item[Code]). Continue?");

                    if (!$continue) {
                        $this->warn("Syncing $item[Name] ($item[Code]) Skipped");
                        continue;
                    }

                    if (!$this->syncPartner($company, $item['OutletPartnerID'])) {
                        $this->error("Partner $item[OutletPartnerID] not found");
                        continue;
                    }

                    $ada = Location::where([
                        $company == 'ima' ? 'id_branch_ima' : 'id_branch' => $item['BranchID']
                    ])->exists();

                    if ($ada) {
                        $this->error("Outlet sudah ada di $company");
                        continue;
                    }


                    $item['Phone'] = str_replace(['(',')','+',' '], '', $item['Phone']);
                    $location = Location::updateOrCreate([
                        $company == 'ima' ? 'id_branch_ima' : 'id_branch' => $item['BranchID']
                    ],[
                        'name' => $item['Name'], 
                        'code' => $item['Code'],
                        'address' => $item['Address'], 
                        'id_city' => 3173, 
                        'pic_contact' => $item['Phone'],
                        'id_partner' => Partner::where($company == 'ima' ? 'id_business_partner_ima' : 'id_business_partner', $item['OutletPartnerID'])->select('id_partner')->pluck('id_partner')->first(),
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
                    $totalBox = $this->ask('Jumlah box? (5)') ?: 5;
                    if ($boxes->count() < $totalBox) {
                        for ($i=0; $i<($totalBox - $boxes->count()); $i++) {
                            $outlet->outlet_box()->create([
                                'outlet_box_code' => $boxes->count() + 1 + $i,
                                'outlet_box_name' => 'BOX ' . ($boxes->count() + 1 + $i),
                                'outlet_box_url' => null,
                                'outlet_box_status' => 'Active',
                                'outlet_box_use_status' => 0
                            ]);
                        }
                    }
                    $this->info("Syncing $item[Name] ($item[Code]) Completed");
                }
            }
        }

        if (!$found) {
            $this->error("Outlet not found");
        }
    }

    public function syncPartner($company, $id)
    {
        $resource = 'business_partner';
        $found = false;

        $basePath = base_path("database/seeds/fetch/$company/$resource/");
        $files = array_diff(scandir($basePath), array('.', '..'));
        foreach ($files as $file) {
            $json = json_decode(file_get_contents($basePath . $file), true);
            $items = $json['Data'];
            foreach ($items as $item) {
                if ($item['BusinessPartnerID'] != $id) {
                    continue;
                }
                $this->comment("Syncing $item[Name]...");
                $found = true;
                $partner = Partner::updateOrCreate([
                    $company == 'ima' ? 'id_business_partner_ima' : 'id_business_partner' => $item['BusinessPartnerID']
                ],[
                    'id_business_partner' => $company == 'ims' ? $item['BusinessPartnerID'] : null,
                    'id_business_partner_ima' => $company == 'ima' ? $item['BusinessPartnerID'] : null,
                    'id_company' => $item['CompanyID'],
                    'id_cluster' => $item['ClusterID'],
                    'code' => $item['Code'],
                    'name' => $item['Name'],
                    'title' => $item['Title'],
                    'contact_person' => $item['ContactPerson'],
                    'gender' => 'Man',
                    'group' => $item['GroupBusinessPartner'],
                    'phone' => $item['PhoneNo'],
                    'mobile' => $item['MobileNo'],
                    'email' => $item['Email'],
                    'address' => $item['Address'],
                    'ownership_status' => null,
                    'cooperation_scheme' => null,
                    'id_bank_account' => null,
                    'npwp' => $item['NPWP'],
                    'npwp_name' => $item['NPWPName'],
                    'npwp_address' => $item['NPWPAddress'],
                    'status_steps' => 'Payment',
                    'status' => 'Active',
                    'is_suspended' => $item['IsSuspended'] ? 1 : 0,
                    'is_tax' => $item['IsTax'] ? 1 : 0,
                    'price_level' => $item['PriceLevel'],
                    'start_date' => $item['JoinDate'] ? date('Y-m-d H:i:s', strtotime($item['JoinDate'])) : null,
                    'end_date' => null,
                    'id_term_payment' => $item['TermOfPaymentID'],
                    'id_account_payable' => $item['AccountPayableID'],
                    'id_account_receivable' => $item['PurchaseDepositID'],
                    'id_sales_disc' => $item['AccountReceivableID'],
                    'id_purchase_disc' => $item['SalesDepositID'],
                    'id_tax_in' => $item['TaxInID'],
                    'id_tax_out' => $item['TaxOutID'],
                    'id_salesman' => $item['SalesmanID'],
                    'is_deleted' => $item['IsDeleted'],
                    'password' => \Hash::make('170845'),
                    'first_update_password' => 0,
                ]);
            }
        }
        return $found;
    }
}
