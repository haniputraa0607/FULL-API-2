<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners_logs', function (Blueprint $table) {
            $table->increments('id_partners_log');
            $table->integer('id_partner');
            $table->string('update_name');
            $table->string('update_phone');
            $table->string('update_email');
            $table->string('update_address');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners_logs');
    }
}
