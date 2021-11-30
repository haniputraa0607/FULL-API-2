<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnExportFranchiseQueue extends Migration
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
        DB::statement('ALTER TABLE `export_franchise_queues` CHANGE `report_type` `report_type` ENUM("Payment","Transaction","Subscription","Deals","Report Transaction Product","Report Transaction Modifier","Report Transaction Service") NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `export_franchise_queues` CHANGE `report_type` `report_type` ENUM("Payment","Transaction","Subscription","Deals","Report Transaction Product","Report Transaction Modifier","") NULL;');
    }
}
