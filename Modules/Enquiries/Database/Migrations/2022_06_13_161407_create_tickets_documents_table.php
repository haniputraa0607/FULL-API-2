<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTicketsDocumentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tickets_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_ticket');
            $table->string('attachment');
            $table->timestamps();

            $table->foreign('id_ticket', 'fk_tikcet_document')->references('id_ticket')->on('tickets')->onUpdate('CASCADE')->onDelete('CASCADE');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tickets_documents');
    }
}
