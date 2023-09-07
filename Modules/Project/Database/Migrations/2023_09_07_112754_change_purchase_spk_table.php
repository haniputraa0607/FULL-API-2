<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangePurchaseSpkTable extends Migration
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
        \DB::statement("ALTER TABLE `purchase_spk` CHANGE COLUMN `id_request_purchase` `id_request_purchase` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `purchase_spk` CHANGE COLUMN `id_business_partner` `id_business_partner` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `purchase_spk` CHANGE COLUMN `id_branch` `id_branch` INT NULL DEFAULT NULL");
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchase_spk', function (Blueprint $table) {
            $table->string('id_request_purchase')->nullable()->change();
            $table->string('id_business_partner')->nullable()->change();
            $table->string('id_branch')->nullable()->change();
        });
    }
}
