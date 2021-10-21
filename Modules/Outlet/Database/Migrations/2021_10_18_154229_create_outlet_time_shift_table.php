<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletTimeShiftTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_time_shift', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_time_shift');
            $table->unsignedInteger('id_outlet');
            $table->enum('shift', ['Morning', 'Evening']);
            $table->time('shift_time_start');
            $table->time('shift_time_end');
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
        Schema::dropIfExists('outlet_time_shift');
    }
}
