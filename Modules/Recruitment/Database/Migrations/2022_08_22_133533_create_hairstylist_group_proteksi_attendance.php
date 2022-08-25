<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupProteksiAttendance extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_proteksi_attendances', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_proteksi_attendance');
            $table->unsignedInteger('id_hairstylist_group');
            $table->foreign('id_hairstylist_group', 'fk_proteksi_attendance_hairstylist_group')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
            $table->unsignedInteger('id_hairstylist_group_default_proteksi_attendance');
            $table->foreign('id_hairstylist_group_default_proteksi_attendance', 'fk_proteksi_attendances_hairstylist_group')->references('id_hairstylist_group_default_proteksi_attendance')->on('hairstylist_group_default_proteksi_attendances')->onDelete('restrict');
            $table->integer('value')->nullable();
            $table->integer('amount')->nullable();
            $table->integer('amount_day')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_group_proteksi_attendances');
    }
}
