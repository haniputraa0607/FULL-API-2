<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistNotAvailable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_not_available', function (Blueprint $table) {
            $table->bigIncrements('id_hairstylist_not_available');
            $table->unsignedInteger('id_outlet');
            $table->unsignedInteger('id_user_hair_stylist');
            $table->string('booking_date');
            $table->time('booking_time');
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
        Schema::dropIfExists('hairstylist_not_available');
    }
}
