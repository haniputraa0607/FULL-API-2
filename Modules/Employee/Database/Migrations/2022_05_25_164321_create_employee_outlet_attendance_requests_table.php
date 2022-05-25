<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeOutletAttendanceRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_outlet_attendance_requests', function (Blueprint $table) {
            $table->bigIncrements('id_employee_outlet_attendance_request');
            $table->unsignedInteger('id');
            $table->unsignedInteger('id_outlet')->nullable();
            $table->date('attendance_date');
            $table->time('clock_in')->nullable();
            $table->time('clock_out')->nullable();
            $table->string('notes');
            $table->enum('status', ['Pending', 'Accepted', 'Rejected'])->default('Pending');
            $table->timestamps();

            $table->foreign('id_outlet', 'fk_id_outlet_employee_outlet_attendance_request')->references('id_outlet')->on('outlets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_outlet_attendance_requests');
    }
}
