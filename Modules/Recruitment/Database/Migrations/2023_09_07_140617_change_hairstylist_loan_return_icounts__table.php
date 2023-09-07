<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeHairstylistLoanReturnIcountsTable extends Migration
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
        \DB::statement("ALTER TABLE `hairstylist_loan_return_icounts` CHANGE COLUMN `SalesPaymentID` `SalesPaymentID` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `hairstylist_loan_return_icounts` CHANGE COLUMN `SalesInvoiceID` `SalesInvoiceID` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `hairstylist_loan_return_icounts` CHANGE COLUMN `BusinessPartnerID` `BusinessPartnerID` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `hairstylist_loan_return_icounts` CHANGE COLUMN `CompanyID` `CompanyID` INT NULL DEFAULT NULL");
        \DB::statement("ALTER TABLE `hairstylist_loan_return_icounts` CHANGE COLUMN `BranchID` `BranchID` INT NULL DEFAULT NULL");
        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_loan_return_icounts', function (Blueprint $table) {
            $table->string('SalesPaymentID')->nullable()->change();
            $table->string('SalesInvoiceID')->nullable()->change();
            $table->string('BusinessPartnerID')->nullable()->change();
            $table->string('CompanyID')->nullable()->change();
            $table->string('BranchID')->nullable()->change();
        });
    }
}
