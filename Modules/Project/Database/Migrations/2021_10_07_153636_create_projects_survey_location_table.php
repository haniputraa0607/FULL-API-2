<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProjectsSurveyLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('projects_survey_location', function (Blueprint $table) {
            $table->increments('id_projects_survey_location');
            $table->integer('id_project')->unsigned();
            $table->text('note');
            $table->string('surveyor');
            $table->decimal('location_length');
            $table->decimal('location_width');
            $table->decimal('location_large');
            $table->enum('status',['Process','Success'])->default('Process');
            $table->dateTime('survey_date')->nullable();
            $table->string('attachment',255);
            $table->foreign('id_project', 'fk_survey_location_project')->references('id_project')->on('projects')->onDelete('restrict');
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
        Schema::dropIfExists('projects_surver_location');
    }
}
