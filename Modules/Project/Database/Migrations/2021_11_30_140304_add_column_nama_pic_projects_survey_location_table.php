<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnNamaPicProjectsSurveyLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_survey_location', function (Blueprint $table) {
            $table->string('nama_pic_mall',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('projects_survey_location', function (Blueprint $table) {
            $table->dropColumn('nama_pic_mall',255)->nullable();
        });
    }
}
