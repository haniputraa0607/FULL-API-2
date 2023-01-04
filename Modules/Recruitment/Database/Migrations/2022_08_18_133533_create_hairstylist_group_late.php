<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupLate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_lates', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_late');
            $table->unsignedInteger('id_hairstylist_group');
            $table->foreign('id_hairstylist_group', 'fk_late_hairstylist_group')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
            $table->unsignedInteger('id_hairstylist_group_default_late');
            $table->foreign('id_hairstylist_group_default_late', 'fk_late_hairstylist_group_default_late')->references('id_hairstylist_group_default_late')->on('hairstylist_group_default_lates')->onDelete('restrict');
            $table->integer('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_group_lates');
    }
}
