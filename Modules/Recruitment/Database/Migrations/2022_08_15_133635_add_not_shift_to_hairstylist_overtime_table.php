<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotShiftToHairstylistOvertimeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hairstylist_overtime', function (Blueprint $table) {
            $table->tinyInteger('not_schedule')->default(0)->after('reject_at');
            $table->time('schedule_in')->nullable()->after('not_schedule');
            $table->time('schedule_out')->nullable()->after('schedule_in');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hairstylist_overtime', function (Blueprint $table) {
            $table->dropColumn('not_schedule');
            $table->dropColumn('schedule_in');
            $table->dropColumn('schedule_out');
        });
    }
}
