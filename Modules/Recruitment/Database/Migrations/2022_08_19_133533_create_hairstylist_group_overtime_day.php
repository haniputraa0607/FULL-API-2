<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupOvertimeDay extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_overtime_days', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_overtime_day');
            $table->unsignedInteger('id_hairstylist_group');
            $table->foreign('id_hairstylist_group', 'fk_overtime_day_hairstylist_group')->references('id_hairstylist_group')->on('hairstylist_groups')->onDelete('restrict');
            $table->unsignedInteger('id_hairstylist_group_default_overtime_day');
            $table->foreign('id_hairstylist_group_default_overtime_day', 'fk_overtime_day_hairstylist_group_default_overtime_day')->references('id_hairstylist_group_default_overtime_day')->on('hairstylist_group_default_overtime_days')->onDelete('restrict');
            $table->integer('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_group_overtime_days');
    }
}
