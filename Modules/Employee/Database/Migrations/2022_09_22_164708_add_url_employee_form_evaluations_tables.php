<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddUrlEmployeeFormEvaluationsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_form_evaluations', function (Blueprint $table) {
            $table->string('directory')->nullable()->after('status_form');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_form_evaluations', function (Blueprint $table) {
            $table->dropColumn('directory');
        });
    }
}
