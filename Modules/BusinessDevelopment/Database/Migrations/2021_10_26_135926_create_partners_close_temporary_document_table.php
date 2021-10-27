<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePartnersCloseTemporaryDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('partners_close_temporary_document', function (Blueprint $table) {
            $table->increments('id_partners_close_temporary_document');
            $table->integer('id_partners_close_temporary')->unsigned();
            $table->string('title',255);
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_partners_close_temporary', 'fk_partner_close_temporary_document')->references('id_partners_close_temporary')->on('partners_close_temporary')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('partners_close_temporary');
    }
}
