<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeOfficeHourShift extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_office_hour_shift', function (Blueprint $table) {
            $table->bigIncrements('id_employee_office_hour_shift');
            $table->unsignedInteger('id_employee_office_hour');
            $table->string('shift_name');
            $table->time('shift_start');
            $table->time('shift_end');
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
        Schema::dropIfExists('employee_office_hour_shift');
    }
}
