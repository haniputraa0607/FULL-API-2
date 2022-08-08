<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeIncome extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_incomes', function (Blueprint $table) {
            $table->Increments('id_employee_income');
            $table->unsignedInteger('id_user');
            $table->foreign('id_user', 'fk_employee_income_id_user')->references('id')->on('users')->onDelete('restrict');
            $table->date('periode')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->enum('status',['Draft','Pending','Completed','Cancelled'])->nullable();
            $table->text('notes')->nullable();
            $table->bigInteger('amount')->nullable();
            $table->text('value_detail')->nullable();
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
        Schema::dropIfExists('employee_incomes');
    }
}
