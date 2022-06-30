<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeLoansReturn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_loan_returns', function (Blueprint $table) {
            $table->Increments('id_employee_loan_return');
            $table->unsignedInteger('id_employee_loan');
            $table->foreign('id_employee_loan', 'fk_return_id_employee_loan')->references('id_employee_loan')->on('employee_loans')->references('id_employee_loan')->onDelete('cascade');
            $table->date('return_date')->nullable();
            $table->integer('amount_return')->nullable();
            $table->enum('status_return',['Pending','Success'])->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_loans');
    }
}
