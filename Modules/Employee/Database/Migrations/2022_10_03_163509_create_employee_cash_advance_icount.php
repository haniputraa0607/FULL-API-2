<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeCashAdvanceIcount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_cash_advance_icounts', function (Blueprint $table) {
            $table->Increments('id_cash_advance_icount');
            $table->unsignedInteger('id_employee_cash_advance')->nullable();
            $table->foreign('id_employee_cash_advance', 'fk_id_employee_cash_advance_employee_cash_advance_icounts')->references('id_employee_cash_advance')->on('employee_cash_advances')->onDelete('restrict');
            $table->string('id_purchase_deposit_request')->nullable();
            $table->text('value_detail')->nullable();
             $table->enum('status',['Success','Failed'])->nullable();
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
        Schema::dropIfExists('employee_cash_advance_icounts');
    }
}
