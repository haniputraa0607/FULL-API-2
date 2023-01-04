<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddHairstylistGroupDefaultProteksiAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_group_default_proteksi_attendances', function (Blueprint $table) {
            $table->integer('amount_proteksi')->nullable()->default(0);
        });
    }

    public function down()
    {
        Schema::table('hairstylist_group_default_proteksi_attendances', function (Blueprint $table) {
            $table->dropColumn('amount_proteksi')->nullable()->default(0);
        });
    }
}
