<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletChangeLocationStepsTable extends Migration
{
    public function up()
    {
        Schema::create('outlet_change_location_steps', function (Blueprint $table) {
            $table->increments('id_outlet_change_location_steps');
            $table->integer('id_outlet_change_location')->unsigned();
            $table->enum('follow_up',["Select Location","Calculation","Confirmation Letter","Payment","Success"])->nullable();
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
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
        Schema::dropIfExists('outlet_change_location_steps');
    }
}
