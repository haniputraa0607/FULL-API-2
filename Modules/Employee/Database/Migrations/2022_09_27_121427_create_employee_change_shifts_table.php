<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeChangeShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_change_shifts', function (Blueprint $table) {
            $table->increments('id_employee_change_shift');
            $table->unsignedInteger('id_user');
            $table->date('change_shift_date');
            $table->unsignedBigInteger('id_employee_office_hour_shift');
            $table->text('reason')->nullable();
            $table->enum('status',['Pending','Approved','Rejected'])->default('Pending');
            $table->unsignedInteger('id_approve')->nullable();
            $table->date('approve_date')->nullable();
            $table->timestamps();

            $table->foreign('id_user', 'fk_employee_change_shift_user')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_approve', 'fk_employee_change_shift_approve')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_employee_office_hour_shift', 'fk_employee_change_shift_hour')->references('id_employee_office_hour_shift')->on('employee_office_hour_shift')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_change_shifts');
    }
}
