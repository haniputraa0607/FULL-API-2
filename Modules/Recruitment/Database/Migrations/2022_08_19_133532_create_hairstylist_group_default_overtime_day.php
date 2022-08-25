<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateHairstylistGroupDefaultOvertimeDay extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hairstylist_group_default_overtime_days', function (Blueprint $table) {
            $table->Increments('id_hairstylist_group_default_overtime_day');
            $table->integer('days')->nullable();
            $table->integer('value')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hairstylist_group_default_overtime_days');
    }
}
