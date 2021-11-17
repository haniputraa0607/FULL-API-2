<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersBecomesIxoboxOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners_becomes_ixobox_outlet', function (Blueprint $table) {
            $table->increments('id_partners_becomes_ixobox_outlet');
            $table->integer('id_partners_becomes_ixobox')->unsigned();
            $table->integer('id_outlet')->unsigned();
            $table->timestamps();
            $table->foreign('id_partners_becomes_ixobox', 'fk_partner_becomes_ixobox_outlet')->references('id_partners_becomes_ixobox')->on('partners_becomes_ixobox')->onDelete('restrict');
            $table->foreign('id_outlet', 'fk_partner_becomes_ixobox_outlets')->references('id_outlet')->on('outlets')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners_becomes_ixobox_outlet');
    }
}
