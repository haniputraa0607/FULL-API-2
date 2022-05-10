<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeTimeOffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_time_off', function (Blueprint $table) {
            $table->bigIncrements('id_employee_time_off');
            $table->integer('id_employee')->unsigned();
            $table->integer('id_outlet')->unsigned();
            $table->integer('approve_by')->unsigned()->nullable();
            $table->integer('request_by')->unsigned();
            $table->dateTime('date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->dateTime('request_at');
            $table->dateTime('approve_at')->nullable();
            $table->dateTime('reject_at')->nullable();
            $table->timestamps();

            $table->foreign('id_employee')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_outlet')->references('id_outlet')->on('outlets')->onDelete('cascade');
            $table->foreign('approve_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('request_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_time_off');
    }
}
