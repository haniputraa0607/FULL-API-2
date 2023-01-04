<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCashAdvanceProductIcount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_cash_advances', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->unsignedInteger('id_product_icount')->nullable()->after('price');
            $table->foreign('id_product_icount', 'fk_id_product_icount_cash_advance')->references('id_product_icount')->on('product_icounts')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_cash_advances', function (Blueprint $table) {
            $table->string('title');
            $table->dropColumn('id_product_icount');
           });
    }
}
