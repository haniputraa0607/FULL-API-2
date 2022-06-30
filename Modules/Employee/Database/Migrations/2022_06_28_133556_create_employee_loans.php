<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeLoans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->Increments('id_employee_loan');
            $table->unsignedInteger('id_user');
            $table->Integer('id_employee_category_loan');
            $table->foreign('id_user')->on('users')->references('id')->onDelete('cascade');
            $table->date('effective_date')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('installment')->nullable();
            $table->enum('type',['Flat'])->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('employee_loans');
    }
}
