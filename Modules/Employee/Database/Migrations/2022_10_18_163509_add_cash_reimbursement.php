<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashReimbursement extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_reimbursement_product_icounts', function (Blueprint $table) {
            $table->integer('max_approve_date')->nullable();
            $table->integer('value')->nullable();
            $table->text('value_text')->nullable();
            $table->enum('type',['month','year'])->nullable();
            $table->string('month')->nullable();
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
           $table->dropColumn('max_approve_date')->nullable();
           $table->dropColumn('value')->nullable();
           $table->dropColumn('value_text')->nullable();
           $table->dropColumn('type')->nullable();
           $table->dropColumn('month')->nullable();
           });
    }
}
