<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePartnersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `start_date` `start_date` date NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `end_date` `end_date` date NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `ownership_status` `ownership_status` ENUM("Central","Partner") NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `cooperation_scheme` `cooperation_scheme` ENUM("Profit Sharing","Management Fee") NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `id_bank_account` `id_bank_account` bigint unsigned NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `partners` CHANGE `start_date` `start_date` date NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `end_date` `end_date` date NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `ownership_status` `ownership_status` ENUM("Central","Partner") NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `cooperation_scheme` `cooperation_scheme` ENUM("Profit Sharing","Management Fee") NULL;');
        DB::statement('ALTER TABLE `partners` CHANGE `id_bank_account` `id_bank_account` bigint unsigned NULL;');
    }
}
