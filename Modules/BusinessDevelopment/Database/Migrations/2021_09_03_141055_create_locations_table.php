<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->increments('id_location');
            $table->string('name');
            $table->string('address');
            $table->integer('id_city')->unsigned();
            $table->decimal('latitude');
            $table->decimal('longitude');
            $table->string('pic_name')->nullable();
            $table->string('pic_contact')->nullable();
            $table->integer('id_partner')->unsigned();
            $table->timestamps();
            $table->foreign('id_partner', 'fk_location_partner')->references('id_partner')->on('partners')->onDelete('restrict');
            $table->foreign('id_city', 'fk_location_city')->references('id_city')->on('cities')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('locations');
    }
}
