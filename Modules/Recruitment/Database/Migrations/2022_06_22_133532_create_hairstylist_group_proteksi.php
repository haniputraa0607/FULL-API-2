<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupProteksi extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_proteksi', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_proteksi');
            $table->unsignedInteger('id_hairstylist_group');
            $table->foreign('id_hairstylist_group', 'fk_proteksi_id_hairstylist_group')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('cascade');
            $table->integer('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_group_proteksi');
    }
}
