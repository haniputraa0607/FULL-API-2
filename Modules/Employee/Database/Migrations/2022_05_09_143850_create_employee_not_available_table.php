<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeNotAvailableTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_not_available', function (Blueprint $table) {
            $table->bigIncrements('id_employee_not_available');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_employee');
            $table->integer('id_employee_time_off')->nullable();
            $table->dateTime('booking_start')->nullable();
            $table->dateTime('booking_end')->nullable();
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
        Schema::dropIfExists('employee_not_available');
    }
}
