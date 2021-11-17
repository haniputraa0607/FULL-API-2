<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCloseTemporaryLocationTable extends Migration
{
    public function up()
    {
        Schema::create('outlet_close_temporary_location', function (Blueprint $table) {
            $table->increments('id_outlet_close_temporary_location');
            $table->integer('id_outlet_close_temporary')->unsigned();
            $table->integer('id_location')->unsigned()->nullable();
            $table->string('name');
            $table->string('mall')->nullable();
            $table->string('address');
            $table->integer('from_id_city')->unsigned()->nullable();
            $table->integer('id_city')->unsigned()->nullable();
            $table->decimal('latitude');
            $table->decimal('longitude');
            $table->integer('id_brand')->unsigned()->nullable();
            $table->foreign('id_brand','fk_location_outlet_close_temporary_from_id_brand')->references('id_brand')->on('brands')->onDelete('restrict');             
            $table->integer('location_large')->nullable();
            $table->integer('rental_price')->nullable();
            $table->integer('service_charge')->nullable();
            $table->integer('promotion_levy')->nullable();
            $table->integer('renovation_cost')->nullable();
            $table->integer('partnership_fee')->nullable();
            $table->integer('income')->nullable();
            $table->datetime('start_date')->nullable();
            $table->datetime('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreign('from_id_city', 'fk_location_outlet_close_temporary_from_id_city')->references('id_city')->on('cities')->onDelete('restrict');
            $table->foreign('id_city', 'fk_location_outlet_close_temporary_id_city')->references('id_city')->on('cities')->onDelete('restrict');
            $table->foreign('id_location', 'fk_location_outlet_close_temporary_location')->references('id_location')->on('locations')->onDelete('restrict');
            $table->foreign('id_outlet_close_temporary', 'fk_location_outlet_close_temporary')->references('id_outlet_close_temporary')->on('outlet_close_temporary')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_close_temporary_location');
    }
}
