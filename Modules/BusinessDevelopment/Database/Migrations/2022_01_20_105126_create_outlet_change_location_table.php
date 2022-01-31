<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletChangeLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_change_location', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_change_location');
            $table->integer('id_location')->nullable();
            $table->integer('id_outlet')->nullable();
            $table->integer('to_id_outlet')->nullable();
            $table->integer('id_partner')->nullable();
            $table->date('date')->nullable();
            $table->enum('status_steps',['Survey Location','Finished Survey Location','Select Location','Calculation','Confirmation Letter','Payment'])->default('Survey Location');
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
        Schema::dropIfExists('outlet_change_location');
    }
}
