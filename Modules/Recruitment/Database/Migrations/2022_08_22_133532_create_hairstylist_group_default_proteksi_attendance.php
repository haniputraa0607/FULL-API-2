<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupDefaultProteksiAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_default_proteksi_attendances', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_default_proteksi_attendance');
            $table->string('month')->nullable();
            $table->integer('value')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('amount_day')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_group_default_proteksi_attendances');
    }
}
