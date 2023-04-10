<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeTypeHairstylistNotAvailable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_not_available', function (Blueprint $table) {
            $table->unsignedInteger('id_user_hair_stylist')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_not_available', function (Blueprint $table) {
            $table->unsignedInteger('id_user_hair_stylist');
        });
    }
}
