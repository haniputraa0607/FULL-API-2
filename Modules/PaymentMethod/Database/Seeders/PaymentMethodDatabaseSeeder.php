<?php

namespace Modules\PaymentMethod\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethodDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

        $this->call(PaymentMethodCategorySeederTableSeeder::class);
        $this->call(PaymentMethodSeederTableSeeder::class);
    }
}
