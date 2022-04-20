<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_attendance_requests', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_attendance_request');
            $table->unsignedInteger('id');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->unsignedBigInteger('id_hairstylist_schedule_date');
            $table->time('clock_in');
            $table->time('clock_out');
            $table->string('notes');
            $table->enum('status', ['Pending', 'Accepted', 'Rejected'])->default('Pending');
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_id_outlet_employee_attendance_request')->references('id_outlet')->on('outlets')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_attendance_requests');
    }
}
