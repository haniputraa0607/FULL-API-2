<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeInvoiceBapTable extends Migration
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
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `id_sales_invoice` `id_sales_invoice` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `id_business_partner` `id_business_partner` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `id_branch` `id_branch` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `dpp` `dpp` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `dpp_tax` `dpp_tax` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `tax` `tax` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `tax_value` `tax_value` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `netto` `netto` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `outstanding` `outstanding` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `invoice_bap` CHANGE COLUMN `amount` `amount` INT NULL DEFAULT NULL");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoice_bap', function (Blueprint $table) {
            $table->string('id_sales_invoice')->nullable()->change();
            $table->string('id_business_partner')->nullable()->change();
            $table->string('id_branch')->nullable()->change();
            $table->string('dpp')->nullable()->change();
            $table->string('dpp_tax')->nullable()->change();
            $table->string('tax')->nullable()->change();
            $table->string('tax_value')->nullable()->change();
            $table->string('netto')->nullable()->change();
            $table->string('outstanding')->nullable()->change();
            $table->string('amount')->nullable()->change();
        });
    }
}
