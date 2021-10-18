<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateConfirmationLetterTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('confirmation_letters', function (Blueprint $table) {
            $table->increments('id_confirmation_letter');
            $table->integer('id_partner')->unsigned();
            $table->string('no_letter');
            $table->string('location');
            $table->date('date');
            $table->string('attachment',255);
            $table->timestamps();
            $table->foreign('id_partner', 'fk_confirmation_letter_partner')->references('id_partner')->on('partners')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('confirmation_letter');
    }
}
