<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCloseTemporaryDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_close_temporary_document', function (Blueprint $table) {
            $table->increments('id_outlet_close_temporary_document');
            $table->integer('id_outlet_close_temporary')->unsigned();
            $table->string('title',255)->nullable();
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_outlet_close_temporary', 'fk_outlet_close_temporary_document')->references('id_outlet_close_temporary')->on('outlet_close_temporary')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_close_temporary_document');
    }
}
