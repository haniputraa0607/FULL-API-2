<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFavoriteUserHairStylist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('favorite_use_hair_stylist', function (Blueprint $table) {
            $table->bigIncrements('id_favorite_use_hair_stylist');
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_user_hair_stylist');
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
        Schema::dropIfExists('favorite_use_hair_stylist');
    }
}
