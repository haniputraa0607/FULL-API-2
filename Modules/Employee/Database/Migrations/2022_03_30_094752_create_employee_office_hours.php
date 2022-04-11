<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeOfficeHours extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_office_hours', function (Blueprint $table) {
            $table->bigIncrements('id_employee_office_hour');
            $table->string('office_hour_name');
            $table->enum('office_hour_type', ['Use Shift', 'Without Shift'])->nullable();
            $table->time('office_hour_start')->nullable();
            $table->time('office_hour_end')->nullable();
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
        Schema::dropIfExists('employee_office_hours');
    }
}
