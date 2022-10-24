<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeReimbursementProductIcountRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_role_reimbursement_product_icounts', function (Blueprint $table) {
            $table->Increments('id_employee_role_reimbursement_product_icount');
            $table->unsignedInteger('id_employee_reimbursement_product_icount');
            $table->foreign('id_employee_reimbursement_product_icount', 'fk_id_employee_reimbursement_product_icount')->references('id_employee_reimbursement_product_icount')->on('employee_reimbursement_product_icounts')->onDelete('restrict');
            $table->bigInteger('id_role')->unsigned();
            $table->foreign('id_role', 'fk_id_role_id_employee_reimbursement_product_icount')->references('id_role')->on('roles')->onDelete('restrict');
             $table->text('value_text')->nullable();
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
        Schema::dropIfExists('employee_role_reimbursement_product_icounts');
    }
}
