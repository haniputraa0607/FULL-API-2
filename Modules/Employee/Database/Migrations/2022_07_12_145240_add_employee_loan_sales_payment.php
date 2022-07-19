<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmployeeLoanSalesPayment extends Migration
{
    public function __construct()
    {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    } 
    public function up()
    {
      Schema::table('employee_loans', function (Blueprint $table) {
            $table->integer('id_employee_sales_payment')->nullable();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         Schema::table('employee_loans', function (Blueprint $table) {
            $table->dropColumn('id_employee_sales_payment')->nullable();
        });
    }
}
