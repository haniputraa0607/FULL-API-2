<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLegalAgreementTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('legal_agreements', function (Blueprint $table) {
            $table->increments('id_legal_agreement');
            $table->integer('id_partner')->unsigned();
            $table->integer('id_location')->unsigned();
            $table->string('no_letter')->nullable();
            $table->date('date_letter')->nullable();
            $table->string('attachment')->nullable();

            $table->foreign('id_partner', 'fk_legal_agreement_partner')->references('id_partner')->on('partners')->onDelete('cascade');
            $table->foreign('id_location', 'fk_legal_agreement_location')->references('id_location')->on('locations')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('legal_agreements');
    }
}
