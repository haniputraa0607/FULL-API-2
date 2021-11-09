<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletChangeOwnershipDocumentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_change_ownership_document', function (Blueprint $table) {
            $table->increments('id_outlet_change_ownership_document');
            $table->integer('id_outlet_change_ownership')->unsigned();
            $table->string('title',255)->nullable();
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_outlet_change_ownership', 'fk_outlet_change_ownership_document')->references('id_outlet_change_ownership')->on('outlet_change_ownership')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_change_ownership_document');
    }
}
