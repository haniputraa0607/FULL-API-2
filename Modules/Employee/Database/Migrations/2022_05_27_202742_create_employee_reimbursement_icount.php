<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeReimbursementIcount extends Migration
{
     public function up()
    {
        Schema::create('employee_reimbursement_icounts', function (Blueprint $table) {
            $table->increments('id_employee_reimbursement_icount');
            $table->string('id_purchase_invoice')->nullable();
            $table->text('value_detail')->nullable();
            $table->enum('status',['Success','Failed'])->nullable();
            $table->timestamps();
        });
    }
    public function down()
    {
        Schema::dropIfExists('employee_reimbursement_icounts');
    }
}
