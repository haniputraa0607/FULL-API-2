<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeProfitSharingToRevenueSharingToLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        DB::statement('ALTER TABLE `locations` CHANGE `cooperation_scheme` `cooperation_scheme` ENUM("Revenue Sharing","Management Fee") NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `locations` CHANGE `cooperation_scheme` `cooperation_scheme` ENUM("Profit Sharing","Management Fee") NULL;');
    }
}
