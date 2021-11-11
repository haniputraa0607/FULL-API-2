<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersBecomesIxoboxDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners_becomes_ixobox_document', function (Blueprint $table) {
            $table->increments('id_partners_becomes_ixobox_document');
            $table->integer('id_partners_becomes_ixobox')->unsigned();
            $table->string('title',255);
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_partners_becomes_ixobox', 'fk_partner_becomes_ixobox_document')->references('id_partners_becomes_ixobox')->on('partners_becomes_ixobox')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners_becomes_ixobox_document');
    }
}
