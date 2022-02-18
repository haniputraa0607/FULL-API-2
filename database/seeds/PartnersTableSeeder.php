<?php

use Illuminate\Database\Seeder;
use Modules\BusinessDevelopment\Entities\Partner;

class PartnersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = ['ima', 'ims'];
        $resource = 'business_partner';

        foreach ($companies as $company) {
            $basePath = base_path("database/seeds/fetch/$company/$resource/");
            $files = array_diff(scandir($basePath), array('.', '..'));
            foreach ($files as $file) {
                $json = json_decode(file_get_contents($basePath . $file), true);
                $items = $json['Data'];
                foreach ($items as $item) {
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
        }
    }
}
