<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddSomeColumnToEmployeeOvertime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->time('rest_before')->nullable()->after('duration');
            $table->time('rest_after')->nullable()->after('rest_before');
            $table->text('notes')->nulable()->after('rest_after');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_overtime', function (Blueprint $table) {
            $table->dropColumn('rest_before');
            $table->dropColumn('rest_after');
            $table->dropColumn('notes');
        });
    }
}
