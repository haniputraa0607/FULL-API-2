<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateEmployeeDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_devices', function (Blueprint $table) {
            $table->increments('id_employee_device');
            $table->unsignedInteger('id_employee')->nullable();
            $table->enum('device_type', ["Android", 'IOS'])->nullable();
            $table->string('device_id', 200);
            $table->string('device_token', 250)->nullable();

            $table->timestamps();

			$table->foreign('id_employee', 'fk_employee_devices_employees')->references('id')->on('users')->onUpdate('CASCADE')->onDelete('CASCADE');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('employee_devices');
    }
}
