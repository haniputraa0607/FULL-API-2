<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeOutletAttendanceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_outlet_attendances', function (Blueprint $table) {
            $table->bigIncrements('id_employee_outlet_attendance');
            $table->unsignedBigInteger('id_employee_schedule_date')->nullable();
            $table->unsignedInteger('id');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->date('attendance_date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->timestamps();

            $table->foreign('id', 'fk_id_employee_outlet_attendance')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_employee_schedule_date')->on('employee_schedule_dates')->references('id_employee_schedule_date')->onDelete('set null');
            $table->foreign('id_outlet', 'fk_id_outlet_employee_attendance')->references('id_outlet')->on('outlets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_outlet_attendances');
    }
}
