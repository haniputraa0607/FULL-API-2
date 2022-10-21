<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDataReimbursements extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_reimbursement_product_icounts', function (Blueprint $table) {
            $table->string('reset_date')->nullable();
            $table->dropColumn('value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_reimbursement_product_icounts', function (Blueprint $table) {
           $table->dropColumn('reset_date')->nullable();
           $table->integer('value')->nullable();
           });
    }
}
