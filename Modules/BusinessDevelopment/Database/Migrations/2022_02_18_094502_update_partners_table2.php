<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatePartnersTable2 extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('partners', function (Blueprint $table) {
            $table->dropForeign('fk_partner_term_payment');
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->string('id_company')->nullable()->change();
            $table->string('id_cluster')->nullable()->change();
            $table->string('id_term_payment')->nullable()->change();
            $table->string('id_account_payable')->nullable()->change();
            $table->string('id_account_receivable')->nullable()->change();
            $table->string('id_sales_disc')->nullable()->change();
            $table->string('id_purchase_disc')->nullable()->change();
            $table->string('id_tax_in')->nullable()->change();
            $table->string('id_tax_out')->nullable()->change();
            $table->string('id_salesman')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_company` `id_company` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_cluster` `id_cluster` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_term_payment` `id_term_payment` INT UNSIGNED NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_account_payable` `id_account_payable` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_account_receivable` `id_account_receivable` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_sales_disc` `id_sales_disc` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_purchase_disc` `id_purchase_disc` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_tax_in` `id_tax_in` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_tax_out` `id_tax_out` INT NULL DEFAULT NULL;
        ");
        \DB::statement("
            ALTER TABLE `partners` CHANGE COLUMN `id_salesman` `id_salesman` INT NULL DEFAULT NULL;
        ");
        Schema::table('partners', function (Blueprint $table) {
            $table->foreign('id_term_payment', 'fk_partner_term_payment')->references('id_term_of_payment')->on('term_of_payments');
        });
    }
}
