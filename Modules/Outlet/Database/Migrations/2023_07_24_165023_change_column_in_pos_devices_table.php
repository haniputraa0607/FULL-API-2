<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeColumnInPosDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('pos_devices');

        Schema::create('pos_devices', function (Blueprint $table) {
            $table->increments('id_pos_device');
			$table->integer('id_outlet')->unsigned()->index('fk_pos_devices_outlets')->nullable();
			$table->enum('device_type', array('Android','IOS'))->nullable();
			$table->string('device_id', 250);
			$table->text('device_token');
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
        Schema::dropIfExists('pos_devices');
        
        Schema::create('pos_devices', function (Blueprint $table) {
            $table->increments('id_pos_device');
			$table->integer('id_outlet')->unsigned()->index('fk_pos_devices_outlets')->nullable();
			$table->enum('device_type', array('Android','IOS'))->nullable();
			$table->string('device_id', 20);
			$table->string('device_token', 160);
			$table->timestamps();
        });
    }
}
