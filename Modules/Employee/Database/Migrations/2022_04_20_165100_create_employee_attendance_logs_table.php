<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeAttendanceLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_attendance_logs', function (Blueprint $table) {
            $table->bigIncrements('id_employee_attendance_log');
            $table->unsignedBigInteger('id_employee_attendance');
            $table->enum('type', ['clock_in', 'clock_out']);
            $table->datetime('datetime');
            $table->decimal('latitude', 11, 8);
            $table->decimal('longitude', 11, 8);
            $table->string('location_name')->nullable();
            $table->string('photo_path')->nullable();
            $table->enum('status', ['Approved', 'Pending', 'Rejected'])->default('Approved');
            $table->unsignedInteger('approved_by')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->foreign('id_employee_attendance', 'fk_employee_attendance_log')->references('id_employee_attendance')->on('employee_attendances')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_attendance_logs');
    }
}
