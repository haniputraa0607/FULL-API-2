<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersClosePermanentOutletTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners_close_permanent_outlet', function (Blueprint $table) {
            $table->increments('id_partners_close_permanent_outlet');
            $table->integer('id_partners_close_permanent')->unsigned();
            $table->integer('id_outlet')->unsigned();
            $table->timestamps();
            $table->foreign('id_partners_close_permanent', 'fk_partner_close_permanent_outlet')->references('id_partners_close_permanent')->on('partners_close_permanent')->onDelete('restrict');
            $table->foreign('id_outlet', 'fk_partner_close_permanent_outlets')->references('id_outlet')->on('outlets')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners_close_permanent_outlet');
    }
}
