<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeScheduleDatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_schedule_dates', function (Blueprint $table) {
            $table->bigIncrements('id_employee_schedule_date');
            $table->unsignedBigInteger('id_employee_schedule')->index();
            $table->unsignedBigInteger('id_employee_attendance')->index()->nullable();
            $table->datetime('date');
            $table->enum('shift', ['Non Shift', 'Morning', 'Midldle','Evening']);
            $table->enum('request_by', ['Employee', 'Admin'])->nullable();
            $table->tinyInteger('is_overtime')->nullable();
            $table->time('time_start')->nullable();
            $table->time('time_end')->nullable();
            $table->string('notes')->nullable();

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
        Schema::dropIfExists('employee_schedule_dates');
    }
}
