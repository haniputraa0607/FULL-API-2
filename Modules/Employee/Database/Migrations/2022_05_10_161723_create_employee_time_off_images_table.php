<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeTimeOffImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_time_off_images', function (Blueprint $table) {
            $table->increments('id_employee_time_off_image');
            $table->bigInteger('id_employee_time_off')->unsigned();
            $table->string('path');
            $table->timestamps();

            $table->foreign('id_employee_time_off', 'fk_employee_time_off_image')->references('id_employee_time_off')->on('employee_time_off')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_time_off_images');
    }
}
