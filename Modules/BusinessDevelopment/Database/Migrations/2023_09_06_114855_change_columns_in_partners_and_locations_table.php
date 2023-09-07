<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnsInPartnersAndLocationsTable extends Migration
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
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_business_partner` `id_business_partner` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_business_partner_ima` `id_business_partner_ima` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_company` `id_company` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_sales_order` `id_sales_order` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_sales_order_detail` `id_sales_order_detail` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_sales_invoice` `id_sales_invoice` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_sales_invoice_detail` `id_sales_invoice_detail` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `partners` CHANGE COLUMN `id_delivery_order_detail` `id_delivery_order_detail` INT NULL DEFAULT NULL");
        

        \DB::statement("ALTER TABLE `locations` CHANGE COLUMN `id_branch` `id_branch` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `locations` CHANGE COLUMN `id_branch_ima` `id_branch_ima` INT NULL DEFAULT NULL");


        \DB::statement("ALTER TABLE `init_branchs` CHANGE COLUMN `id_sales_order` `id_sales_order` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `init_branchs` CHANGE COLUMN `id_company` `id_company` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `init_branchs` CHANGE COLUMN `id_sales_order_detail` `id_sales_order_detail` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `init_branchs` CHANGE COLUMN `id_item` `id_item` INT NULL DEFAULT NULL");


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->string('id_business_partner')->nullable()->change();
            $table->string('id_business_partner_ima')->nullable()->change();
            $table->string('id_company')->nullable()->change();
            $table->string('id_sales_order')->nullable()->change();
            $table->string('id_sales_order_detail')->nullable()->change();
            $table->string('id_sales_invoice')->nullable()->change();
            $table->string('id_sales_invoice_detail')->nullable()->change();
            $table->string('id_delivery_order_detail')->nullable()->change();
        });
        Schema::table('locations', function (Blueprint $table) {
            $table->string('id_branch')->nullable()->change();
            $table->string('id_branch_ima')->nullable()->change();
        });
        Schema::table('init_branchs', function (Blueprint $table) {
            $table->string('id_company')->nullable()->change();
            $table->string('id_sales_order')->nullable()->change();
            $table->string('id_sales_order_detail')->nullable()->change();
            $table->string('id_item')->nullable()->change();
        });
    }
}
