<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeSchedulesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->bigIncrements('id_employee_schedule');
            $table->unsignedBigInteger('id')->index();
            $table->unsignedInteger('id_outlet')->index();
            $table->unsignedInteger('approve_by')->nullable();
            $table->integer('last_updated_by')->nullable();
            $table->tinyInteger('schedule_month')->nullable();
            $table->year('schedule_year')->nullable();
            $table->datetime('request_at')->nullable();
            $table->datetime('approve_at')->nullable();
            $table->datetime('reject_at')->nullable();

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
        Schema::dropIfExists('employee_schedules');
    }
}
