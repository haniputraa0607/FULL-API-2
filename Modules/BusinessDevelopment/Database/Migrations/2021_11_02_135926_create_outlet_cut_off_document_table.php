<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCutOffDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_cut_off_document', function (Blueprint $table) {
            $table->increments('id_outlet_cut_off_document');
            $table->integer('id_outlet_cut_off')->unsigned();
            $table->string('title',255)->nullable();
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_outlet_cut_off', 'fk_outlet_cut_off_document')->references('id_outlet_cut_off')->on('outlet_cut_off')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_cut_off_document');
    }
}
