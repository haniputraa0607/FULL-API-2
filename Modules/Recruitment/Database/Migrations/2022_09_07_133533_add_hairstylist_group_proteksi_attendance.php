<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHairstylistGroupProteksiAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_group_proteksi_attendances', function (Blueprint $table) {
            $table->integer('amount_proteksi')->nullable();
        });
    }

    public function down()
    {
        Schema::table('hairstylist_group_proteksi_attendances', function (Blueprint $table) {
            $table->dropColumn('amount_proteksi')->nullable();
        });
    }
}
