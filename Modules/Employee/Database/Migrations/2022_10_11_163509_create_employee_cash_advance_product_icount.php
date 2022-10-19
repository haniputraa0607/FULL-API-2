<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeCashAdvanceProductIcount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_cash_advance_product_icounts', function (Blueprint $table) {
            $table->Increments('id_employee_cash_advance_product_icount');
            $table->unsignedInteger('id_product_icount');
            $table->foreign('id_product_icount', 'fk_id_product_icount_cash_advance_id_product_icount')->references('id_product_icount')->on('product_icounts')->onDelete('restrict');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_cash_advance_product_icounts');
    }
}
