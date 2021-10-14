<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToFormSurvey extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('form_surveys', function (Blueprint $table) {
            $table->string('surveyor')->after('survey');
            $table->enum('potential', ['1','0'])->after('surveyor');
            $table->text('note')->after('potential');
            $table->date('survey_date')->after('note');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('form_surveys', function (Blueprint $table) {
            $table->dropColumn('surveyor');
            $table->dropColumn('potential');
            $table->dropColumn('note');
            $table->dropColumn('survey_date');
        });
    }
}
