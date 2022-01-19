<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeStatusDeliveryProductDetailsTable extends Migration
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
        DB::statement('ALTER TABLE `delivery_product_details` CHANGE `status` `status` ENUM("Less","Enough","More") NULL;');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE `delivery_product_details` CHANGE `status` `status` ENUM("Pending","Approved","Rejected") default "Pending" NOT NULL;');
    }
}
