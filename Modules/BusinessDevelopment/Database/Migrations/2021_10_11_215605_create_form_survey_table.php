<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFormSurveyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_surveys', function (Blueprint $table) {
            $table->increments('id_form_survey');
            $table->integer('id_partner')->unsigned();
            $table->text('survey');
            $table->timestamps();
            $table->foreign('id_partner', 'fk_survey_partner')->references('id_partner')->on('partners')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('form_surveys');
    }
}
