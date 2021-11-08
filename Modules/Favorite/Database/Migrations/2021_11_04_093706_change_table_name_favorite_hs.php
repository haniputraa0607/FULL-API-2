<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTableNameFavoriteHs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('favorite_use_hair_stylist', function(Blueprint $table) {
            $table->renameColumn('id_favorite_use_hair_stylist', 'id_favorite_user_hair_stylist');
        });
        Schema::rename('favorite_use_hair_stylist', 'favorite_user_hair_stylist');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('favorite_user_hair_stylist', 'favorite_use_hair_stylist');
        Schema::table('favorite_use_hair_stylist', function(Blueprint $table) {
            $table->renameColumn('id_favorite_user_hair_stylist', 'id_favorite_use_hair_stylist');
        });
    }
}
