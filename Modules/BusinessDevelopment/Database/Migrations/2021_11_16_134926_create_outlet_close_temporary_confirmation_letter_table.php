<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCloseTemporaryConfirmationLetterTable extends Migration
{
    public function up()
    {
        Schema::create('outlet_close_temporary_confirmation_letter', function (Blueprint $table) {
            $table->increments('id_outlet_close_temporary_confirmation_letter');
            $table->integer('id_outlet_close_temporary')->unsigned();
            $table->string('no_letter',255);
            $table->string('location',255);
            $table->date('date');
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_outlet_close_temporary', 'fk_confirmation_letter_outlet_close_temporary')->references('id_outlet_close_temporary')->on('outlet_close_temporary')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_close_temporary_steps');
    }
}
