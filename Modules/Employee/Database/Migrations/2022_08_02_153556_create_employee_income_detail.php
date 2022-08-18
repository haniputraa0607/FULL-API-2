<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeIncomeDetail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_income_details', function (Blueprint $table) {
            $table->Increments('id_employee_income_detail');
            $table->unsignedInteger('id_employee_income');
            $table->unsignedInteger('id_outlet');
            $table->foreign('id_employee_income', 'fk_employee_income_detail')->references('id_employee_income')->on('employee_incomes')->onDelete('restrict');
            $table->string('source')->nullable();
            $table->string('reference')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->enum('type',['Incentive','Salary Cut'])->nullable();
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
        Schema::dropIfExists('employee_income_details');
    }
}
