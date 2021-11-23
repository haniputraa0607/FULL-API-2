<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletCloseTemporaryFormSurveyTable extends Migration
{
    public function up()
    {
        Schema::create('outlet_close_temporary_form_survey', function (Blueprint $table) {
            $table->increments('id_outlet_close_temporary_form_survey');
            $table->integer('id_outlet_close_temporary')->unsigned();
            $table->text('survey');
            $table->tinyinteger('potential');
            $table->string('surveyor',255);
            $table->date('survey_date');
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
            $table->timestamps();
            $table->foreign('id_outlet_close_temporary', 'fk_form_survey_outlet_close_temporary')->references('id_outlet_close_temporary')->on('outlet_close_temporary')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('outlet_close_temporary_form_survey');
    }
}
