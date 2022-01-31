<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateOutletChangeLocationFormSurveyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('outlet_change_location_form_survey', function (Blueprint $table) {
            $table->bigIncrements('id_outlet_change_location_form_survey');
            $table->integer('id_outlet_change_location')->unsigned()->nullable();
            $table->text('survey');
            $table->tinyinteger('potential');
            $table->string('surveyor',255);
            $table->date('survey_date');
            $table->text('note')->nullable();
            $table->string('attachment',255)->nullable();
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
        Schema::dropIfExists('outlet_change_location_form_survey');
    }
}
