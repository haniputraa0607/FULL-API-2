<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashAdvanceName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_cash_advance_product_icounts', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
        Schema::table('employee_reimbursement_product_icounts', function (Blueprint $table) {
            $table->string('name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_cash_advance_product_icounts', function (Blueprint $table) {
           $table->dropColumn('name')->nullable();
           });
        Schema::table('employee_reimbursement_product_icounts', function (Blueprint $table) {
           $table->dropColumn('name')->nullable();
           });
    }
}
