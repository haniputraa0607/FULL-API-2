<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateDefaultOnProjectsSurveyLocationTable extends Migration
{
    public function __construct() {
        DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    public function up()
    {
        Schema::table('projects_survey_location', function (Blueprint $table) {
            $table->string('attachment',255)->nullable()->change();
            $table->text('note')->nullable()->change();
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
           $table->string('attachment',255)->nullable()->change();
           $table->text('note')->nullable()->change();
        });
    }
}
