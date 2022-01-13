<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnProjectsSurveyTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('projects_survey_location', function (Blueprint $table) {
            $table->decimal('location_height');
            $table->dropColumn('nama_kontraktor')->nullable();
            $table->dropColumn('cp_kontraktor')->nullable();
            $table->dropColumn('area_lokasi')->nullable();
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
            $table->dropColumn('location_height')->nullable();
            $table->string('nama_kontraktor')->nullable();
            $table->string('cp_kontraktor')->nullable();
            $table->string('area_lokasi')->nullable();
        });
    }
}
